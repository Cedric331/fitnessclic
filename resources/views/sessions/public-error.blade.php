<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $title ?? 'Erreur' }} - FitnessClic</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    html, body {
      height: 100%;
      width: 100%;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      font-size: 16px;
      color: #212121;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }

    .error-container {
      background-color: #ffffff;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      padding: 40px;
      max-width: 500px;
      width: 100%;
      text-align: center;
    }

    .error-icon {
      width: 80px;
      height: 80px;
      margin: 0 auto 20px;
      background-color: #fee2e2;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 40px;
      color: #dc2626;
    }

    .error-title {
      font-size: 24px;
      font-weight: bold;
      color: #1f2937;
      margin-bottom: 16px;
    }

    .error-message {
      font-size: 16px;
      color: #6b7280;
      line-height: 1.6;
      margin-bottom: 24px;
    }

    .error-footer {
      font-size: 14px;
      color: #9ca3af;
      margin-top: 24px;
      padding-top: 24px;
      border-top: 1px solid #e5e7eb;
    }

    .error-footer a {
      color: #667eea;
      text-decoration: none;
    }

    .error-footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="error-container">
    <div class="error-icon">
      ⚠️
    </div>
    <h1 class="error-title">{{ $title ?? 'Erreur' }}</h1>
    <p class="error-message">{{ $message ?? 'Une erreur est survenue.' }}</p>
    <div class="error-footer">
      <p>Si vous pensez qu'il s'agit d'une erreur, veuillez contacter le créateur de la séance.</p>
      <p style="margin-top: 12px;">
        <a href="{{ url('/') }}">Retour à l'accueil</a>
      </p>
    </div>
  </div>
</body>
</html>

