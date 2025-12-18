<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>Séance - {{ $session->name ?? 'Nouvelle Séance' }}</title>
  <style>
    @page {
      /* Réduire l'espace entre le haut de page et le header */
      margin: 12px 16px 0 0;
    }
    
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
      color: #212121;
      margin: 0;
      /* Top plus compact + réserve pour le footer fixe */
      padding: 12px 16px 28px 16px;
    }

    table {
      border-collapse: collapse;
    }

    .container {
      width: 100%;
    }

    /* Header */
    .header-table {
      width: 100%;
      margin-bottom: 8px;
    }

    .header-logo-cell {
      vertical-align: top;
      padding-right: 12px;
    }

    .header-logo {
        height: 40px;              
        width: auto;
        display: block;
    }

    /* Footer: logo légèrement plus compact */
    .footer .header-logo {
      height: 28px;
      display: inline-block;
      vertical-align: middle;
      margin-left: 8px;
    }

    .header-title {
      font-size: 28px;
      font-weight: bold;
      color: #212121;
      vertical-align: top;
    }

    .header-date {
      font-size: 12px;
      text-align: right;
      vertical-align: top;
    }

    .calendar-icon {
      display: inline-block;
      width: 15px;
      height: 13px;
      margin-left: 8px;
      vertical-align: middle;
      position: relative;
      border: 1.2px solid #212121;
      border-radius: 1px;
      background-color: transparent;
      box-sizing: border-box;
    }

    .calendar-icon::before {
      content: '';
      position: absolute;
      top: -4px;
      left: 2px;
      width: 2.5px;
      height: 2.5px;
      border: 1.2px solid #212121;
      border-radius: 50%;
      background-color: transparent;
    }

    .calendar-icon::after {
      content: '';
      position: absolute;
      top: -4px;
      right: 2px;
      width: 2.5px;
      height: 2.5px;
      border: 1.2px solid #212121;
      border-radius: 50%;
      background-color: transparent;
    }

    .calendar-line {
      display: block;
      width: 85%;
      height: 1px;
      background-color: #212121;
      margin: 2.5px auto 0;
    }

    .header-line {
      border-bottom: 2px solid #212121;
      margin: 8px 0 10px 0;
    }

    .header-info-table {
      width: 100%;
      /* Réduire l'espace entre le header et le 1er exercice */
      margin-bottom: 16px;
    }

    .header-info-table td {
      font-size: 12px;
      padding: 0;
      border: none;
      margin-bottom: 10px;
    }

    .note-label {
      font-weight: bold;
    }

    /* Exercise Section */
    .exercise-table {
      width: 100%;
      /* Réduire l'espace entre chaque exercice */
      margin-top: 10px;
      background-color: #f7f7f7;  
      padding: 5px;               
      border-radius: 4px;         
    }

    .exercise-table-first {
      /* Un poil plus serré sous le header */
      margin-top: 8px;
    }

    .exercise-number-cell {
      width: 40px;
      vertical-align: top;
      border-left: 2px solid #212121;
      padding-left: 8px;
      padding-top: 4px;
    }

    .exercise-number {
      font-size: 26px;
      font-weight: bold;
      color: #212121;
    }

    .exercise-content-cell {
      vertical-align: top;
      padding-left: 12px;
    }

    /* Exercise Header */
    .exercise-header-table {
      width: 100%;
    }

    .exercise-image-cell {
      width: 100px;
      vertical-align: top;
      text-align: center;
    }

    .exercise-image {
      width: 100px;
      height: 62px;
      background-color: #f3f4f6;
      text-align: center;
      line-height: 62px;
      font-size: 10px;
      color: #9ca3af;
    }

    .exercise-image-small {
      width: 80px;
      height: 48px;
      background-color: #f3f4f6;
      text-align: center;
      line-height: 48px;
      font-size: 10px;
      color: #9ca3af;
    }

    .exercise-details-cell {
      vertical-align: top;
      padding-left: 16px;
    }

    .exercise-title {
      font-size: 15px;
      font-weight: bold;
      margin-bottom: 4px;
    }

    .exercise-title-small {
      font-size: 14px;
      font-weight: bold;
      margin-bottom: 4px;
    }

    .exercise-description {
      font-size: 12px;
      line-height: 1.3;
    }

    /* Data Tables */
    .data-table {
        margin-top: 8px;
        width: 100%;
        border-collapse: collapse;
        border-left: 1px solid #d1d5db;   
        border-right: 1px solid #d1d5db;  
    }

    .data-table th {
        background-color: #e0f4fc;
        color: #212121;
        font-size: 12px;
        font-weight: 500;
        padding: 6px 12px;
        text-align: center;
        border-top: 1px solid #d1d5db;    
        border-bottom: 1px solid #d1d5db; 
        border-left: none;                 
        border-right: none;
    }

    .data-table td {
        font-size: 12px;
        padding: 6px 12px;
        text-align: center;
        border-top: 1px solid #d1d5db;    
        border-bottom: 1px solid #d1d5db; 
        border-left: none;                 
        border-right: none;
    }

    .data-table-small {
      margin-top: 8px;
    }

    .data-table-small th {
      background-color: #e0f4fc;
      color: #212121;
      font-size: 10px;
      font-weight: 500;
      padding: 4px 8px;
      text-align: center;
      border: 1px solid #d1d5db;
    }

    .data-table-small td {
      font-size: 10px;
      padding: 4px 8px;
      text-align: center;
      border: 1px solid #d1d5db;
    }

    .bold {
      font-weight: bold;
    }

    /* Series lines */
    .series-table {
      width: 100%;
      margin-top: 4px;
      border-collapse: collapse;
    }

    .series-table td {
      width: 25%;
      background-color: #e0f4fc;
      color: #212121;
      font-size: 12px;
      padding: 4px 6px;
      text-align: center;
      border-right: 1px solid #d1d5db;
    }

    .series-table td:last-child {
      border-right: none;
    }

    /* Super Set */
    .superset-label {
      text-align: right;
      margin-bottom: 8px;
      margin-right: 25px;
      font-size: 12px;
    }

    .superset-label-bold {
      font-weight: bold;
    }

    .sub-exercise-table {
      width: 100%;
      margin-bottom: 10px;
    }

    .sub-exercise-image-cell {
      width: 80px;
      vertical-align: top;
      text-align: center;
    }

    .sub-exercise-content-cell {
      vertical-align: top;
      padding-left: 12px;
    }

    /* Footer */
    .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      width: 100%;
      background-color: #d5f5f5;
      /* Footer légèrement moins haut */
      padding: 4px 12px;
      z-index: 1000;
      margin: 0;
    }

    .footer-table {
      width: 100%;
      max-width: 100%;
      margin: 0 auto;
    }

    .footer-text-cell {
        display: flex !important;
        align-items: center;          
        justify-content: center;      
        text-align: center;
        font-size: 10px; 
        line-height: 1.15;
    }

    .footer-text {
      font-size: 10px;
    }

    .footer-text-bold {
      font-weight: bold;
    }

    .footer-image-cell {
      width: 200px;
      text-align: right;
      vertical-align: middle;
      padding-left: 20px;
    }

    .footer-image {
      width: 80px;
      height: 50px;
      background-color: transparent;
      text-align: center;
      line-height: 50px;
      font-size: 10px;
      color: #9ca3af;
    }

    .footer-image img {
      width: 100px;
      height: auto;
      max-height: 50px;
    }

    img {
      max-width: 100%;
      height: auto;
    }
  </style>
</head>
<body>
@php
    // Les fonctions formatDuration, formatRestTime, extractRestSeconds et extractDurationSeconds
    // sont maintenant dans app/helpers.php et chargées automatiquement

    $sessionExercises = $session->sessionExercises ?? collect();
    if (is_array($sessionExercises)) $sessionExercises = collect($sessionExercises);
    if (!($sessionExercises instanceof \Illuminate\Support\Collection)) $sessionExercises = collect($sessionExercises);
    $sessionExercises = $sessionExercises->sortBy('order')->values();
    $exerciseCount = $sessionExercises->count();

    // Grouper les exercices en blocs
    $setBlocks = [];
    $standardExercises = [];

    foreach ($sessionExercises as $sessionExercise) {
        $blockId = $sessionExercise->block_id ?? null;
        $blockType = $sessionExercise->block_type ?? null;
        $order = $sessionExercise->order ?? 0;

        if ($blockType === 'set' && $blockId !== null) {
            if (!isset($setBlocks[$blockId])) {
                $setBlocks[$blockId] = [
                    'blockId'   => $blockId,
                    'order'     => $order,
                    'exercises' => [],
                ];
            }
            $setBlocks[$blockId]['exercises'][] = $sessionExercise;
        } else {
            $standardExercises[] = [
                'order'    => $order,
                'exercise' => $sessionExercise,
            ];
        }
    }

    foreach ($setBlocks as $blockId => &$block) {
        usort($block['exercises'], function($a, $b) {
            $posA = $a->position_in_block ?? 0;
            $posB = $b->position_in_block ?? 0;
            return $posA <=> $posB;
        });
        $block['type'] = 'set';
    }
    unset($block);

    $allItems = [];
    foreach ($setBlocks as $block) $allItems[] = $block;
    foreach ($standardExercises as $ex) {
        $allItems[] = [
            'type'     => 'standard',
            'order'    => $ex['order'],
            'exercise' => $ex['exercise'],
        ];
    }

    usort($allItems, function($a, $b) {
        $orderA = $a['order'] ?? 0;
        $orderB = $b['order'] ?? 0;
        return $orderA <=> $orderB;
    });

    $orderedItems   = $allItems;
    $sectionNumber  = 1;
@endphp

  <div class="container">
    <!-- Header -->
    <table class="header-table">
      <tr>
        <td class="header-title">{{ $session->name ?? 'Nouvelle Séance' }}</td>
        <td class="header-date">
          {{ $session->session_date ? \Carbon\Carbon::parse($session->session_date)->format('d/m/Y') : 'Non définie' }}
          <span class="calendar-icon">
            <span class="calendar-line"></span>
          </span>
        </td>
      </tr>
    </table>

    <div class="header-line"></div>

    <table class="header-info-table">
      <tr>
        <td style="width: 33%;">
          @if(isset($session->user))
            {{ strtoupper($session->user->name) }}
          @endif
        </td>
        <td style="width: 34%; text-align: center;">
          @if($session->notes ?? null)
            <span class="note-label">Note :</span> {{ $session->notes }}
          @endif
        </td>
        <td style="width: 33%; text-align: right; font-weight: bold;">{{ $exerciseCount }} exercice{{ $exerciseCount > 1 ? 's' : '' }}</td>
      </tr>
    </table>

    <!-- Exercises -->
    @if($exerciseCount > 0)
      @foreach($orderedItems as $item)
        <table class="exercise-table {{ $loop->first ? 'exercise-table-first' : '' }}">
          <tr style="margin: 15px;">
            <td class="exercise-number-cell">
              <span class="exercise-number">{{ $sectionNumber }}</span>
            </td>
            <td class="exercise-content-cell">
              @if($item['type'] === 'standard')
                @php
                  $sessionExercise = $item['exercise'];
                  $exercise = $sessionExercise->exercise ?? null;
                  $sets = $sessionExercise->sets ?? collect();
                  if (is_array($sets)) $sets = collect($sets);
                  $sets = $sets->sortBy('order');

                  $exerciseImage = null;
                  if ($exercise) {
                    $firstMedia = $exercise->getFirstMedia('exercise_image');
                    if ($firstMedia) {
                      $imagePath = $firstMedia->getPath();
                      if (file_exists($imagePath)) {
                        $exerciseImage = $imagePath;
                      } else {
                        $publicPath = public_path('storage/' . $firstMedia->getPathRelativeToRoot());
                        if (file_exists($publicPath)) $exerciseImage = $publicPath;
                      }
                    }
                  }

                  $setsCount    = $sets->count() > 0 ? $sets->count() : ($sessionExercise->sets_count ?? 1);
                  // Récupérer use_duration et use_bodyweight - gérer différents formats (bool, int, string)
                  $useDurationRaw = $sessionExercise->use_duration ?? false;
                  $useBodyweightRaw = $sessionExercise->use_bodyweight ?? false;
                  
                  // Convertir en boolean de manière robuste
                  $useDuration = $useDurationRaw === true || $useDurationRaw === 1 || $useDurationRaw === '1' || $useDurationRaw === 'true';
                  $useBodyweight = $useBodyweightRaw === true || $useBodyweightRaw === 1 || $useBodyweightRaw === '1' || $useBodyweightRaw === 'true';

                  $durationOrReps = '-';
                  if ($sets->count() > 0) {
                    $firstSet = $sets->first();
                    if ($useDuration) {
                      $rawDuration   = $firstSet->duration ?? $sessionExercise->duration ?? '-';
                      $durationOrReps= formatDuration($rawDuration);
                    } else {
                      $durationOrReps= $firstSet->repetitions ?? $sessionExercise->repetitions ?? '-';
                    }
                  } else {
                    if ($useDuration) {
                      $rawDuration   = $sessionExercise->duration ?? '-';
                      $durationOrReps= formatDuration($rawDuration);
                    } else {
                      $durationOrReps= $sessionExercise->repetitions ?? '-';
                    }
                  }

                  $charge = '-';
                  if ($useBodyweight) {
                    $charge = 'poids de corps';
                  } else {
                    if ($sets->count() > 0) {
                      $firstSet = $sets->first();
                      $charge   = !empty($firstSet->weight) ? $firstSet->weight.' kg' : ($sessionExercise->weight ?? '-');
                    } else {
                      $charge   = !empty($sessionExercise->weight) ? $sessionExercise->weight.' kg' : '-';
                    }
                  }

                  $rest = '-';
                  if ($sets->count() > 0) {
                    $firstSet = $sets->first();
                    $rawRest  = $firstSet->rest_time ?? $sessionExercise->rest_time ?? '-';
                    $rest     = formatRestTime($rawRest);
                  } else {
                    $rawRest  = $sessionExercise->rest_time ?? '-';
                    $rest     = formatRestTime($rawRest);
                  }
                @endphp

                @if($exercise)
                  <table class="exercise-header-table">
                    <tr>
                      <td class="exercise-image-cell">
                        @if($exerciseImage)
                          <img src="{{ $exerciseImage }}" alt="{{ $sessionExercise->custom_exercise_name ?? $exercise->title }}" style="max-width: 100px; max-height: 62px; width: auto; height: auto;">
                        @else
                          <div class="exercise-image">🏋️</div>
                        @endif
                      </td>
                      <td class="exercise-details-cell">
                        <div class="exercise-title">{{ $sessionExercise->custom_exercise_name ?? $exercise->title }}</div>
                        @if($sessionExercise->additional_description ?? $exercise->description ?? null)
                          <div class="exercise-description">{{ $sessionExercise->additional_description ?? $exercise->description }}</div>
                        @endif
                      </td>
                    </tr>
                  </table>
                  <div style="margin-top: 3px;">
                    @if($sets->count() > 0)
                      @foreach($sets as $set)
                        @php
                          $setNumber = $set->set_number ?? $loop->iteration;
                          
                          // Utiliser use_duration et use_bodyweight du set, sinon ceux de l'exercice
                          $setUseDurationRaw = $set->use_duration ?? $sessionExercise->use_duration ?? false;
                          $setUseBodyweightRaw = $set->use_bodyweight ?? $sessionExercise->use_bodyweight ?? false;
                          $setUseDuration = $setUseDurationRaw === true || $setUseDurationRaw === 1 || $setUseDurationRaw === '1' || $setUseDurationRaw === 'true';
                          $setUseBodyweight = $setUseBodyweightRaw === true || $setUseBodyweightRaw === 1 || $setUseBodyweightRaw === '1' || $setUseBodyweightRaw === 'true';
                          
                          $setDurationOrReps = '-';
                          if ($setUseDuration) {
                            $rawDuration = $set->duration ?? $sessionExercise->duration ?? '-';
                            $durationSeconds = extractDurationSeconds($rawDuration);
                            $repsLabel = 'Durée';
                            $repsValue = $durationSeconds;
                            $repsDisplayLabel = 'seconde';
                          } else {
                            $setDurationOrReps = $set->repetitions ?? $sessionExercise->repetitions ?? '-';
                            $repsLabel = 'Repets';
                            $repsValue = $setDurationOrReps;
                            $repsDisplayLabel = 'répétition';
                          }

                          $setCharge = '-';
                          $chargeLabel = 'Charges';
                          if ($setUseBodyweight) {
                            $setCharge = 'poids de corps';
                            $chargeLabel = 'Poids de corps';
                          } else {
                            $setCharge = !empty($set->weight) ? $set->weight : ($sessionExercise->weight ?? '-');
                            if ($setCharge !== '-' && $setCharge !== null) {
                              $setCharge = is_numeric($setCharge) ? number_format((float)$setCharge, 0, '.', '') : $setCharge;
                            }
                          }

                          $setRest = '-';
                          $rawRest = $set->rest_time ?? $sessionExercise->rest_time ?? '-';
                          $restSeconds = extractRestSeconds($rawRest);
                          
                          // Construire le texte de la charge
                          $chargeText = '';
                          if ($setUseBodyweight) {
                            $chargeText = 'Poids de corps';
                          } else {
                            $chargeText = $chargeLabel . ' : ' . $setCharge;
                          }
                        @endphp
                        <table class="series-table">
                          <tr>
                            <td><strong>{{ $setNumber }}</strong> série{{ $setNumber > 1 ? 's' : '' }}</td>
                            <td>
                              @if($setUseDuration)
                                Durée : <strong>{{ $repsValue }}</strong> {{ 'seconde' . ($repsValue > 1 ? 's' : '') }}
                              @else
                                <strong>{{ $repsValue }}</strong> {{ $repsDisplayLabel . ($repsValue > 1 ? 's' : '') }}
                              @endif
                            </td>
                            <td>
                              @if($setUseBodyweight)
                                <strong>Poids de corps</strong>
                              @else
                                charge : <strong>{{ $setCharge !== '-' && $setCharge !== null ? $setCharge . 'kg' : '-' }}</strong>
                              @endif
                            </td>
                            <td>repos inter-séries : <strong>{{ $restSeconds !== '-' ? $restSeconds . ' seconde' . ($restSeconds > 1 ? 's' : '') : '-' }}</strong></td>
                          </tr>
                        </table>
                      @endforeach
                    @else
                      @php
                        if ($useDuration) {
                          $durationSeconds = extractDurationSeconds($durationOrReps);
                          $repsLabel = 'Durée (secondes)';
                          $repsValue = $durationSeconds;
                          $repsDisplayLabel = 'seconde';
                        } else {
                          $repsLabel = 'Repets';
                          $repsValue = $durationOrReps;
                          $repsDisplayLabel = 'répétition';
                        }
                        $chargeLabel = $useBodyweight ? 'Poids de corps' : 'Charges';
                        $chargeValue = $charge;
                        if (!$useBodyweight && $chargeValue !== '-' && $chargeValue !== 'poids de corps') {
                          $chargeValue = str_replace(' kg', '', $chargeValue);
                          if (is_numeric($chargeValue)) {
                            $chargeValue = number_format((float)$chargeValue, 0, '.', '');
                          }
                        }
                        $restSeconds = extractRestSeconds($rest);
                        
                        // Construire le texte de la charge
                        $chargeText = '';
                        if ($useBodyweight) {
                          $chargeText = 'Poids de corps';
                        } else {
                          $chargeText = $chargeLabel . ' : ' . $chargeValue;
                        }
                      @endphp
                      <table class="series-table">
                        <tr>
                          <td><strong>{{ $setsCount }}</strong> série{{ $setsCount > 1 ? 's' : '' }}</td>
                          <td>
                            @if($useDuration)
                              Durée : <strong>{{ $repsValue }}</strong> {{ 'seconde' . ($repsValue > 1 ? 's' : '') }}
                            @else
                              <strong>{{ $repsValue }}</strong> {{ $repsDisplayLabel . ($repsValue > 1 ? 's' : '') }}
                            @endif
                          </td>
                          <td>
                            @if($useBodyweight)
                              <strong>Poids de corps</strong>
                            @else
                              charge : <strong>{{ $chargeValue !== '-' && $chargeValue !== null && $chargeValue !== 'poids de corps' ? $chargeValue . 'kg' : '-' }}</strong>
                            @endif
                          </td>
                          <td>repos inter-séries : <strong>{{ $restSeconds !== '-' ? $restSeconds . ' seconde' . ($restSeconds > 1 ? 's' : '') : '-' }}</strong></td>
                        </tr>
                      </table>
                    @endif
                  </div>
                @endif
              @else
                @php
                  $blockExercises   = $item['exercises'];
                  $firstExercise    = $blockExercises[0];
                  $blockDescription = $firstExercise->description ?? $firstExercise->additional_description ?? '';
                @endphp

            @if($blockDescription)
                <div class="superset-label">
                  <span class="superset-label-bold">Super set :</span> {{ $blockDescription }}
                </div>
            @endif

                @foreach($blockExercises as $sessionExercise)
                  @php
                    $exercise = $sessionExercise->exercise ?? null;
                    $sets = $sessionExercise->sets ?? collect();
                    if (is_array($sets)) $sets = collect($sets);
                    $sets = $sets->sortBy('order');

                    $exerciseImage = null;
                    if ($exercise) {
                      $firstMedia = $exercise->getFirstMedia('exercise_image');
                      if ($firstMedia) {
                        $imagePath = $firstMedia->getPath();
                        if (file_exists($imagePath)) {
                          $exerciseImage = $imagePath;
                        } else {
                          $publicPath = public_path('storage/' . $firstMedia->getPathRelativeToRoot());
                          if (file_exists($publicPath)) $exerciseImage = $publicPath;
                        }
                      }
                    }

                    $setsCount    = $sets->count() > 0 ? $sets->count() : ($sessionExercise->sets_count ?? 1);
                    // Récupérer use_duration et use_bodyweight - gérer différents formats (bool, int, string)
                    $useDurationRaw = $sessionExercise->use_duration ?? false;
                    $useBodyweightRaw = $sessionExercise->use_bodyweight ?? false;
                    
                    // Convertir en boolean
                    $useDuration = $useDurationRaw === true || $useDurationRaw === 1 || $useDurationRaw === '1' || $useDurationRaw === 'true';
                    $useBodyweight = $useBodyweightRaw === true || $useBodyweightRaw === 1 || $useBodyweightRaw === '1' || $useBodyweightRaw === 'true';

                    $durationOrReps = '-';
                    if ($sets->count() > 0) {
                      $firstSet = $sets->first();
                      if ($useDuration) {
                        $rawDuration   = $firstSet->duration ?? $sessionExercise->duration ?? '-';
                        $durationOrReps= formatDuration($rawDuration);
                      } else {
                        $durationOrReps= $firstSet->repetitions ?? $sessionExercise->repetitions ?? '-';
                      }
                    } else {
                      if ($useDuration) {
                        $rawDuration   = $sessionExercise->duration ?? '-';
                        $durationOrReps= formatDuration($rawDuration);
                      } else {
                        $durationOrReps= $sessionExercise->repetitions ?? '-';
                      }
                    }

                    $charge = '-';
                    if ($useBodyweight) {
                      $charge = 'poids de corps';
                    } else {
                      if ($sets->count() > 0) {
                        $firstSet = $sets->first();
                        $charge   = !empty($firstSet->weight) ? $firstSet->weight.' kg' : ($sessionExercise->weight ?? '-');
                      } else {
                        $charge   = !empty($sessionExercise->weight) ? $sessionExercise->weight.' kg' : '-';
                      }
                    }

                    $rest = '-';
                    if ($sets->count() > 0) {
                      $firstSet = $sets->first();
                      $rawRest  = $firstSet->rest_time ?? $sessionExercise->rest_time ?? '-';
                      $rest     = formatRestTime($rawRest);
                    } else {
                      $rawRest  = $sessionExercise->rest_time ?? '-';
                      $rest     = formatRestTime($rawRest);
                    }
                  @endphp

                  @if($exercise)
                    <table class="sub-exercise-table">
                      <tr>
                        <td class="sub-exercise-image-cell">
                          @if($exerciseImage)
                            <img src="{{ $exerciseImage }}" alt="{{ $sessionExercise->custom_exercise_name ?? $exercise->title }}" style="max-width: 80px; max-height: 48px; width: auto; height: auto;">
                          @endif
                        </td>
                        <td class="sub-exercise-content-cell">
                          <div class="exercise-title-small">{{ $sessionExercise->custom_exercise_name ?? $exercise->title }}</div>
                          <div style="margin-top: 3px;">
                            @if($sets->count() > 0)
                              @foreach($sets as $set)
                                @php
                                  $setNumber = $set->set_number ?? $loop->iteration;
                                  
                                  // Utiliser use_duration et use_bodyweight du set, sinon ceux de l'exercice
                                  $setUseDurationRaw = $set->use_duration ?? $sessionExercise->use_duration ?? false;
                                  $setUseBodyweightRaw = $set->use_bodyweight ?? $sessionExercise->use_bodyweight ?? false;
                                  $setUseDuration = $setUseDurationRaw === true || $setUseDurationRaw === 1 || $setUseDurationRaw === '1' || $setUseDurationRaw === 'true';
                                  $setUseBodyweight = $setUseBodyweightRaw === true || $setUseBodyweightRaw === 1 || $setUseBodyweightRaw === '1' || $setUseBodyweightRaw === 'true';
                                  
                                  $setDurationOrReps = '-';
                                  if ($setUseDuration) {
                                    $rawDuration = $set->duration ?? $sessionExercise->duration ?? '-';
                                    $durationSeconds = extractDurationSeconds($rawDuration);
                                    $repsLabel = 'Durée (secondes)';
                                    $repsValue = $durationSeconds;
                                    $repsDisplayLabel = 'seconde';
                                  } else {
                                    $setDurationOrReps = $set->repetitions ?? $sessionExercise->repetitions ?? '-';
                                    $repsLabel = 'Repets';
                                    $repsValue = $setDurationOrReps;
                                    $repsDisplayLabel = 'répétition';
                                  }

                                  $setCharge = '-';
                                  $chargeLabel = 'Charges';
                                  if ($setUseBodyweight) {
                                    $setCharge = 'poids de corps';
                                    $chargeLabel = 'Poids de corps';
                                  } else {
                                    $setCharge = !empty($set->weight) ? $set->weight : ($sessionExercise->weight ?? '-');
                                    if ($setCharge !== '-' && $setCharge !== null) {
                                      $setCharge = is_numeric($setCharge) ? number_format((float)$setCharge, 0, '.', '') : $setCharge;
                                    }
                                  }

                                  $setRest = '-';
                                  $rawRest = $set->rest_time ?? $sessionExercise->rest_time ?? '-';
                                  $restSeconds = extractRestSeconds($rawRest);
                                  
                                  // Construire le texte de la charge
                                  $chargeText = '';
                                  if ($setUseBodyweight) {
                                    $chargeText = 'Poids de corps';
                                  } else {
                                    $chargeText = $chargeLabel . ' : ' . $setCharge;
                                  }
                                @endphp
                                <table class="series-table">
                                  <tr>
                                    <td><strong>{{ $setNumber }}</strong> série{{ $setNumber > 1 ? 's' : '' }}</td>
                                    <td>
                                      @if($setUseDuration)
                                        Durée : <strong>{{ $repsValue }}</strong> {{ 'seconde' . ($repsValue > 1 ? 's' : '') }}
                                      @else
                                        <strong>{{ $repsValue }}</strong> {{ $repsDisplayLabel . ($repsValue > 1 ? 's' : '') }}
                                      @endif
                                    </td>
                                    <td>
                                      @if($setUseBodyweight)
                                        <strong>Poids de corps</strong>
                                      @else
                                        charge : <strong>{{ $setCharge !== '-' && $setCharge !== null ? $setCharge . 'kg' : '-' }}</strong>
                                      @endif
                                    </td>
                                    <td>repos inter-séries : <strong>{{ $restSeconds !== '-' ? $restSeconds . ' seconde' . ($restSeconds > 1 ? 's' : '') : '-' }}</strong></td>
                                  </tr>
                                </table>
                              @endforeach
                            @else
                              @php
                                if ($useDuration) {
                                  $durationSeconds = extractDurationSeconds($durationOrReps);
                                  $repsLabel = 'Durée (secondes)';
                                  $repsValue = $durationSeconds;
                                  $repsDisplayLabel = 'seconde';
                                } else {
                                  $repsLabel = 'Repets';
                                  $repsValue = $durationOrReps;
                                  $repsDisplayLabel = 'répétition';
                                }
                                $chargeLabel = $useBodyweight ? 'Poids de corps' : 'Charges';
                                $chargeValue = $charge;
                                if (!$useBodyweight && $chargeValue !== '-' && $chargeValue !== 'poids de corps') {
                                  $chargeValue = str_replace(' kg', '', $chargeValue);
                                  if (is_numeric($chargeValue)) {
                                    $chargeValue = number_format((float)$chargeValue, 0, '.', '');
                                  }
                                }
                                $restSeconds = extractRestSeconds($rest);
                                
                                // Construire le texte de la charge
                                $chargeText = '';
                                if ($useBodyweight) {
                                  $chargeText = 'Poids de corps';
                                } else {
                                  $chargeText = $chargeLabel . ' : ' . $chargeValue;
                                }
                              @endphp
                              <table class="series-table">
                                <tr>
                                  <td><strong>{{ $setsCount }}</strong> série{{ $setsCount > 1 ? 's' : '' }}</td>
                                  <td>
                                    @if($useDuration)
                                      Durée : <strong>{{ $repsValue }}</strong> {{ 'seconde' . ($repsValue > 1 ? 's' : '') }}
                                    @else
                                      <strong>{{ $repsValue }}</strong> {{ $repsDisplayLabel . ($repsValue > 1 ? 's' : '') }}
                                    @endif
                                  </td>
                                  <td>
                                    @if($useBodyweight)
                                      <strong>Poids de corps</strong>
                                    @else
                                      charge : <strong>{{ $chargeValue !== '-' && $chargeValue !== null && $chargeValue !== 'poids de corps' ? $chargeValue . 'kg' : '-' }}</strong>
                                    @endif
                                  </td>
                                  <td>repos inter-séries : <strong>{{ $restSeconds !== '-' ? $restSeconds . ' seconde' . ($restSeconds > 1 ? 's' : '') : '-' }}</strong></td>
                                </tr>
                              </table>
                            @endif
                          </div>
                        </td>
                      </tr>
                    </table>
                  @endif
                @endforeach
              @endif
            </td>
          </tr>
        </table>
        @php $sectionNumber++; @endphp
      @endforeach
    @endif
  </div>

  <!-- Footer -->
  <div class="footer">
    <table class="footer-table">
      <tr>
        <td class="footer-text-cell">
          <span style="font-weight: bold;">Fitnessclic.com</span> <span>création de séances personnalisées en quelques clics.</span>
          @php
          $logoPath = '/var/www/fitnessclic/public/assets/logo_fitnessclic.png';
          if (!file_exists($logoPath)) {
            $logoPath = public_path('assets/logo_fitnessclic.png');
          }
          if (!file_exists($logoPath)) {
            $logoPath = base_path('public/assets/logo_fitnessclic.png');
          }
          if (!file_exists($logoPath)) {
            $logoPath = storage_path('app/public/assets/logo_fitnessclic.png');
          }
        @endphp
          @if(file_exists($logoPath))
              <img src="{{ $logoPath }}" alt="FitnessClic" class="header-logo">
          @endif
        </td>
      </tr>
    </table>
  </div>  
</body>
</html>
