<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre séance d'entraînement</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
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
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .header h1 {
            color: #111827;
            font-size: 24px;
            margin: 0 0 10px 0;
        }
        .content {
            margin-bottom: 30px;
        }
        .content p {
            margin: 15px 0;
            color: #4b5563;
        }
        .session-info {
            background-color: #f9fafb;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .session-info h2 {
            color: #111827;
            font-size: 18px;
            margin: 0 0 10px 0;
        }
        .session-info p {
            margin: 5px 0;
            color: #6b7280;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 14px;
            color: #9ca3af;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3b82f6;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .notes {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .notes p {
            margin: 0;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Votre séance d'entraînement</h1>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $customerName }}</strong>,</p>

            <p>Votre coach <strong>{{ $coachName }}</strong> vous a préparé une nouvelle séance d'entraînement.</p>

            <p>Vous trouverez en pièce jointe le PDF détaillé de votre séance avec tous les exercices, séries et répétitions à effectuer.</p>

            <p style="text-align: center; margin: 15px 0;">
                <a href="{{ $publicUrl }}" class="button" style="display: inline-block; padding: 6px 12px; background-color:rgb(7, 39, 90); color: #ffffff; text-decoration: none; border-radius: 6px;">
                    Consulter la séance en ligne
                </a>
            </p>

            <p style="font-size: 12px; color: #6b7280; text-align: center;">
                Vous pouvez également consulter cette séance directement sur le site web en cliquant sur le bouton ci-dessus.
            </p>

            <p>Bonne séance ! 💪</p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé depuis FitnessClic</p>
            <p>© {{ date('Y') }} FitnessClic. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>

