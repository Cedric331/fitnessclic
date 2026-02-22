<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur FitnessClic</title>
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
        .greeting {
            font-size: 18px;
            color: #111827;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .greeting-name {
            color: #667eea;
            font-weight: 700;
        }
        .content-text {
            font-size: 16px;
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 20px;
        }
        .features-grid {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
            border: 1px solid #e0e7ff;
        }
        .features-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 18px;
            text-align: center;
        }
        .features-list {
            list-style: none;
            padding: 0;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 14px;
            font-size: 15px;
            color: #4b5563;
        }
        .feature-icon {
            color: #667eea;
            font-size: 18px;
            margin-right: 12px;
            margin-top: 2px;
            flex-shrink: 0;
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
        .signature {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }
        .signature-text {
            font-size: 15px;
            color: #4b5563;
            margin-bottom: 8px;
        }
        .signature-name {
            font-size: 16px;
            color: #111827;
            font-weight: 600;
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
            margin: 0;
        }
        .footer-logo {
            margin-top: 15px;
        }
        .footer-logo img {
            max-width: 120px;
            height: auto;
            opacity: 0.7;
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
    <div style="padding: 20px 0; background-color: #f3f4f6;">
        <div class="email-wrapper">
            <div class="email-container">
                <!-- Header avec gradient et logo -->
                <div class="header-gradient">
                    <div class="logo-container">
                        <img src="{{ $logoUrl }}" alt="FitnessClic" class="logo">
                    </div>
                    <h1 class="header-title">Bienvenue !</h1>
                    <p class="header-subtitle">Votre aventure commence maintenant</p>
                </div>

                <!-- Contenu principal -->
                <div class="content">
                    <p class="greeting">
                        Bonjour <span class="greeting-name">{{ $userName }}</span>,
                    </p>
                    
                    <p class="content-text">
                        Merci pour votre inscription sur <strong>FitnessClic</strong> ! Nous sommes ravis de vous accueillir dans notre communauté de coachs sportifs passionnés.
                    </p>
                    
                    <p class="content-text">
                        Vous disposez maintenant d'un outil puissant pour créer, gérer et partager vos séances d'entraînement personnalisées.
                    </p>

                    <p class="content-text">
                        Si vous avez des questions ou besoin d'aide, notre équipe est là pour vous accompagner. N'hésitez pas à nous contacter !
                    </p>

                    <!-- Bouton CTA -->
                    <div class="cta-section">
                        <a href="{{ $loginUrl }}" class="cta-button">Accéder à mon compte</a>
                    </div>

                    <!-- Signature -->
                    <div class="signature">
                        <p class="signature-text">À très bientôt,</p>
                        <p class="signature-name">L'équipe FitnessClic</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer">
                    <div class="footer-logo">
                        <img src="{{ $logoUrl }}" alt="FitnessClic">
                    </div>
                    <p class="footer-text">
                        Cet email a été envoyé depuis FitnessClic<br>
                        © {{ date('Y') }} FitnessClic. Tous droits réservés.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
