<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0a0a0a;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #e0e0e0;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background: #111111;
            border: 1px solid #222;
            border-radius: 12px;
            overflow: hidden;
        }
        .email-header {
            background: linear-gradient(135deg, #1a0000, #0a0a0a);
            padding: 30px 20px;
            text-align: center;
            border-bottom: 2px solid #E10600;
        }
        .email-header img {
            width: 60px;
            height: auto;
            margin-bottom: 10px;
        }
        .email-header h1 {
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .email-header .brand-red {
            color: #E10600;
        }
        .email-body {
            padding: 30px 25px;
        }
        .email-body h2 {
            color: #ffffff;
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0 0 16px;
        }
        .email-body p {
            color: #b0b0b0;
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 0 0 14px;
        }
        .email-body ul,
        .email-body ol {
            margin: 0 0 14px;
            padding-left: 18px;
            color: #b0b0b0 !important;
        }
        .email-body li {
            color: #b0b0b0 !important;
            line-height: 1.6;
            margin-bottom: 6px;
        }
        .email-body strong {
            color: #f3f4f6 !important;
        }
        .email-body a {
            color: #E10600 !important;
        }
        .cta-button {
            display: inline-block;
            background: #E10600;
            color: #ffffff !important;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(225, 6, 0, 0.3);
        }
        .email-footer {
            background: #0a0a0a;
            padding: 20px 25px;
            text-align: center;
            border-top: 1px solid #222;
        }
        .email-footer p {
            color: #666;
            font-size: 0.75rem;
            margin: 0;
        }
        .highlight-box {
            background: rgba(225, 6, 0, 0.08);
            border: 1px solid rgba(225, 6, 0, 0.2);
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }
        .highlight-box p,
        .highlight-box ul,
        .highlight-box ol,
        .highlight-box li {
            color: #d1d5db !important;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('img/logo.png');
        $logoEmbedded = null;

        if (isset($message) && file_exists($logoPath)) {
            try {
                $logoEmbedded = $message->embed($logoPath);
            } catch (\Throwable $e) {
                $logoEmbedded = null;
            }
        }

        $logoSrc = $logoEmbedded ?: rtrim(config('app.url'), '/') . '/img/logo.png';
    @endphp

    <div class="email-wrapper">
        <div class="email-header">
            <img src="{{ $logoSrc }}" alt="GX" />
            <h1><span class="brand-red">GUERRILLA</span> XTREM</h1>
        </div>

        <div class="email-body">
            <h2>{{ $heading }}</h2>
            {!! $body !!}

            @if($ctaText && $ctaUrl)
                <div style="text-align: center; margin-top: 24px;">
                    <a href="{{ $ctaUrl }}" class="cta-button">{{ $ctaText }}</a>
                </div>
            @endif
        </div>

        <div class="email-footer">
            <p>&copy; {{ date('Y') }} Guerrilla Xtrem. Todos los derechos reservados.</p>
            <p style="margin-top: 6px;">@guerrilla_xtrem</p>
        </div>
    </div>
</body>
</html>
