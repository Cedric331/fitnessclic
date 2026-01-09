<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'light') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "light" }}';

                // Remove dark class first to ensure clean state
                document.documentElement.classList.remove('dark');

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                } else if (appearance === 'dark') {
                    document.documentElement.classList.add('dark');
                }
                // If appearance is 'light', dark class is already removed above
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @php
            // Détecter si c'est la page d'accueil (Welcome)
            $isWelcomePage = false;
            if (isset($page['component']) && $page['component'] === 'Welcome') {
                $isWelcomePage = true;
            } elseif (request()->route() && request()->route()->getName() === 'home') {
                $isWelcomePage = true;
            } elseif (request()->is('/')) {
                $isWelcomePage = true;
            }
            
            $siteName = 'FitnessClic';
            $siteUrl = 'https://fitnessclic.com';
            $currentUrl = request()->url();
            // S'assurer que l'URL utilise HTTPS
            $currentUrl = str_replace('http://', 'https://', $currentUrl);
            $title = 'FitnessClic - Logiciel pour coachs sportifs, créer vos programmes rapidement';
            $description = 'L\'outil professionnel pour les coachs sportifs et particuliers. Créez, organisez et partagez vos programmes d\'entraînement facilement. Bibliothèque d\'exercices, gestion de clients, export PDF. Compte gratuit disponible.';
            $keywords = 'coach sportif, séance d\'entraînement, programme fitness, création séance, gestion clients, bibliothèque exercices, fitness, sport, entraînement personnalisé';
            $imageUrl = $siteUrl . '/assets/logo_fitnessclic.png';
            $twitterHandle = '@FitnessClic';
        @endphp

        @if($isWelcomePage)
            {{-- Meta Tags de base pour SEO --}}
            <meta name="description" content="{{ $description }}" />
            <meta name="keywords" content="{{ $keywords }}" />
            <meta name="author" content="{{ $siteName }}" />
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
            <meta name="language" content="French" />
            <meta name="revisit-after" content="7 days" />
            <link rel="canonical" href="{{ $currentUrl }}" />

            {{-- Open Graph / Facebook --}}
            <meta property="og:type" content="website" />
            <meta property="og:url" content="{{ $currentUrl }}" />
            <meta property="og:title" content="{{ $title }}" />
            <meta property="og:description" content="{{ $description }}" />
            <meta property="og:image" content="{{ $imageUrl }}" />
            <meta property="og:image:secure_url" content="{{ $imageUrl }}" />
            <meta property="og:image:type" content="image/png" />
            <meta property="og:image:width" content="1200" />
            <meta property="og:image:height" content="630" />
            <meta property="og:image:alt" content="{{ $siteName }} - {{ $description }}" />
            <meta property="og:site_name" content="{{ $siteName }}" />
            <meta property="og:locale" content="fr_FR" />

            {{-- Twitter Card --}}
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:url" content="{{ $currentUrl }}" />
            <meta name="twitter:title" content="{{ $title }}" />
            <meta name="twitter:description" content="{{ $description }}" />
            <meta name="twitter:image" content="{{ $imageUrl }}" />
            <meta name="twitter:image:alt" content="{{ $siteName }} - {{ $description }}" />
            <meta name="twitter:creator" content="{{ $twitterHandle }}" />
            <meta name="twitter:site" content="{{ $twitterHandle }}" />
        @endif

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
