<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre offre FitnessClic</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td {font-family: Arial, sans-serif !important;}
    </style>
    <![endif]-->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background-color: #f3f4f6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }
        .header-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px 30px;
            text-align: center;
        }
        .logo-container {
            margin-bottom: 20px;
        }
        .logo {
            max-width: 180px;
            height: auto;
            display: inline-block;
        }
        .header-title {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header-subtitle {
            color: rgba(255, 255, 255, 0.95);
            font-size: 16px;
            margin-top: 8px;
            font-weight: 400;
        }
        .content {
            padding: 40px 35px;
        }
        .content-text {
            font-size: 16px;
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 20px;
        }
        .content-card {
            background-color: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .promo {
            margin: 25px 0;
            padding: 18px 24px;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 2px solid #10b981;
            border-radius: 10px;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            color: #047857;
        }
        .cta-section {
            text-align: center;
            margin: 35px 0 25px;
        }
        .cta-button {
            display: inline-block;
            padding: 16px 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
            transform: translateY(-2px);
        }
        .footer {
            background-color: #f9fafb;
            padding: 25px 35px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer-text {
            font-size: 12px;
            color: #9ca3af;
            margin: 8px 0;
        }
        .footer-logo {
            margin-top: 15px;
        }
        .footer-logo img {
            max-width: 120px;
            height: auto;
            opacity: 0.7;
        }
        .preheader {
            display: none;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            color: transparent;
        }
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                width: 100% !important;
            }
            .header-gradient {
                padding: 30px 20px 25px;
            }
            .content {
                padding: 30px 25px;
            }
            .header-title {
                font-size: 24px;
            }
            .cta-button {
                padding: 14px 28px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="preheader">Votre offre FitnessClic et votre code promo sont prêts.</div>
    <div style="padding: 20px 0; background-color: #f3f4f6;">
        <div class="email-wrapper">
            <div class="email-container">
                <!-- Header avec gradient et logo -->
                <div class="header-gradient">
                    <div class="logo-container">
                        <img src="{{ $logoUrl }}" alt="FitnessClic" class="logo">
                    </div>
                    <h1 class="header-title">{{ $title }}</h1>
                    <p class="header-subtitle">Votre offre personnalisée est prête</p>
                </div>

                <!-- Contenu principal -->
                <div class="content">
                    <div class="content-card">
                        {!! $content !!}
                    </div>

                    @if(!empty($promoCode))
                        <div class="promo">
                            Code promo : {{ $promoCode }}
                        </div>
                    @endif

                    <div class="cta-section">
                        <a href="{{ $registerUrl }}" class="cta-button">Créer mon compte</a>
                    </div>
                    <p style="text-align: center; font-size: 12px; color: #6b7280;">
                        Valable pour une durée limitée. Si vous avez des questions, répondez simplement à cet email.
                    </p>
                </div>

                <!-- Footer -->
                <div class="footer">
                    <div class="footer-logo">
                        <img src="{{ $logoUrl }}" alt="FitnessClic">
                    </div>
                    <p class="footer-text">
                        Ce message vous a été envoyé par FitnessClic<br>
                        © {{ date('Y') }} FitnessClic. Tous droits réservés.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
