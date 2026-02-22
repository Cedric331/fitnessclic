<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Équipe supprimée</title>
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
        .team-name {
            color: #667eea;
            font-weight: 700;
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
                    <h1 class="header-title">Équipe supprimée</h1>
                    <p class="header-subtitle">Notification importante</p>
                </div>

                <!-- Contenu principal -->
                <div class="content">
                    <p class="content-text">Bonjour,</p>
                    <p class="content-text">
                        L'équipe <strong class="team-name">{{ $teamName }}</strong> a été supprimée et n'est plus disponible.
                    </p>
                    <p class="content-text">
                        Vous pouvez créer ou rejoindre une nouvelle équipe depuis votre espace.
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
