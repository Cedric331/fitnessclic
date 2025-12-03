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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5mm;
            padding-bottom: 4mm;
            border-bottom: 1px solid #e5e7eb;
        }

        .logo-container img {
            height: 40px;
            width: auto;
        }

        .header-info {
            text-align: right;
            font-size: 7.5pt;
            color: #4b5563;
        }

        .header-info div {
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
        }

        .exercise-image {
            float: left;
            width: 60px;         /* largeur fixe */
            height: auto;        /* respecte le ratio */
            border-radius: 4px;
            border: 1px solid #d1d5db;
            margin-right: 4mm;
            display: block;
        }

        .exercise-header {
            overflow: hidden; /* pour contenir le float */
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
            width: 100%;
            border-collapse: collapse;
            margin-top: 3mm;
            font-size: 7.5pt;
        }

        .sets-table thead {
            background: #e5e7eb;
        }

        .sets-table th,
        .sets-table td {
            padding: 2mm 2mm;
            border: 1px solid #d1d5db;
            text-align: center;
            vertical-align: middle;
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

        /* NOTES --------------------------------------------------- */
        .notes-section {
            margin-top: 6mm;
            padding: 4mm;
            border-radius: 4px;
            background: #fffbeb;
            border: 1px solid #fef3c7;
        }

        .notes-section h3 {
            margin: 0 0 2mm;
            font-size: 8.5pt;
            font-weight: 700;
            color: #92400e;
        }

        .notes-section p {
            margin: 0;
            font-size: 7pt;
            color: #78350f;
            white-space: pre-wrap;
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
        <div class="logo-container">
            @php
                $logoPath = public_path('assets/logo_fitnessclic.png');
                $logoUrl = file_exists($logoPath)
                    ? $logoPath
                    : asset('assets/logo_fitnessclic.png');
            @endphp
            <img src="{{ $logoUrl }}" alt="FitnessClic">
        </div>

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
                    if ($exercise && isset($exercise->media) && $exercise->media->count() > 0) {
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
                        <div class="exercise-header">
                            @if($imagePath)
                                <img src="{{ $imagePath }}" alt="{{ $exercise->title }}" class="exercise-image">
                            @endif

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

    <!-- NOTES -->
    @if($session->notes ?? null)
        <div class="notes-section">
            <h3>Notes</h3>
            <p>{{ $session->notes }}</p>
        </div>
    @endif

    <!-- FOOTER -->
    <div class="pdf-footer">
        <div>Téléchargez et gérez vos séances sur <strong>fitnessclic.com</strong></div>
        <div>Création de séances personnalisées en quelques clics</div>
    </div>
</body>
</html>
