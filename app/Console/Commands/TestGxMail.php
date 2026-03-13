<?php

namespace App\Console\Commands;

use App\Mail\GxStyledMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestGxMail extends Command
{
    protected $signature = 'gx:mail-test {email : Correo destino para pruebas}';
    protected $description = 'Envía correos de prueba GX (pendiente, confirmado, rechazado y reset password).';

    public function handle(): int
    {
        $to = trim((string) $this->argument('email'));

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Correo inválido.');
            return self::FAILURE;
        }

        $this->info("Enviando correos de prueba a {$to}...");

        $testName = \App\Models\User::where('email', $to)->value('blader_name')
            ?: \App\Models\User::where('email', $to)->value('name')
            ?: ucfirst(str_replace(['.', '_', '-'], ' ', strstr($to, '@', true) ?: 'Blader'));

        $templates = [
            [
                'subject' => 'GX TEST - Preinscripción recibida',
                'heading' => 'Hemos recibido tu preinscripción',
                'body' => '<p>Hola <strong>' . htmlspecialchars($testName) . '</strong>,</p><p>Tu preinscripción quedó en estado <strong>pendiente de validación</strong>.</p>',
            ],
            [
                'subject' => 'GX TEST - Preinscripción confirmada',
                'heading' => 'Inscripción validada',
                'body' => '<p>Hola <strong>' . htmlspecialchars($testName) . '</strong>,</p><p>Tu pago fue validado y tu cupo ya está <strong>confirmado</strong>.</p>',
            ],
            [
                'subject' => 'GX TEST - Preinscripción rechazada',
                'heading' => 'Estado de tu preinscripción',
                'body' => '<p>Hola <strong>' . htmlspecialchars($testName) . '</strong>,</p><p>Tu preinscripción fue <strong>rechazada</strong>. Revisa el motivo con el staff.</p>',
            ],
            [
                'subject' => 'GX TEST - Recuperación de contraseña',
                'heading' => 'Recuperación de contraseña',
                'body' => '<p>Hola <strong>' . htmlspecialchars($testName) . '</strong>,</p><p>Este es un test de plantilla para restablecer contraseña.</p>',
                'ctaText' => 'Restablecer contraseña',
                'ctaUrl' => config('app.url') . '/reset-password/test-token?email=' . urlencode($to),
            ],
        ];

        foreach ($templates as $template) {
            try {
                Mail::to($to)->send(new GxStyledMail(
                    subject: $template['subject'],
                    heading: $template['heading'],
                    body: $template['body'],
                    ctaText: $template['ctaText'] ?? null,
                    ctaUrl: $template['ctaUrl'] ?? null,
                ));

                $this->line("  OK: {$template['subject']}");
            } catch (\Throwable $e) {
                $this->error("  ERROR: {$template['subject']}");
                $this->error('  ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        $this->info('Pruebas de correo enviadas correctamente.');
        return self::SUCCESS;
    }
}
