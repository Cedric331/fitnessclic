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

        .exercises-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
            margin-bottom: 2mm;
        }

        .exercises-table thead {
            background: #2563eb;
            color: white;
        }

        .exercises-table th {
            padding: 2mm 1.5mm;
            text-align: left;
            font-weight: 600;
            font-size: 7pt;
            border: 1px solid #1e40af;
        }

        .exercises-table td {
            padding: 1.5mm;
            border: 1px solid #d1d5db;
            vertical-align: top;
            font-size: 6.5pt;
        }

        .exercises-table tbody tr {
            page-break-inside: avoid;
        }

        .exercises-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .exercise-number {
            text-align: center;
            font-weight: bold;
            width: 8mm;
            color: #2563eb;
        }

        .exercise-title {
            font-weight: 600;
            color: #111827;
            min-width: 40mm;
        }

        .exercise-sets {
            font-size: 6pt;
            color: #4b5563;
            line-height: 1.4;
        }

        .super-set-row {
            background: #eff6ff !important;
            border-left: 3px solid #2563eb !important;
        }

        .super-set-header {
            background: #dbeafe !important;
            font-weight: 700;
            color: #1e40af;
        }

        .super-set-badge {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6pt;
            font-weight: 600;
            margin-right: 2mm;
        }

        .exercise-in-set {
            padding-left: 4mm;
            font-size: 6pt;
            color: #4b5563;
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
        // Convertir en collection si ce n'est pas déjà le cas
        if (!($sessionExercises instanceof \Illuminate\Support\Collection)) {
            $sessionExercises = collect($sessionExercises);
        }
        // Trier par ordre pour respecter l'ordre de la liste
        $sessionExercises = $sessionExercises->sortBy('order')->values();
        $exerciseCount = $sessionExercises->count();
        
        // Debug: vérifier que les champs block_id, block_type sont présents
        // (à retirer en production)
        // \Log::info('PDF Debug - Total exercises: ' . $sessionExercises->count());
        // foreach ($sessionExercises->take(5) as $se) {
        //     \Log::info('PDF Debug Exercise', [
        //         'id' => $se->id ?? 'no-id', 
        //         'block_id' => $se->block_id ?? 'null', 
        //         'block_type' => $se->block_type ?? 'null', 
        //         'position_in_block' => $se->position_in_block ?? 'null',
        //         'order' => $se->order ?? 'no-order'
        //     ]);
        // }
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
            @php
                // Créer une liste mixte qui respecte l'ordre de la liste
                $orderedItems = [];
                $displayIndex = 0;
                
                // D'abord, grouper tous les exercices par block_id pour les Super Set
                $setBlocks = [];
                $standardExercises = [];
                
                foreach ($sessionExercises as $sessionExercise) {
                    $blockId = $sessionExercise->block_id ?? null;
                    $blockType = $sessionExercise->block_type ?? null;
                    $order = $sessionExercise->order ?? 0;
                    
                    // Vérifier si c'est un exercice dans un bloc Super Set
                    if ($blockType === 'set' && $blockId !== null) {
                        // Grouper par block_id
                        if (!isset($setBlocks[$blockId])) {
                            $setBlocks[$blockId] = [
                                'blockId' => $blockId,
                                'order' => $order,
                                'exercises' => [],
                            ];
                        }
                        $setBlocks[$blockId]['exercises'][] = $sessionExercise;
                    } else {
                        // Exercice standard
                        $standardExercises[] = [
                            'order' => $order,
                            'exercise' => $sessionExercise,
                        ];
                    }
                }
                
                // Trier les exercices dans chaque bloc Super Set par position_in_block
                foreach ($setBlocks as $blockId => &$block) {
                    usort($block['exercises'], function($a, $b) {
                        $posA = $a->position_in_block ?? 0;
                        $posB = $b->position_in_block ?? 0;
                        return $posA <=> $posB;
                    });
                    $block['type'] = 'set';
                }
                unset($block);
                
                // Créer la liste ordonnée en mélangeant les blocs Super Set et les exercices standard
                $allItems = [];
                foreach ($setBlocks as $block) {
                    $allItems[] = $block;
                }
                foreach ($standardExercises as $ex) {
                    $allItems[] = [
                        'type' => 'standard',
                        'order' => $ex['order'],
                        'exercise' => $ex['exercise'],
                    ];
                }
                
                // Trier par ordre pour respecter l'ordre de la liste
                usort($allItems, function($a, $b) {
                    $orderA = $a['order'] ?? 0;
                    $orderB = $b['order'] ?? 0;
                    return $orderA <=> $orderB;
                });
                
                $orderedItems = $allItems;
            @endphp
            
            <table class="exercises-table">
                <thead>
                    <tr>
                        <th style="width: 8mm;">#</th>
                        <th>Exercice</th>
                        <th style="width: 20mm;">Séries</th>
                        <th style="width: 18mm;">Rep/Durée</th>
                        <th style="width: 15mm;">Charge/Poids</th>
                        <th style="width: 15mm;">Repos</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orderedItems as $item)
                        @if($item['type'] === 'standard')
                            @php
                                $sessionExercise = $item['exercise'];
                                $index = $displayIndex++;
                                $exercise = $sessionExercise->exercise ?? null;
                                $sets = $sessionExercise->sets ?? collect();
                                if (is_array($sets)) {
                                    $sets = collect($sets);
                                }
                                $sets = $sets->sortBy('order');
                            @endphp
                            @if($exercise)
                                <tr>
                                    <td class="exercise-number">{{ $index + 1 }}</td>
                                    <td class="exercise-title">{{ $sessionExercise->custom_exercise_name ?? $exercise->title }}</td>
                                    <td class="exercise-sets">
                                        @if($sets->count() > 0)
                                            {{ $sets->count() }}
                                        @elseif($sessionExercise->sets_count ?? null)
                                            {{ $sessionExercise->sets_count }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="exercise-sets">
                                        @if($sets->count() > 0)
                                            @foreach($sets as $set)
                                                @if($sessionExercise->use_duration ?? false)
                                                    {{ $set->duration ?? '-' }}@if(!$loop->last)<br>@endif
                                                @else
                                                    {{ $set->repetitions ?? '-' }}@if(!$loop->last)<br>@endif
                                                @endif
                                            @endforeach
                                        @else
                                            @if($sessionExercise->use_duration ?? false)
                                                {{ $sessionExercise->duration ?? '-' }}
                                            @else
                                                {{ $sessionExercise->repetitions ?? '-' }}
                                            @endif
                                        @endif
                                    </td>
                                    <td class="exercise-sets">
                                        @if($sessionExercise->use_bodyweight ?? false)
                                            Poids de corps
                                        @else
                                            @if($sets->count() > 0)
                                                @foreach($sets as $set)
                                                    @if(!empty($set->weight))
                                                        {{ $set->weight }}kg
                                                    @else
                                                        -
                                                    @endif
                                                    @if(!$loop->last)<br>@endif
                                                @endforeach
                                            @else
                                                @if(!empty($sessionExercise->weight))
                                                    {{ $sessionExercise->weight }}kg
                                                @else
                                                    -
                                                @endif
                                            @endif
                                        @endif
                                    </td>
                                    <td class="exercise-sets">
                                        @if($sets->count() > 0)
                                            @foreach($sets as $set)
                                                {{ $set->rest_time ?? '-' }}@if(!$loop->last)<br>@endif
                                            @endforeach
                                        @else
                                            {{ $sessionExercise->rest_time ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="exercise-sets" style="font-size: 6pt;">
                                        {{ $sessionExercise->additional_description ?? '-' }}
                                    </td>
                                </tr>
                            @endif
                        @else
                            @php
                                $blockExercises = $item['exercises'];
                                $blockIndex = $displayIndex++;
                                $firstExercise = true;
                            @endphp
                            @foreach($blockExercises as $exerciseIndex => $sessionExercise)
                                @php
                                    $exercise = $sessionExercise->exercise ?? null;
                                    $sets = $sessionExercise->sets ?? collect();
                                    if (is_array($sets)) {
                                        $sets = collect($sets);
                                    }
                                    $sets = $sets->sortBy('order');
                                @endphp
                                @if($exercise)
                                    <tr class="super-set-row">
                                        <td class="exercise-number">
                                            @if($firstExercise)
                                                <span class="super-set-badge">SS</span><br>
                                                {{ $blockIndex + 1 }}
                                                @php $firstExercise = false; @endphp
                                            @endif
                                        </td>
                                        <td class="exercise-title exercise-in-set">
                                            <strong>{{ $exerciseIndex + 1 }}.</strong> {{ $sessionExercise->custom_exercise_name ?? $exercise->title }}
                                        </td>
                                        <td class="exercise-sets">
                                            @if($sets->count() > 0)
                                                {{ $sets->count() }}
                                            @elseif($sessionExercise->sets_count ?? null)
                                                {{ $sessionExercise->sets_count }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="exercise-sets">
                                            @if($sets->count() > 0)
                                                @foreach($sets as $set)
                                                    @if($sessionExercise->use_duration ?? false)
                                                        {{ $set->duration ?? '-' }}@if(!$loop->last)<br>@endif
                                                    @else
                                                        {{ $set->repetitions ?? '-' }}@if(!$loop->last)<br>@endif
                                                    @endif
                                                @endforeach
                                            @else
                                                @if($sessionExercise->use_duration ?? false)
                                                    {{ $sessionExercise->duration ?? '-' }}
                                                @else
                                                    {{ $sessionExercise->repetitions ?? '-' }}
                                                @endif
                                            @endif
                                        </td>
                                        <td class="exercise-sets">
                                            @if($sessionExercise->use_bodyweight ?? false)
                                                Poids de corps
                                            @else
                                                @if($sets->count() > 0)
                                                    @foreach($sets as $set)
                                                        @if(!empty($set->weight))
                                                            {{ $set->weight }}kg
                                                        @else
                                                            -
                                                        @endif
                                                        @if(!$loop->last)<br>@endif
                                                    @endforeach
                                                @else
                                                    @if(!empty($sessionExercise->weight))
                                                        {{ $sessionExercise->weight }}kg
                                                    @else
                                                        -
                                                    @endif
                                                @endif
                                            @endif
                                        </td>
                                        <td class="exercise-sets">
                                            @if($sets->count() > 0)
                                                @foreach($sets as $set)
                                                    {{ $set->rest_time ?? '-' }}@if(!$loop->last)<br>@endif
                                                @endforeach
                                            @else
                                                {{ $sessionExercise->rest_time ?? '-' }}
                                            @endif
                                        </td>
                                        <td class="exercise-sets" style="font-size: 6pt;">
                                            {{ $sessionExercise->additional_description ?? '-' }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- FOOTER -->
    <div class="pdf-footer">
        <div>Téléchargez et gérez vos séances sur <strong>fitnessclic.com</strong></div>
        <div>Création de séances personnalisées en quelques clics</div>
    </div>
</body>
</html>
