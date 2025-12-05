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
                $processedBlockIds = [];
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
                                'description' => null,
                            ];
                        }
                        $setBlocks[$blockId]['exercises'][] = $sessionExercise;
                        // Prendre la description du premier exercice du bloc
                        if ($setBlocks[$blockId]['description'] === null) {
                            $setBlocks[$blockId]['description'] = $sessionExercise->additional_description ?? $sessionExercise->description ?? null;
                        }
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
            
            <!-- Afficher les items dans l'ordre de la liste -->
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
                @else
                    @php
                        $blockExercises = $item['exercises'];
                        $blockDescription = $item['description'];
                        $blockIndex = $displayIndex++;
                    @endphp
                    <div class="super7-block" style="page-break-inside: avoid; margin-bottom: 8mm; border: 2px solid #3b82f6; border-radius: 8px; padding: 4mm; background: linear-gradient(to bottom, #f0f9ff 0%, #e0f2fe 100%); position: relative; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);">
                        <!-- Numéro du bloc en haut à gauche -->
                        <div style="position: absolute; top: -12px; left: -12px; width: 28px; height: 28px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10pt; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            {{ $blockIndex + 1 }}
                        </div>
                        <!-- Badge Super Set en haut à droite -->
                        <div style="position: absolute; top: -10px; right: 10px; background: #3b82f6; color: white; padding: 2px 8px; border-radius: 12px; font-size: 7pt; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                            Super Set
                        </div>
                        @if($blockDescription && !empty(trim($blockDescription)))
                            <div style="font-size: 8pt; margin-bottom: 3mm; margin-top: 5px; padding: 2mm; border: 1px solid #93c5fd; border-left: 4px solid #3b82f6; background-color: #eff6ff; border-radius: 4px;">
                                <strong style="color: #1e40af;">Consignes du bloc :</strong> <span style="color: #1e3a8a;">{{ $blockDescription }}</span>
                            </div>
                        @endif
                        <div style="display: grid; grid-template-columns: repeat({{ count($blockExercises) }}, 1fr); gap: 3mm; margin-top: 2mm;">
                            @foreach($blockExercises as $sessionExercise)
                                @php
                                    $exercise = $sessionExercise->exercise ?? null;
                                    $imagePath = null;
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
                                <div class="set-exercise-item" style="border: 1px solid #93c5fd; border-radius: 6px; padding: 2.5mm; background: white; box-shadow: 0 1px 2px rgba(59, 130, 246, 0.1);">
                                    @if($exercise)
                                        @if($imagePath)
                                            <img src="{{ $imagePath }}" alt="{{ $exercise->title }}" style="max-width: 100%; height: auto; margin-bottom: 1.5mm; border-radius: 4px; border: 1px solid #e5e7eb;">
                                        @endif
                                        <div style="font-size: 7pt; font-weight: 600; margin-bottom: 1.5mm; line-height: 1.3; color: #1e3a8a;">
                                            {{ $exercise->title }}
                                        </div>
                                        <!-- Informations compactes (serie, reps, poids, repos) -->
                                        @php
                                            $sets = $sessionExercise->sets ?? collect();
                                            if (is_array($sets)) {
                                                $sets = collect($sets);
                                            }
                                            $firstSet = $sets->first();
                                        @endphp
                                        <div style="font-size: 6pt; color: #4b5563; line-height: 1.5; background: #f9fafb; padding: 1.5mm; border-radius: 3px;">
                                            @if($sessionExercise->sets_count ?? null) 
                                                <div style="margin-bottom: 0.5mm;"><strong style="color: #1e40af;">Série:</strong> {{ $sessionExercise->sets_count }}</div>
                                            @endif
                                            @if($firstSet && ($firstSet->repetitions ?? null)) 
                                                <div style="margin-bottom: 0.5mm;"><strong style="color: #1e40af;">Rep:</strong> {{ $firstSet->repetitions }}</div>
                                            @elseif($sessionExercise->repetitions ?? null)
                                                <div style="margin-bottom: 0.5mm;"><strong style="color: #1e40af;">Rep:</strong> {{ $sessionExercise->repetitions }}</div>
                                            @endif
                                            @if($firstSet && ($firstSet->weight ?? null)) 
                                                <div style="margin-bottom: 0.5mm;"><strong style="color: #1e40af;">Charge:</strong> {{ $firstSet->weight }}kg</div>
                                            @elseif($sessionExercise->weight ?? null)
                                                <div style="margin-bottom: 0.5mm;"><strong style="color: #1e40af;">Charge:</strong> {{ $sessionExercise->weight }}kg</div>
                                            @endif
                                            @if($firstSet && ($firstSet->rest_time ?? null)) 
                                                <div><strong style="color: #1e40af;">Repos:</strong> {{ $firstSet->rest_time }}</div>
                                            @elseif($sessionExercise->rest_time ?? null)
                                                <div><strong style="color: #1e40af;">Repos:</strong> {{ $sessionExercise->rest_time }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
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
