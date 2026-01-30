<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Équipe supprimée</title>
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
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e5e7eb;
        }
        .header h1 {
            color: #111827;
            font-size: 22px;
            margin: 0;
        }
        .content p {
            margin: 12px 0;
            color: #4b5563;
        }
        .footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Équipe supprimée</h1>
        </div>

        <div class="content">
            <p>Bonjour,</p>
            <p>L’équipe <strong>{{ $teamName }}</strong> a été supprimée et n’est plus disponible.</p>
            <p>Vous pouvez créer ou rejoindre une nouvelle équipe depuis votre espace.</p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé depuis FitnessClic</p>
        </div>
    </div>
</body>
</html>

