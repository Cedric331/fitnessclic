<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre séance d'entraînement</title>
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
        .greeting-name {
            color: #667eea;
            font-weight: 700;
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
                    <h1 class="header-title">Votre séance d'entraînement</h1>
                    <p class="header-subtitle">Prête à être consultée</p>
                </div>

                <!-- Contenu principal -->
                <div class="content">
                    <p class="content-text">
                        Bonjour <strong class="greeting-name">{{ $customerName }}</strong>,
                    </p>

                    <p class="content-text">
                        Votre coach <strong>{{ $coachName }}</strong> vous a préparé une nouvelle séance d'entraînement.
                    </p>

                    <p class="content-text">
                        Vous trouverez en pièce jointe le PDF détaillé de votre séance avec tous les exercices, séries et répétitions à effectuer.
                    </p>

                    <div class="cta-section">
                        <a href="{{ $publicUrl }}" class="cta-button">Consulter la séance en ligne</a>
                    </div>

                    <p style="font-size: 12px; color: #6b7280; text-align: center;">
                        Vous pouvez également consulter cette séance directement sur le site web en cliquant sur le bouton ci-dessus.
                    </p>

                    <p class="content-text" style="margin-top: 25px;">
                        Bonne séance ! 💪
                    </p>
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
