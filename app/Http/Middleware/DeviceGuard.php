<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Mail\GxStyledMail;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Anti-abuse middleware for tournament registration.
 *
 * Layers:
 * 1. Honeypot — hidden field "website" must be empty
 * 2. Rate Limiting — max N attempts per IP per window
 * 3. Device Fingerprint — blocks known abuser device IDs
 * 4. Profanity / Insult Filter — auto-detects abusive content
 */
class DeviceGuard
{
    /**
     * Abusive keywords and patterns (case-insensitive, partial match).
     * Exhaustive list including Chilean slurs, local variations, and hostile terms.
     */
    private const BLOCKED_PATTERNS = [
        // --- Los Clásicos y Variaciones ---
        'mamon', 'mamones', 'losmeo', 'los meo', 'wekito', 'wekos', 'weko', 'wekit', 'guerrillero', 'guerrillera', 'guerrillerx',
        'guerrilleroswekos', 'guerrillerospobre', 'guerrilleroscl', 'gxwekos', 'gxbasura', 'gxmierda',
        
        // --- Insultos Chilenos de Alta Intensidad ---
        'ctm', 'conchetumare', 'conchetumadre', 'conchaetumare', 'conchaetumadre', 'conchesumadre', 'conchesumare',
        'chuchetumare', 'chuchatumare', 'reconchetumare', 're-conchetumare', 'csm', 'ctmre',
        'maricon', 'maricón', 'marakita', 'maraca', 'maraco', 'puto', 'puta', 'mierda', 'cagada',
        'aweonao', 'aweona', 'weon', 'weón', 'wn', 'wna', 'wea', 'weas', 'ahuevonado', 'ahuevonao',
        'culiao', 'culiado', 'culiá', 'qliao', 'qlio', 'ql', 'qlo', 'reqliao', 're-culiao',
        'perkin', 'perkins', 'perkinazo', 'perkines', 'sacoewea', 'sacowea', 'sacodewea',
        'flaite', 'longi', 'gil', 'penco', 'penca', 'callampa', 'callampero',
        
        // --- Referencias Vulgares ---
        'pico', 'tula', 'pichula', 'corneta', 'chupa', 'chupalo', 'chupenlo', 'chupala', 'chupenla',
        'chupapico', 'chupatula', 'mamalo', 'picon', 'tulaql', 'perroql', 'perroqlo', 'sapoculiao', 'sapoql',
        
        // --- Hostilidad y Descalificación ---
        'bastardo', 'zorra', 'perra', 'malnacido', 'hdp', 'hijodeputa', 'hijodeperra',
        'estafa', 'estafadores', 'robando', 'ladrones', 'rateros', 'sinverguenzas', 'basura', 'escoria',
        'pobre', 'pobretone', 'resentido', 'antisocial', 'delincuente', 'lacra', 'cancer', 'parasito',
        'mueranse', 'muerete', 'entierrense', 'asqueroso', 'asco', 'vomito', 'fome', 'webon', 'huevon',
        'perkinql', 'perkinqlo', 'sapoql', 'sapoculiao', 'conchesumadre',
    ];

    /**
     * Email domain patterns that are obviously fake/troll or temporary.
     */
    private const BLOCKED_EMAIL_DOMAINS = [
        // Trolls locales
        'wekos.cl', 'guerrilleroswekos.cl', 'losmeo.cl', 'mamon.cl', 'fake.cl', 'troll.cl', 'spam.cl', 
        'basura.cl', 'mierda.cl', 'putas.cl', 'guerrilleras.cl', 'maricones.cl', 'losmeos.cl', 
        'sacoweas.cl', 'conchesumadres.cl',
        
        // Temporales y deshechables (Spambots)
        'troll.com', 'fake.com', 'trash.com', 'temp-mail.org', '10minutemail.com', 'guerrillamail.com', 
        'mailinator.com', 'dispostable.com', 'getnada.com', 'yopmail.com', 'sharklasers.com',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // ── Layer 1: Honeypot ────────────────────────────────────
        if ($this->failsHoneypot($request)) {
            Log::warning('[DeviceGuard] Honeypot triggered', [
                'ip' => $this->resolveIp($request),
                'ua' => $request->userAgent(),
            ]);

            return $this->shadowBanResponse($request);
        }

        // ── Layer 2: Rate Limiting ───────────────────────────────
        $rateLimitKey = 'reg_attempt:' . $this->resolveIp($request);
        $maxAttempts = (int) config('device_fingerprint.rate_limit_max', 5);
        $decayMinutes = (int) config('device_fingerprint.rate_limit_decay_minutes', 10);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            Log::warning('[DeviceGuard] Rate limit exceeded', [
                'ip' => $this->resolveIp($request),
                'device_id' => $request->input('device_id', 'unknown'),
            ]);

            return $this->shadowBanResponse($request);
        }

        RateLimiter::hit($rateLimitKey, $decayMinutes * 60);

        // ── Layer 3: Device Fingerprint ──────────────────────────
        $deviceId = $request->input('device_id', '');
        $blockedIds = config('device_fingerprint.blocked_ids', []);

        if (!empty($deviceId) && in_array($deviceId, $blockedIds, true)) {
            $telemetry = $this->extractTelemetry($request);

            Log::channel('single')->warning('[DeviceGuard] BLOCKED DEVICE detected', $telemetry);

            AuditLogger::log('blocked_device', 'TournamentRegistration', null, $telemetry);

            $this->sendAbuseAlert($request, $telemetry, 'device_blocked');

            return $this->shadowBanResponse($request, $telemetry);
        }

        // ── Layer 4: Profanity / Insult Filter ───────────────────
        $profanityResult = $this->detectProfanity($request);

        if ($profanityResult !== null) {
            $telemetry = $this->extractTelemetry($request);
            $telemetry['profanity_match'] = $profanityResult['match'];
            $telemetry['profanity_field'] = $profanityResult['field'];

            Log::channel('single')->warning('[DeviceGuard] PROFANITY detected in registration', $telemetry);

            AuditLogger::log('blocked_profanity', 'TournamentRegistration', null, $telemetry);

            $this->sendAbuseAlert($request, $telemetry, 'profanity_detected');

            return $this->shadowBanResponse($request, $telemetry);
        }

        return $next($request);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function failsHoneypot(Request $request): bool
    {
        $value = $request->input('website');
        return $value !== null && $value !== '';
    }

    private function detectProfanity(Request $request): ?array
    {
        $fieldsToCheck = [
            'blader_name' => $request->input('blader_name', ''),
            'email'       => $request->input('email', ''),
            'first_name'  => $request->input('first_name', ''),
            'last_name'   => $request->input('last_name', ''),
        ];

        foreach ($fieldsToCheck as $fieldName => $fieldValue) {
            if (empty($fieldValue)) {
                continue;
            }

            // Normalize: lowercase, remove accents, collapse spaces
            $normalized = $this->normalizeForCheck((string) $fieldValue);

            // Check blocked patterns
            foreach (self::BLOCKED_PATTERNS as $pattern) {
                $normalizedPattern = $this->normalizeForCheck($pattern);
                if ($normalizedPattern !== '' && str_contains($normalized, $normalizedPattern)) {
                    return [
                        'field' => $fieldName,
                        'match' => $pattern,
                    ];
                }
            }

            // Check email domains specifically
            if ($fieldName === 'email') {
                $domain = strtolower(trim(substr(strrchr($fieldValue, '@') ?: '', 1)));
                foreach (self::BLOCKED_EMAIL_DOMAINS as $blockedDomain) {
                    if ($domain === $blockedDomain || str_ends_with($domain, '.' . $blockedDomain)) {
                        return [
                            'field' => 'email_domain',
                            'match' => $blockedDomain,
                        ];
                    }
                }
            }
        }

        return null;
    }

    private function normalizeForCheck(string $value): string
    {
        $lower = mb_strtolower($value);
        // Remove accents
        $noAccents = strtr($lower, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ]);
        // Remove non-alphanumeric (keep @ and . for emails)
        $cleaned = preg_replace('/[^a-z0-9@.]/', '', $noAccents);
        return $cleaned ?? $noAccents;
    }

    private function resolveIp(Request $request): string
    {
        // Hostinger / Cloudflare headers
        foreach (['CF-Connecting-IP', 'X-Forwarded-For', 'X-Real-IP'] as $header) {
            $ip = $request->header($header);
            if (!empty($ip)) {
                return explode(',', $ip)[0];
            }
        }

        return $request->ip() ?? '0.0.0.0';
    }

    private function extractTelemetry(Request $request): array
    {
        return [
            'device_id'   => $request->input('device_id', 'unknown'),
            'ip'          => $this->resolveIp($request),
            'user_agent'  => $request->userAgent() ?? 'unknown',
            'screen'      => $request->input('d_screen', 'unknown'),
            'cores'       => $request->input('d_cores', 'unknown'),
            'memory'      => $request->input('d_memory', 'unknown'),
            'platform'    => $request->input('d_platform', 'unknown'),
            'timezone'    => $request->input('d_timezone', 'unknown'),
            'language'    => $request->input('d_language', 'unknown'),
            'cookies'     => $request->input('d_cookies', 'No detectadas'),
            'timestamp'   => now()->toIso8601String(),
            'event_id'    => $request->route('event')?->id ?? $request->route('event'),
            'blader_name' => $request->input('blader_name', 'unknown'),
            'email'       => $request->input('email', 'unknown'),
            'exif'        => $this->getExifData($request),
        ];
    }

    private function shadowBanResponse(Request $request, array $telemetry = []): Response
    {
        // For Inertia requests: redirect back with a fake success flash
        if ($request->header('X-Inertia')) {
            $ip = htmlspecialchars($telemetry['ip'] ?? $this->resolveIp($request), ENT_QUOTES, 'UTF-8');
            $ua = htmlspecialchars($telemetry['user_agent'] ?? $request->userAgent() ?? '?', ENT_QUOTES, 'UTF-8');
            $screen = htmlspecialchars((string) ($telemetry['screen'] ?? '?'), ENT_QUOTES, 'UTF-8');
            $memory = htmlspecialchars((string) ($telemetry['memory'] ?? '?'), ENT_QUOTES, 'UTF-8');
            $cookies = htmlspecialchars((string) ($telemetry['cookies'] ?? $request->input('d_cookies', 'No detectadas')), ENT_QUOTES, 'UTF-8');
            $exif = $telemetry['exif'] ?? ['model' => 'Desconocido', 'gps' => 'No disponible'];

            $trollMessage = "Hola Fan :)<br><br>"
                . "<b>Tu navegador es:</b> {$ua}<br>"
                . "<b>Tu resolución es:</b> {$screen}<br>"
                . "<b>Tu memoria es:</b> {$memory} GB<br>"
                . "<b>Tus cookies son:</b> <div style='font-family:monospace; font-size:10px; padding:10px; background:rgba(0,0,0,0.3); max-height:80px; overflow:auto; word-break:break-all;'>{$cookies}</div><br>"
                . "<b>Dispositivo Cámara:</b> {$exif['model']}<br>"
                . "<b>Ubicación Foto:</b> {$exif['gps']}<br><br>"
                . "<b>Ten más hombria y cuidado donde rellenas un formulario la próxima vez, gracias por entregarme el acceso a tu navegador, sistema y datos. 😈</b>";

            return back()->with([
                'success'        => $trollMessage,
                '_shadow_banned' => true,
            ]);
        }

        // For non-Inertia (API / raw POST)
        return response()->json([
            'message' => 'Registro recibido correctamente.',
        ], 200);
    }

    private function sendAbuseAlert(Request $request, array $telemetry, string $reason = 'unknown'): void
    {
        $recipient = config('device_fingerprint.admin_alert_email');
        if (empty($recipient)) {
            return;
        }

        app()->terminating(function () use ($recipient, $telemetry, $reason) {
            try {
                $ip = htmlspecialchars($telemetry['ip'], ENT_QUOTES, 'UTF-8');
                $deviceId = htmlspecialchars($telemetry['device_id'], ENT_QUOTES, 'UTF-8');
                $ua = htmlspecialchars($telemetry['user_agent'], ENT_QUOTES, 'UTF-8');

                $reasonLabel = match ($reason) {
                    'device_blocked' => '🔒 Dispositivo bloqueado por fingerprint',
                    'profanity_detected' => '🤬 Contenido ofensivo detectado',
                    default => '⚠️ Comportamiento sospechoso',
                };

                $body = '<p>🚨 <strong>' . $reasonLabel . '</strong></p>'
                    . '<div class="highlight-box">'
                    . '<p style="margin:0 0 8px; font-weight:700; color:#E10600;">Datos del Dispositivo</p>'
                    . '<ul style="margin:0; padding-left:18px; font-size:0.9rem;">'
                    . '<li><strong>Device ID:</strong> <code>' . $deviceId . '</code></li>'
                    . '<li><strong>IP:</strong> ' . $ip . '</li>'
                    . '<li><strong>User Agent:</strong> ' . $ua . '</li>'
                    . '<li><strong>Pantalla:</strong> ' . htmlspecialchars((string) $telemetry['screen'], ENT_QUOTES, 'UTF-8') . '</li>'
                    . '<li><strong>CPU Cores:</strong> ' . htmlspecialchars((string) $telemetry['cores'], ENT_QUOTES, 'UTF-8') . '</li>'
                    . '<li><strong>Memoria:</strong> ' . htmlspecialchars((string) $telemetry['memory'], ENT_QUOTES, 'UTF-8') . ' GB</li>'
                    . '<li><strong>Plataforma:</strong> ' . htmlspecialchars((string) $telemetry['platform'], ENT_QUOTES, 'UTF-8') . '</li>'
                    . '<li><strong>Zona Horaria:</strong> ' . htmlspecialchars((string) $telemetry['timezone'], ENT_QUOTES, 'UTF-8') . '</li>'
                    . '<li><strong>Idioma:</strong> ' . htmlspecialchars((string) $telemetry['language'], ENT_QUOTES, 'UTF-8') . '</li>'
                    . '</ul>'
                    . '</div>'
                    . '<div class="highlight-box">'
                    . '<p style="margin:0 0 8px; font-weight:700;">Datos del Intento</p>'
                    . '<ul style="margin:0; padding-left:18px; font-size:0.9rem;">'
                    . '<li><strong>Blader Name:</strong> ' . htmlspecialchars((string) $telemetry['blader_name'], ENT_QUOTES, 'UTF-8') . '</li>'
                    . '<li><strong>Email:</strong> ' . htmlspecialchars((string) $telemetry['email'], ENT_QUOTES, 'UTF-8') . '</li>'
                    . '<li><strong>Event ID:</strong> ' . htmlspecialchars((string) $telemetry['event_id'], ENT_QUOTES, 'UTF-8') . '</li>'
                    . '<li><strong>Timestamp:</strong> ' . htmlspecialchars((string) $telemetry['timestamp'], ENT_QUOTES, 'UTF-8') . '</li>'
                    . '<li><strong>Cámara Foto:</strong> ' . htmlspecialchars($telemetry['exif']['model'] ?? '?', ENT_QUOTES, 'UTF-8') . '</li>'
                    . '<li><strong>GPS Foto:</strong> ' . ($telemetry['exif']['gps'] ?? '?') . '</li>'
                    . '</ul>'
                    . '</div>';

                if (!empty($telemetry['profanity_match'])) {
                    $body .= '<div class="highlight-box">'
                        . '<p style="margin:0 0 8px; font-weight:700; color:#F59E0B;">Detalle de Profanidad</p>'
                        . '<ul style="margin:0; padding-left:18px; font-size:0.9rem;">'
                        . '<li><strong>Campo:</strong> ' . htmlspecialchars((string) $telemetry['profanity_field'], ENT_QUOTES, 'UTF-8') . '</li>'
                        . '<li><strong>Patrón detectado:</strong> <code>' . htmlspecialchars((string) $telemetry['profanity_match'], ENT_QUOTES, 'UTF-8') . '</code></li>'
                        . '</ul>'
                        . '</div>';
                }

                $body .= '<p>El registro fue <strong style="color:#22c55e;">silenciosamente bloqueado</strong>. '
                    . 'El usuario vio un mensaje de éxito falso. Nada fue guardado en la base de datos.</p>';

                Mail::to($recipient)->send(
                    new GxStyledMail(
                        subject: '🚨 Alerta Anti-Abuso: ' . $reasonLabel,
                        heading: 'Alerta de Abuso Detectada',
                        body: $body,
                    )
                );
            } catch (\Throwable $e) {
                Log::error('[DeviceGuard] Failed to send abuse alert email: ' . $e->getMessage());
            }
        });
    }

    private function getExifData(Request $request): array
    {
        $default = ['model' => 'Desconocido', 'gps' => 'No disponible'];

        if (!function_exists('exif_read_data') || !$request->hasFile('proof')) {
            return $default;
        }

        try {
            $file = $request->file('proof');
            $path = $file->getRealPath();

            // Only JPEG/TIFF usually have EXIF
            $mime = $file->getMimeType();
            if (!in_array($mime, ['image/jpeg', 'image/jpg', 'image/tiff'])) {
                return $default;
            }

            $exif = @exif_read_data($path, 'ANY_TAG', true);
            if (!$exif) {
                return $default;
            }

            $model = $exif['IFD0']['Model'] ?? $exif['ID0']['Model'] ?? 'Desconocido';
            $gpsLink = 'No disponible';

            if (isset($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef'], $exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef'])) {
                $lat = $this->convertExifToDecimal($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef']);
                $lng = $this->convertExifToDecimal($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef']);
                $gpsLink = "https://www.google.com/maps?q={$lat},{$lng}";
            }

            return [
                'model' => $model,
                'gps'   => $gpsLink,
            ];
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function convertExifToDecimal(array $coord, string $ref): float
    {
        $degrees = $this->parseExifRational($coord[0]);
        $minutes = $this->parseExifRational($coord[1]);
        $seconds = $this->parseExifRational($coord[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if ($ref === 'S' || $ref === 'W') {
            $decimal *= -1;
        }

        return (float) $decimal;
    }

    private function parseExifRational(string $rational): float
    {
        $parts = explode('/', $rational);
        if (count($parts) === 1) {
            return (float) $parts[0];
        }
        if (count($parts) === 2 && (float) $parts[1] !== 0.0) {
            return (float) $parts[0] / (float) $parts[1];
        }
        return 0.0;
    }
}
