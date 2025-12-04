<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Séance - {{ $session->name ?? 'Nouvelle Séance' }}</title>
    <style>
        @page {
            margin: 12mm 12mm 16mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            margin: 0;
            padding: 0 0 16mm 0;
            color: #111827;
        }

        /* HEADER -------------------------------------------------- */
        .pdf-header {
            margin-bottom: 5mm;
            padding-bottom: 4mm;
            border-bottom: 1px solid #e5e7eb;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 3mm;
        }

        .logo-container {
            flex-shrink: 0;
        }

        .logo-container img {
            height: 60px;
            width: auto;
            max-width: 100%;
        }

        .header-bottom {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8mm;
        }

        .header-notes {
            font-size: 7.5pt;
            color: #111827;
            flex: 1;
            line-height: 1.4;
        }

        .header-notes strong {
            display: inline;
            font-size: 7.5pt;
            font-weight: 700;
            color: #111827;
            margin-right: 2mm;
        }

        .header-info {
            text-align: right;
            font-size: 7.5pt;
            color: #4b5563;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .header-info > div {
            margin-bottom: 2px;
        }

        .header-info strong {
            font-weight: 600;
            color: #111827;
        }

        /* TITRE DE SEANCE ----------------------------------------- */
        .session-title {
            margin-bottom: 5mm;
        }

        .session-title h1 {
            font-size: 12pt;
            font-weight: 700;
            margin: 0 0 2mm 0;
            color: #111827;
        }

        .session-meta {
            font-size: 7.5pt;
            color: #6b7280;
        }

        /* EXERCICES ----------------------------------------------- */
        .exercises-section {
            margin-top: 3mm;
        }

        .exercise-item {
            page-break-inside: avoid;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 4mm;
            margin-bottom: 5mm;
            background: #f9fafb;
            overflow: hidden;
            position: relative;
        }

        .exercise-image-wrapper {
            width: 60px;
            border-radius: 4px;
            background: #f9fafb;
            padding: 3px;
            box-sizing: border-box;
            overflow: hidden;
            position: absolute;
            top: 4mm;
            left: 4mm;
            bottom: 4mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .exercise-image {
            display: block;
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
            margin: 0;
        }

        .exercise-header {
            margin-left: 70px; /* 60px image + 4mm margin + 6mm padding */
            overflow: hidden;
        }


        .exercise-content {
            overflow: hidden; /* force le texte à se placer à côté de l'image */
        }

        .exercise-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%; /* <- indispensable */
        }

        .exercise-title {
            margin: 0;
            padding: 0;
            font-size: 11pt;
            font-weight: 700;
            color: #111827;
        }


        .exercise-description {
            font-size: 7.5pt;
            color: #4b5563;
        }

        .exercise-description + .exercise-description {
            margin-top: 1mm;
            font-style: italic;
            color: #6b7280;
        }

        /* TABLE DES SERIES ---------------------------------------- */
        .sets-table {
            width: calc(100% - 70px);
            max-width: calc(100% - 70px);
            border-collapse: collapse;
            margin-top: 3mm;
            margin-left: 70px;
            font-size: 7pt;
        }

        .sets-table thead {
            background: #e5e7eb;
        }

        .sets-table th,
        .sets-table td {
            padding: 1.5mm 1mm;
            border: 1px solid #d1d5db;
            text-align: center;
            vertical-align: middle;
            font-size: 6.5pt;
        }

        .sets-table th {
            font-weight: 600;
            color: #374151;
        }

        .sets-table td {
            color: #111827;
        }

        .sets-table .set-index {
            font-weight: 600;
        }

        /* === FOOTER GLOBAL === */
        .pdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10mm;
            padding-top: 3mm;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
            text-align: center;
            color: #6b7280;
            font-size: 7pt;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .pdf-footer strong {
            color: #111827;
        }
    </style>
</head>
<body>
    @php
        $sessionExercises = $session->sessionExercises ?? collect();
        if (is_array($sessionExercises)) {
            $sessionExercises = collect($sessionExercises);
        }
        $exerciseCount = $sessionExercises->count();
    @endphp

    <!-- HEADER -->
    <div class="pdf-header">
        <div class="header-top">
            <div class="logo-container">
                @php
                    $logoPath = public_path('assets/logo_fitnessclic.png');
                    $logoUrl = file_exists($logoPath)
                        ? $logoPath
                        : asset('assets/logo_fitnessclic.png');
                @endphp
                <img src="{{ $logoUrl }}" alt="FitnessClic">
            </div>
        </div>

        <div class="header-bottom">
            @if($session->notes ?? null)
                <div class="header-notes">
                    <strong>Notes :</strong>{{ $session->notes }}
                </div>
            @else
                <div></div>
            @endif

            <div class="header-info">
                @if(isset($session->user))
                    <div><strong>Coach :</strong> {{ $session->user->name }}</div>
                @endif
                <div>
                    <strong>Date :</strong>
                    {{ $session->session_date ? \Carbon\Carbon::parse($session->session_date)->format('d/m/Y') : 'Non définie' }}
                </div>
            </div>
        </div>
    </div>

    <!-- TITRE SEANCE -->
    <div class="session-title">
        <h1>{{ $session->name ?? 'Nouvelle Séance' }}</h1>
        <div class="session-meta">
            {{ $exerciseCount }} exercice{{ $exerciseCount > 1 ? 's' : '' }}
        </div>
    </div>

    <!-- EXERCICES -->
    @if($exerciseCount > 0)
        <div class="exercises-section">
            @foreach($sessionExercises as $index => $sessionExercise)
                @php
                    $exercise = $sessionExercise->exercise ?? null;

                    $sets = $sessionExercise->sets ?? collect();
                    if (is_array($sets)) {
                        $sets = collect($sets);
                    }
                    $sets = $sets->sortBy('order');

                    $imagePath = null;
                    // Utiliser l'image optimisée si disponible (pour les emails)
                    if (isset($use_optimized_images) && $use_optimized_images && isset($exercise->optimized_image_path)) {
                        $imagePath = $exercise->optimized_image_path;
                    } elseif ($exercise && isset($exercise->media) && $exercise->media->count() > 0) {
                        $firstMedia = $exercise->media->first();
                        if ($firstMedia) {
                            try {
                                $imagePath = $firstMedia->getPath();
                            } catch (\Exception $e) {
                                $imagePath = storage_path('app/public/' . $firstMedia->id . '/' . $firstMedia->file_name);
                            }
                        }
                    } elseif ($exercise && method_exists($exercise, 'getFirstMediaPath')) {
                        $imagePath = $exercise->getFirstMediaPath('exercise_image');
                    }
                    if ($imagePath && ! file_exists($imagePath)) {
                        $imagePath = null;
                    }
                @endphp

                @if($exercise)
                    <div class="exercise-item">
                        @if($imagePath)
                            <div class="exercise-image-wrapper">
                                <img src="{{ $imagePath }}" alt="{{ $exercise->title }}" class="exercise-image">
                            </div>
                        @endif

                        <div class="exercise-header">
                            <div class="exercise-content">
                                <div class="exercise-title-row">
                                    <div class="exercise-title">
                                        {{ $index + 1 }}. {{ $exercise->title }}
                                    </div>
                                </div>

                                @if($exercise->description)
                                    <div class="exercise-description">
                                        {{ $exercise->description }}
                                    </div>
                                @endif

                                @if($sessionExercise->additional_description ?? null)
                                    <div class="exercise-description">
                                        {{ $sessionExercise->additional_description }}
                                    </div>
                                @endif

                                @if($sessionExercise->sets_count ?? null)
                                    <div class="exercise-description" style="font-weight: 600; color: #111827;">
                                        Nombre de séries : {{ $sessionExercise->sets_count }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($sets->count() > 0)
                            <table class="sets-table">
                                <thead>
                                    <tr>
                                        <th>Série</th>
                                        <th>Répétitions</th>
                                        <th>Charge</th>
                                        <th>Repos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sets as $setIndex => $set)
                                        <tr>
                                            <td class="set-index">{{ $set->set_number ?? ($setIndex + 1) }}</td>
                                            <td>{{ $set->repetitions ?? '-' }}</td>
                                            <td>
                                                @if(!empty($set->weight))
                                                    {{ $set->weight }} kg
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $set->rest_time ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <!-- FOOTER -->
    <div class="pdf-footer">
        <div>Téléchargez et gérez vos séances sur <strong>fitnessclic.com</strong></div>
        <div>Création de séances personnalisées en quelques clics</div>
    </div>
</body>
</html>
