<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirection vers Stripe...</title>
    <script>
        // Redirection immédiate au niveau supérieur pour éviter les iframes
        (function() {
            try {
                // Essayer d'abord au niveau supérieur (si dans un iframe)
                if (window.top !== window.self) {
                    window.top.location.href = '{{ $url }}';
                } else {
                    // Sinon, redirection normale
                    window.location.href = '{{ $url }}';
                }
            } catch (e) {
                // En cas d'erreur (CORS), forcer la redirection
                window.location.href = '{{ $url }}';
            }
        })();
    </script>
    <meta http-equiv="refresh" content="0;url={{ $url }}">
</head>
<body>
    <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
        <h2>Redirection vers Stripe Checkout...</h2>
        <p>Si vous n'êtes pas redirigé automatiquement dans quelques secondes, <a href="{{ $url }}" style="color: #0066cc;">cliquez ici</a>.</p>
    </div>
</body>
</html>

