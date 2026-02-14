<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre offre FitnessClic</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 28px 24px 20px;
            background: linear-gradient(135deg, #10b981, #06b6d4);
            color: #ffffff;
        }
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            margin: 0 0 6px;
        }
        .header p {
            margin: 0;
            color: rgba(255, 255, 255, 0.85);
            font-size: 14px;
        }
        .content {
            padding: 24px 28px 10px;
        }
        .content p {
            margin: 12px 0;
            color: #4b5563;
        }
        .content h2 {
            margin: 18px 0 8px;
            font-size: 18px;
            color: #111827;
        }
        .content ul {
            padding-left: 18px;
            margin: 12px 0;
        }
        .content li {
            margin: 6px 0;
        }
        .promo {
            margin: 18px 0 10px;
            padding: 14px 18px;
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            color: #047857;
        }
        .content-card {
            background-color: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px 18px;
            margin: 12px 0 18px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #10b981;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 18px 0;
            font-weight: 600;
        }
        .cta {
            text-align: center;
            padding: 4px 0 18px;
        }
        .footer {
            margin-top: 24px;
            padding: 16px 24px 24px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
        .preheader {
            display: none;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            color: transparent;
        }
    </style>
</head>
<body>
    <div class="preheader">Votre offre FitnessClic et votre code promo sont prêts.</div>
    <div class="email-container">
        <div class="header">
            <h1>{{ $title }}</h1>
            <p>Votre offre personnalisée est prête</p>
        </div>

        <div class="content">
            <div class="content-card">
                {!! $content !!}
            </div>

            @if(!empty($promoCode))
                <div class="promo">
                    Code promo : {{ $promoCode }}
                </div>
            @endif

            <div class="cta">
                <a href="{{ $registerUrl }}" class="button">Créer mon compte</a>
            </div>
            <p style="text-align: center; font-size: 12px; color: #6b7280;">
                Valable pour une durée limitée. Si vous avez des questions, répondez simplement à cet email.
            </p>
        </div>

        <div class="footer">
            <p>Ce message vous a été envoyé par FitnessClic</p>
            <p>© {{ date('Y') }} FitnessClic. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>

