<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $excerpt }}">
    <link rel="canonical" href="{{ $url }}">

    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $excerpt }}">
    <meta property="og:image" content="{{ $imageUrl }}">
    <meta property="og:image:secure_url" content="{{ $imageUrl }}">
    <meta property="og:site_name" content="FitnessClic">
    <meta property="og:locale" content="fr_FR">
    @if(!empty($publishedAt))
        <meta property="article:published_time" content="{{ $publishedAt }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $excerpt }}">
    <meta name="twitter:image" content="{{ $imageUrl }}">
    <meta name="twitter:site" content="@FitnessClic">
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>{{ $excerpt }}</p>
</body>
</html>

