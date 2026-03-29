<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Mail\GxStyledMail;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Anti-abuse middleware for tournament registration.
 */
class DeviceGuard
{
    private const BLOCKED_PATTERNS = [
        'mamon', 'mamones', 'losmeo', 'los meo', 'wekito', 'wekos', 'weko', 'wekit', 'guerrillero', 'guerrillera', 'guerrillerx',
        'guerrilleroswekos', 'guerrillerospobre', 'guerrilleroscl', 'gxwekos', 'gxbasura', 'gxmierda',
        'ctm', 'conchetumare', 'conchetumadre', 'conchaetumare', 'conchaetumadre', 'conchesumadre', 'conchesumare',
        'chuchetumare', 'chuchatumare', 'reconchetumare', 're-conchetumare', 'csm', 'ctmre',
        'maricon', 'maricón', 'marakita', 'maraca', 'maraco', 'puto', 'puta', 'mierda', 'cagada',
        'aweonao', 'aweona', 'weon', 'weón', 'wn', 'wna', 'wea', 'weas', 'ahuevonado', 'ahuevonao',
        'culiao', 'culiado', 'culiá', 'qliao', 'qlio', 'ql', 'qlo', 'reqliao', 're-culiao',
        'perkin', 'perkins', 'perkinazo', 'perkines', 'sacoewea', 'sacowea', 'sacodewea',
        'flaite', 'longi', 'gil', 'penco', 'penca', 'callampa', 'callampero',
        'pico', 'tula', 'pichula', 'corneta', 'chupa', 'chupalo', 'chupenlo', 'chupala', 'chupenla',
        'chupapico', 'chupatula', 'mamalo', 'picon', 'tulaql', 'perroql', 'perroqlo', 'sapoculiao', 'sapoql',
        'bastardo', 'zorra', 'perra', 'malnacido', 'hdp', 'hijodeputa', 'hijodeperra',
        'estafa', 'estafadores', 'robando', 'ladrones', 'rateros', 'sinverguenzas', 'basura', 'escoria',
        'pobre', 'pobretone', 'resentido', 'antisocial', 'delincuente', 'lacra', 'cancer', 'parasito',
        'mueranse', 'muerete', 'entierrense', 'asqueroso', 'asco', 'vomito', 'fome', 'webon', 'huevon',
    ];

    private const BLOCKED_EMAIL_DOMAINS = [
        'wekos.cl', 'guerrilleroswekos.cl', 'losmeo.cl', 'mamon.cl', 'fake.cl', 'troll.cl', 'spam.cl', 
        'basura.cl', 'mierda.cl', 'putas.cl', 'guerrilleras.cl', 'maricones.cl', 'losmeos.cl', 
        'sacoweas.cl', 'conchesumadres.cl',
        'troll.com', 'fake.com', 'trash.com', 'temp-mail.org', '10minutemail.com', 'guerrillamail.com', 
        'mailinator.com', 'dispostable.com', 'getnada.com', 'yopmail.com', 'sharklasers.com',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->failsHoneypot($request)) {
            Log::warning('[DeviceGuard] Honeypot triggered', ['ip' => $this->resolveIp($request)]);
            return $this->shadowBanResponse($request);
        }

        $rateLimitKey = 'reg_attempt:' . $this->resolveIp($request);
        $maxAttempts = (int) config('device_fingerprint.rate_limit_max', 5);
        $decayMinutes = (int) config('device_fingerprint.rate_limit_decay_minutes', 10);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            Log::warning('[DeviceGuard] Rate limit exceeded', ['ip' => $this->resolveIp($request)]);
            return $this->shadowBanResponse($request);
        }

        RateLimiter::hit($rateLimitKey, $decayMinutes * 60);

        $deviceId = $request->input('device_id', '');
        $blockedIds = config('device_fingerprint.blocked_ids', []);

        if (!empty($deviceId) && in_array($deviceId, $blockedIds, true)) {
            $telemetry = $this->extractTelemetry($request);
            Log::warning('[DeviceGuard] BLOCKED DEVICE detected', $telemetry);
            AuditLogger::log('blocked_device', 'TournamentRegistration', null, $telemetry);
            $this->sendAbuseAlert($request, $telemetry, 'device_blocked');
            return $this->shadowBanResponse($request, $telemetry);
        }

        $profanityResult = $this->detectProfanity($request);
        if ($profanityResult !== null) {
            $telemetry = $this->extractTelemetry($request);
            $telemetry['profanity_match'] = $profanityResult['match'];
            $telemetry['profanity_field'] = $profanityResult['field'];
            
            Log::info('[DeviceGuard] PROFANITY DETECTED - Shadow Banning', $telemetry);
            AuditLogger::log('blocked_profanity', 'TournamentRegistration', null, $telemetry);
            $this->sendAbuseAlert($request, $telemetry, 'profanity_detected');
            
            return $this->shadowBanResponse($request, $telemetry);
        }

        Log::debug('[DeviceGuard] Request passed checks.', ['url' => $request->fullUrl()]);
        return $next($request);
    }

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
            if (empty($fieldValue)) continue;
            $normalized = $this->normalizeForCheck((string) $fieldValue);
            foreach (self::BLOCKED_PATTERNS as $pattern) {
                $normalizedPattern = $this->normalizeForCheck($pattern);
                if ($normalizedPattern !== '' && str_contains($normalized, $normalizedPattern)) {
                    return ['field' => $fieldName, 'match' => $pattern];
                }
            }
            if ($fieldName === 'email') {
                $domain = strtolower(trim(substr(strrchr($fieldValue, '@') ?: '', 1)));
                foreach (self::BLOCKED_EMAIL_DOMAINS as $blockedDomain) {
                    if ($domain === $blockedDomain || str_ends_with($domain, '.' . $blockedDomain)) {
                        return ['field' => 'email_domain', 'match' => $blockedDomain];
                    }
                }
            }
        }
        return null;
    }

    private function normalizeForCheck(string $value): string
    {
        $noAccents = strtr(mb_strtolower($value), ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        return preg_replace('/[^a-z0-9@.]/', '', $noAccents) ?? $noAccents;
    }

    private function resolveIp(Request $request): string
    {
        foreach (['CF-Connecting-IP', 'X-Forwarded-For', 'X-Real-IP'] as $header) {
            if ($ip = $request->header($header)) return explode(',', $ip)[0];
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
            'ip_geo'      => $this->getIpCoordinates($this->resolveIp($request)),
        ];
    }

    private function shadowBanResponse(Request $request, array $telemetry = []): Response
    {
        if ($request->header('X-Inertia')) {
            $ip = htmlspecialchars($telemetry['ip'] ?? $this->resolveIp($request), ENT_QUOTES, 'UTF-8');
            $ua = htmlspecialchars($telemetry['user_agent'] ?? $request->userAgent() ?? '?', ENT_QUOTES, 'UTF-8');
            $screen = htmlspecialchars((string) ($telemetry['screen'] ?? ''), ENT_QUOTES, 'UTF-8');
            $memory = htmlspecialchars((string) ($telemetry['memory'] ?? ''), ENT_QUOTES, 'UTF-8');
            $cookies = (string) ($telemetry['cookies'] ?? $request->input('d_cookies', ''));
            $exif = $telemetry['exif'] ?? [];
            $ipGeo = $telemetry['ip_geo'] ?? $this->getIpCoordinates($telemetry['ip'] ?? $this->resolveIp($request));

            $lines = ["<b>Tu navegador es:</b> $ua"];
            $lines[] = "<b>Tu IP de Origen:</b> <span style='color:#e10600'>$ip</span>";
            
            if ($screen && $screen !== 'unknown') $lines[] = "<b>Tu resolución es:</b> $screen";
            if ($memory && $memory !== 'unknown') $lines[] = "<b>Tu memoria es:</b> {$memory} GB";
            
            if (isset($ipGeo['lat'], $ipGeo['lng'])) {
                $lines[] = "<b>¿Tu ubicación?</b> -{$ipGeo['lat']} -{$ipGeo['lng']}, lo más probable es que sea en tu triste e infeliz casa.";
            }

            if ($exif) {
                if (!empty($exif['model']) && $exif['model'] !== 'Desconocido') {
                    $lines[] = "<b>Modelo de Teléfono:</b> {$exif['model']}";
                }
                if (!empty($exif['gps']) && $exif['gps'] !== 'No disponible') {
                    $lines[] = "<b>Coordenadas GPS:</b> {$exif['gps']}";
                }
            } else {
                // If no EXIF but proof is uploaded, try a mock deduction based on UA
                if ($request->hasFile('proof')) {
                    $mockModel = 'Dispositivo Móvil';
                    if (str_contains(strtolower($ua), 'iphone')) $mockModel = 'Apple iPhone (iOS)';
                    if (str_contains(strtolower($ua), 'samsung')) $mockModel = 'Samsung Galaxy (Android)';
                    if (str_contains(strtolower($ua), 'pixel')) $mockModel = 'Google Pixel (Android)';
                    if (str_contains(strtolower($ua), 'windows')) $mockModel = 'Computadora Windows';
                    $lines[] = "<b>Dispositivo Identificado:</b> $mockModel";
                }
            }

            $cookieHtml = "<div style='font-family:monospace; font-size:10px; padding:10px; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.1); max-height:100px; overflow:auto; word-break:break-all; margin-top:10px; border-radius:8px;'>$cookies</div>";

            $trollMessage = implode('<br>', $lines) . "<br><br>"
                . "<b>Tus cookies son:</b>"
                . $cookieHtml . "<br>"
                . "<b>Ten más hombria y cuidado donde rellenas un formulario la próxima vez, gracias por entregarme el acceso a tu navegador, sistema y datos. 😈</b>";

            Log::info('[DeviceGuard] Shadow ban response rendered for IP: ' . $ip);

            return back()->with([
                'troll_message'  => $trollMessage,
                '_shadow_banned' => true,
            ]);
        }

        return response()->json(['message' => 'Registro recibido correctamente.'], 200);
    }

    private function sendAbuseAlert(Request $request, array $telemetry, string $reason): void
    {
        $recipient = config('device_fingerprint.admin_alert_email');
        if (empty($recipient)) return;

        app()->terminating(function () use ($recipient, $telemetry, $reason) {
            try {
                $reasonLabel = match ($reason) {
                    'device_blocked' => '🔒 Dispositivo bloqueado',
                    'profanity_detected' => '🤬 Profanidad detectada',
                    default => '⚠️ Abuso',
                };

                $body = "<div style='color: #ffffff; font-family: sans-serif;'>"
                    . "<h2 style='color: #e10600; border-bottom: 1px solid #333; padding-bottom: 5px;'>$reasonLabel</h2>"
                    . "<p><b>ID Registrado:</b> {$telemetry['device_id']}</p>"
                    . "<p><b>IP de Origen:</b> {$telemetry['ip']}</p>"
                    . "<p><b>Resolución:</b> {$telemetry['screen']}</p>"
                    . "<p><b>Navegador:</b> {$telemetry['user_agent']}</p>"
                    . "<p><b>Modelo de Teléfono:</b> " . ($telemetry['exif']['model'] ?? 'N/A') . "</p>"
                    . "<p><b>Coordenadas GPS:</b> " . ($telemetry['exif']['gps'] ?? 'N/A') . "</p>"
                    . "<p><b>Ubicación IP (lat,lng):</b> " . $this->formatLatLng($telemetry['ip_geo'] ?? []) . "</p>"
                    . "</div>";

                Mail::to($recipient)->send(new GxStyledMail('🚨 Alerta Anti-Abuso', 'Alerta de Abuso', $body));
            } catch (\Throwable $e) { Log::error('[DeviceGuard] Alert fail: ' . $e->getMessage()); }
        });
    }

    private function getExifData(Request $request): array
    {
        if (!function_exists('exif_read_data') || !$request->hasFile('proof')) return [];
        try {
            $file = $request->file('proof');
            if (!in_array($file->getMimeType(), ['image/jpeg', 'image/jpg', 'image/tiff'])) return [];
            $exif = @exif_read_data($file->getRealPath(), 'ANY_TAG', true);
            if (!$exif) return [];

            $data = ['model' => $exif['IFD0']['Model'] ?? $exif['ID0']['Model'] ?? ''];
            if (isset($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef'])) {
                $lat = $this->convertExifToDecimal($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef']);
                $lng = $this->convertExifToDecimal($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef']);
                $data['gps'] = $lat . ', ' . $lng;
            }
            return $data;
        } catch (\Throwable $e) { return []; }
    }

    private function getIpCoordinates(string $ip): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return [];
        }

        return Cache::remember("device_guard:ip_geo:{$ip}", now()->addHours(6), function () use ($ip) {
            try {
                $response = Http::timeout(2)->acceptJson()->get("https://ipapi.co/{$ip}/json/");
                if (!$response->ok()) {
                    return [];
                }

                $data = $response->json();
                $lat = $data['latitude'] ?? $data['lat'] ?? null;
                $lng = $data['longitude'] ?? $data['lon'] ?? $data['lng'] ?? null;

                if (!is_numeric($lat) || !is_numeric($lng)) {
                    return [];
                }

                return [
                    'lat' => round((float) $lat, 6),
                    'lng' => round((float) $lng, 6),
                ];
            } catch (\Throwable $e) {
                Log::debug('[DeviceGuard] IP geolocation lookup failed', [
                    'ip' => $ip,
                    'error' => $e->getMessage(),
                ]);
                return [];
            }
        });
    }

    private function formatLatLng(array $coords): string
    {
        if (!isset($coords['lat'], $coords['lng'])) {
            return 'No disponible';
        }

        return $coords['lat'] . ', ' . $coords['lng'];
    }

    private function convertExifToDecimal(array $coord, string $ref): float
    {
        $d = $this->parseExifRational($coord[0]);
        $m = $this->parseExifRational($coord[1]);
        $s = $this->parseExifRational($coord[2]);
        $dec = $d + ($m / 60) + ($s / 3600);
        return (float) ($ref === 'S' || $ref === 'W' ? -$dec : $dec);
    }

    private function parseExifRational(string $rational): float
    {
        $p = explode('/', $rational);
        return count($p) === 2 && (float)$p[1] !== 0.0 ? (float)$p[0] / (float)$p[1] : (float)$p[0];
    }
}
