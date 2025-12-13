<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
  <title>Séance - {{ $session->name ?? 'Nouvelle Séance' }}</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    html, body {
      height: 100%;
      overflow: hidden;
      width: 100%;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      font-size: 14px;
      color: #212121;
      background-color: #f5f5f5;
      line-height: 1.6;
      height: 100vh;
      width: 100%;
      max-width: 100%;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow-x: hidden;
      box-sizing: border-box;
    }

    .scrollable-container {
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 20px;
      padding-bottom: 20px;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    
    @media (min-width: 769px) {
      .scrollable-container {
        padding-bottom: 100px;
      }
    }

    .main-content {
      max-width: 100%;
      width: 100%;
      margin: 0 auto;
      background-color: #ffffff;
      padding: 30px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      margin-bottom: 20px;
      box-sizing: border-box;
    }
    
    @media (min-width: 769px) {
      .main-content {
        max-width: 800px;
      }
    }

    table {
      border-collapse: collapse;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    
    table tbody,
    table thead,
    table tr,
    table td,
    table th {
      box-sizing: border-box;
    }

    /* Header */
    .header-table {
      width: 100%;
      margin-bottom: 8px;
      table-layout: fixed;
    }

    .header-title {
      font-size: 28px;
      font-weight: bold;
      color: #212121;
      vertical-align: top;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }

    .header-date {
      font-size: 12px;
      text-align: right;
      vertical-align: top;
      white-space: nowrap;
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
      margin: 10px 0 12px 0;
    }

    .header-info-table {
      width: 100%;
      margin-bottom: 30px;
      table-layout: fixed;
    }

    .header-info-table td {
      font-size: 12px;
      padding: 0;
      border: none;
      margin-bottom: 10px;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }

    .note-label {
      font-weight: bold;
    }

    /* Exercise Section */
    .exercise-table {
      width: 100%;
      margin-top: 24px;
      background-color: #f7f7f7;  
      padding: 16px;               
      border-radius: 4px;
      border-left: 2px solid #212121;
      table-layout: fixed;
    }

    .exercise-number-cell {
      width: 50px;
      min-width: 50px;
      vertical-align: top;
      padding-right: 12px;
    }

    .exercise-number {
      font-size: 32px;
      font-weight: bold;
      color: #212121;
    }

    .exercise-content-cell {
      vertical-align: top;
      min-width: 0;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }

    /* Exercise Header */
    .exercise-header-table {
      width: 100%;
      margin-bottom: 12px;
      table-layout: fixed;
    }

    .exercise-image-cell {
      width: 120px;
      min-width: 100px;
      vertical-align: top;
      padding-right: 16px;
    }

    .exercise-image {
      width: 120px;
      height: 85px;
      background-color: #f3f4f6;
      text-align: center;
      line-height: 85px;
      font-size: 10px;
      color: #9ca3af;
      border-radius: 4px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .exercise-image img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .exercise-image-small {
      width: 100px;
      height: 70px;
      background-color: #f3f4f6;
      text-align: center;
      line-height: 70px;
      font-size: 10px;
      color: #9ca3af;
      border-radius: 4px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .exercise-image-small img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .exercise-details-cell {
      vertical-align: top;
      min-width: 0;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }

    .exercise-title {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 8px;
      color: #212121;
    }

    .exercise-title-small {
      font-size: 16px;
      font-weight: bold;
      margin-bottom: 6px;
      color: #212121;
    }

    .exercise-description {
      font-size: 13px;
      line-height: 1.5;
      color: #4b5563;
    }

    /* Data Tables */
    .data-table-wrapper {
        margin-top: 12px;
        margin-left: 4px;
        margin-right: 4px;
        width: calc(100% - 8px);
    }
    
    /* Cartes mobile - masquées par défaut */
    .sets-cards-mobile {
        display: none;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #d1d5db;
        table-layout: fixed;
        min-width: 0;
    }

    .data-table th {
        background-color: #e0f4fc;
        color: #212121;
        font-size: 12px;
        font-weight: 500;
        padding: 8px 12px;
        text-align: center;
        border: 1px solid #d1d5db;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .data-table td {
        font-size: 12px;
        padding: 8px 12px;
        text-align: center;
        border: 1px solid #d1d5db;
        background-color: #ffffff;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .bold {
      font-weight: bold;
    }

    /* Super Set */
    .superset-label {
      text-align: right;
      margin-bottom: 12px;
      margin-right: 25px;
      font-size: 12px;
      font-weight: bold;
      color: #212121;
    }

    .sub-exercise-table {
      width: 100%;
      margin-bottom: 16px;
      table-layout: fixed;
    }

    .sub-exercise-image-cell {
      width: 100px;
      min-width: 80px;
      vertical-align: top;
      padding-right: 12px;
    }

    .sub-exercise-content-cell {
      vertical-align: top;
      min-width: 0;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }

    /* Footer */
    .footer {
      position: relative;
      width: 100%;
      max-width: 100%;
      background-color: #d5f5f5;
      padding: 12px 20px;
      text-align: center;
      margin-top: 40px;
      box-sizing: border-box;
    }
    
    @media (max-width: 768px) {
      .footer {
        position: relative;
        margin-top: 40px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
      }
    }
    
    @media (min-width: 769px) {
      .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        margin-top: 0;
        z-index: 1000;
        box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
      }
    }

    .footer-content {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .footer-text {
      font-size: 11px;
      color: #212121;
    }

    .footer-text-bold {
      font-weight: bold;
    }

    .footer-image {
      display: inline-block;
      vertical-align: middle;
    }

    .footer-image img {
      height: 30px;
      width: auto;
    }

    /* Scroll to top button */
    .scroll-to-top {
      position: fixed;
      bottom: 100px;
      right: 30px;
      width: 50px;
      height: 50px;
      background-color: #3b82f6;
      color: #ffffff;
      border: none;
      border-radius: 50%;
      cursor: pointer;
      display: none;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 999;
      transition: all 0.3s ease;
    }

    .scroll-to-top:hover {
      background-color: #2563eb;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }

    .scroll-to-top:active {
      transform: translateY(0);
    }

    .scroll-to-top.visible {
      display: flex;
    }

    .scroll-to-top::before {
      content: '↑';
      font-size: 24px;
      line-height: 1;
    }

    @media (max-width: 768px) {
      .scroll-to-top {
        bottom: 70px;
        right: 20px;
        width: 45px;
        height: 45px;
      }
      .scroll-to-top::before {
        font-size: 20px;
      }
    }

    @media print {
      html, body {
        height: auto;
        overflow: visible;
      }
      body {
        display: block;
        background-color: #ffffff;
        padding: 0;
      }
      .scrollable-container {
        overflow: visible;
        padding: 0;
      }
      .main-content {
        box-shadow: none;
        padding: 20px;
      }
      .footer {
        position: relative;
        box-shadow: none;
      }
    }

    /* Tablette */
    @media (max-width: 1024px) and (min-width: 769px) {
      .main-content {
        max-width: 95%;
        padding: 25px;
      }
      .header-title {
        font-size: 26px;
      }
      .exercise-table {
        padding: 18px;
      }
    }

    /* Vue mobile - Cartes au lieu de tableaux */
    @media (max-width: 768px) {
      * {
        max-width: 100%;
      }
      html, body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        margin: 0;
        padding: 0;
      }
      table,
      table tbody,
      table thead,
      table tfoot {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box;
        display: block;
      }
      table tbody,
      table thead {
        width: 100% !important;
        max-width: 100% !important;
      }
      table tbody tr,
      table thead tr,
      table tfoot tr {
        width: 100% !important;
        max-width: 100% !important;
        display: block;
        box-sizing: border-box;
      }
      table tbody td,
      table tbody th,
      table thead td,
      table thead th,
      table tfoot td,
      table tfoot th {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box;
        display: block;
      }
      .scrollable-container {
        padding: 0;
        padding-bottom: 20px;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        box-sizing: border-box;
      }
      .main-content {
        padding: 16px;
        border-radius: 0;
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        box-sizing: border-box;
        position: relative;
      }
      
      /* Header mobile */
      .header-table {
        display: block;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        margin: 0;
      }
      .header-table tr {
        display: block;
        width: 100%;
        max-width: 100%;
      }
      .header-table td {
        display: block;
        width: 100% !important;
        max-width: 100%;
        box-sizing: border-box;
        margin: 0;
        padding-left: 0;
        padding-right: 0;
      }
      .header-title {
        font-size: 22px;
        margin-bottom: 8px;
        line-height: 1.2;
      }
      .header-date {
        text-align: left;
        font-size: 12px;
        color: #6b7280;
        margin-top: 4px;
      }
      .header-line {
        margin: 12px 0;
      }
      .header-info-table {
        display: block;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        margin: 0;
      }
      .header-info-table tr {
        display: block;
        width: 100%;
        max-width: 100%;
      }
      .header-info-table td {
        display: block;
        width: 100% !important;
        max-width: 100%;
        padding: 6px 0;
        font-size: 12px;
        text-align: left !important;
        box-sizing: border-box;
        margin: 0;
      }
      .header-info-table td:last-child {
        font-weight: 600;
        color: #111827;
      }
      
      /* Exercices mobile - vue carte */
      .exercise-table {
        padding: 16px;
        margin-top: 20px;
        margin-left: 0;
        margin-right: 0;
        display: block;
        border-left: 4px solid #3b82f6;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        position: relative;
      }
      .exercise-table tr {
        display: block;
        width: 100%;
        max-width: 100%;
      }
      .exercise-table td {
        display: block;
        width: 100% !important;
        max-width: 100%;
        box-sizing: border-box;
        margin: 0;
        padding-left: 0;
        padding-right: 0;
      }
      .exercise-number-cell {
        width: 100%;
        padding: 0;
        margin-bottom: 24px;
        text-align: center;
      }
      .exercise-number {
        font-size: 28px;
        display: inline-block;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        width: 50px;
        height: 50px;
        margin-bottom: 14px;
        line-height: 50px;
        border-radius: 50%;
        text-align: center;
      }
      .exercise-content-cell {
        width: 100%;
        max-width: 100%;
        padding: 0;
        box-sizing: border-box;
      }
      
      /* Header exercice mobile */
      .exercise-header-table {
        display: block;
        margin-bottom: 16px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
      }
      .exercise-header-table tr {
        display: block;
        width: 100%;
        max-width: 100%;
      }
      .exercise-header-table td {
        display: block;
        width: 100% !important;
        max-width: 100%;
        box-sizing: border-box;
        margin: 0;
        padding-left: 0;
        padding-right: 0;
      }
      .exercise-image-cell {
        width: 100%;
        max-width: 100%;
        padding: 0;
        margin-bottom: 12px;
        text-align: center;
        box-sizing: border-box;
      }
      .exercise-image {
        width: 100%;
        max-width: 280px;
        height: auto;
        min-height: 180px;
        margin: 0 auto;
        border-radius: 8px;
      }
      .exercise-details-cell {
        width: 100%;
        max-width: 100%;
        text-align: center;
        padding: 0;
        box-sizing: border-box;
      }
      .exercise-title {
        font-size: 18px;
        margin-bottom: 8px;
        text-align: center;
      }
      .exercise-description {
        font-size: 12px;
        text-align: center;
        color: #6b7280;
      }
      
      /* Masquer les tableaux sur mobile */
      .data-table-wrapper {
        display: none;
      }
      
      /* Cartes de séries pour mobile */
      .sets-cards-mobile {
        display: block;
        margin-top: 16px;
        margin-left: 0;
        margin-right: 0;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
      }
      .set-card {
        background: #ffffff;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 10px;
        margin-left: 0;
        margin-right: 0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
      }
      .set-data {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 16px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
      }
      .set-data-item {
        display: flex;
        flex-direction: column;
      }
      .set-data-label {
        font-size: 10px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
        font-weight: 600;
      }
      .set-data-value {
        font-size: 16px;
        font-weight: 700;
        color: #111827;
      }
      
      /* Super set mobile */
      .superset-label {
        text-align: center;
        margin: 0 0 16px 0;
        font-size: 12px;
        font-weight: 600;
        color: #3b82f6;
        background: #eff6ff;
        padding: 10px 16px;
        border-radius: 8px;
      }
      
      /* Sous-exercices mobile */
      .sub-exercise-table {
        display: block;
        margin-bottom: 20px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
      }
      .sub-exercise-table tr {
        display: block;
      }
      .sub-exercise-table td {
        display: block;
        width: 100% !important;
      }
      .sub-exercise-image-cell {
        width: 100%;
        max-width: 100%;
        padding: 0;
        margin-bottom: 12px;
        text-align: center;
        box-sizing: border-box;
      }
      .exercise-image-small {
        width: 100%;
        max-width: 200px;
        height: auto;
        min-height: 140px;
        margin: 0 auto;
        border-radius: 8px;
      }
      .sub-exercise-content-cell {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
      }
      .exercise-title-small {
        font-size: 17px;
        margin-bottom: 12px;
        text-align: center;
      }
      
      .footer {
        padding: 12px 16px;
      }
      .footer-content {
        flex-direction: column;
        gap: 6px;
      }
      .footer-text {
        font-size: 10px;
      }
      .scroll-to-top {
        bottom: 70px;
        right: 16px;
        width: 48px;
        height: 48px;
      }
      .scroll-to-top::before {
        font-size: 18px;
      }
    }

    /* Très petits mobiles */
    @media (max-width: 480px) {
      .scrollable-container {
        padding: 10px;
        padding-bottom: 100px;
      }
      .main-content {
        padding: 16px 12px;
      }
      .header-title {
        font-size: 20px;
      }
      .exercise-table {
        padding: 12px 10px;
      }
      .exercise-number {
        font-size: 24px;
      }
      .exercise-title {
        font-size: 16px;
      }
      .exercise-title-small {
        font-size: 15px;
      }
      .data-table th,
      .data-table td {
        padding: 8px 6px;
        font-size: 10px;
      }
      .footer {
        padding: 10px 12px;
      }
      .footer-text {
        font-size: 9px;
      }
      .scroll-to-top {
        bottom: 85px;
        right: 12px;
        width: 44px;
        height: 44px;
      }
    }

    /* Orientation paysage mobile */
    @media (max-width: 768px) and (orientation: landscape) {
      .scrollable-container {
        padding: 15px;
      }
      .main-content {
        padding: 20px;
      }
      .exercise-image {
        max-width: 150px;
        min-height: 100px;
      }
      .exercise-image-small {
        max-width: 120px;
        min-height: 80px;
      }
    }
  </style>
</head>
<body>
  <div class="scrollable-container">
@php
    function formatDuration($duration) {
        if (empty($duration) || $duration === '-') return '-';
        if (strpos($duration, 'minute') !== false || strpos($duration, 'seconde') !== false) return $duration;

        $totalSeconds = 0;
        if (preg_match('/(\d+)\s*min/i', $duration, $m)) $totalSeconds += intval($m[1]) * 60;
        if (preg_match('/(\d+)\s*s/i', $duration, $m))   $totalSeconds += intval($m[1]);
        if ($totalSeconds === 0 && preg_match('/^(\d+)$/', $duration, $m)) {
            $totalSeconds = intval($m[1]);
        }
        if ($totalSeconds === 0) return $duration;

        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;
        $result = '';
        if ($minutes > 0) $result .= $minutes.' minute'.($minutes > 1 ? 's' : '');
        if ($seconds > 0) {
            if ($minutes > 0) $result .= ' ';
            $result .= $seconds.' seconde'.($seconds > 1 ? 's' : '');
        }
        return $result ?: $duration;
    }

    function formatRestTime($restTime) {
        if (empty($restTime) || $restTime === '-') return '-';
        if (strpos($restTime, 'seconde') !== false || strpos($restTime, 'minute') !== false) return $restTime;
        if (preg_match('/^(\d+)$/', $restTime, $m)) {
            $seconds = intval($m[1]);
            return $seconds.' seconde'.($seconds > 1 ? 's' : '');
        }
        return $restTime;
    }

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

  <div class="main-content">
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
        <table class="exercise-table">
          <tr>
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
                      $exerciseImage = $firstMedia->getUrl();
                    }
                  }

                  $setsCount    = $sets->count() > 0 ? $sets->count() : ($sessionExercise->sets_count ?? 1);
                  $useDuration  = $sessionExercise->use_duration ?? false;
                  $useBodyweight= $sessionExercise->use_bodyweight ?? false;
                @endphp

                @if($exercise)
                  <table class="exercise-header-table">
                    <tr>
                      <td class="exercise-image-cell">
                        @if($exerciseImage)
                          <div class="exercise-image">
                            <img src="{{ $exerciseImage }}" alt="{{ $sessionExercise->custom_exercise_name ?? $exercise->title }}">
                          </div>
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
                  
                  <!-- Tableau desktop -->
                  <div class="data-table-wrapper">
                    <table class="data-table">
                      <tr>
                        <th style="width: 70px;">série(s)</th>
                        <th>{{ $useDuration ? 'durée' : 'repets' }}</th>
                        <th>{{ $useBodyweight ? 'Poids de corps' : 'Charge' }}</th>
                        <th>repos</th>
                      </tr>
                    @if($sets->count() > 0)
                      @foreach($sets as $set)
                        @php
                          // Utiliser use_duration et use_bodyweight du set, sinon ceux de l'exercice
                          $setUseDurationRaw = $set->use_duration ?? $sessionExercise->use_duration ?? false;
                          $setUseBodyweightRaw = $set->use_bodyweight ?? $sessionExercise->use_bodyweight ?? false;
                          $setUseDuration = $setUseDurationRaw === true || $setUseDurationRaw === 1 || $setUseDurationRaw === '1' || $setUseDurationRaw === 'true';
                          $setUseBodyweight = $setUseBodyweightRaw === true || $setUseBodyweightRaw === 1 || $setUseBodyweightRaw === '1' || $setUseBodyweightRaw === 'true';
                          
                          $durationOrReps = '-';
                          if ($setUseDuration) {
                            $rawDuration = $set->duration ?? $sessionExercise->duration ?? '-';
                            $durationOrReps = formatDuration($rawDuration);
                            $durationOrReps = 'Durée : ' . $durationOrReps;
                          } else {
                            $durationOrReps = $set->repetitions ?? $sessionExercise->repetitions ?? '-';
                          }

                          $charge = '-';
                          if ($setUseBodyweight) {
                            $charge = 'Poids de corps';
                          } else {
                            $charge = !empty($set->weight) ? number_format($set->weight, 2, '.', '') . ' kg' : ($sessionExercise->weight ? number_format($sessionExercise->weight, 2, '.', '') . ' kg' : '-');
                          }

                          $rest = '-';
                          $rawRest = $set->rest_time ?? $sessionExercise->rest_time ?? '-';
                          $rest = formatRestTime($rawRest);
                        @endphp
                        <tr>
                          <td class="bold">{{ $set->set_number ?? ($loop->iteration) }}</td>
                          <td>{{ $durationOrReps }}</td>
                          <td>{{ $charge }}</td>
                          <td>{{ $rest }}</td>
                        </tr>
                      @endforeach
                    @else
                      @php
                        $durationOrReps = '-';
                        if ($useDuration) {
                          $rawDuration = $sessionExercise->duration ?? '-';
                          $durationOrReps = formatDuration($rawDuration);
                        } else {
                          $durationOrReps = $sessionExercise->repetitions ?? '-';
                        }

                        $charge = '-';
                        if ($useBodyweight) {
                          $charge = 'Poids de corps';
                        } else {
                          $charge = !empty($sessionExercise->weight) ? number_format($sessionExercise->weight, 2, '.', '') . ' kg' : '-';
                        }

                        $rest = '-';
                        $rawRest = $sessionExercise->rest_time ?? '-';
                        $rest = formatRestTime($rawRest);
                      @endphp
                      <tr>
                        <td class="bold">{{ $setsCount }}</td>
                        <td>{{ $durationOrReps }}</td>
                        <td>{{ $charge }}</td>
                        <td>{{ $rest }}</td>
                      </tr>
                    @endif
                    </table>
                  </div>
                  
                  <!-- Cartes mobile -->
                  <div class="sets-cards-mobile">
                    @if($sets->count() > 0)
                      @foreach($sets as $set)
                        @php
                          // Utiliser use_duration et use_bodyweight du set, sinon ceux de l'exercice
                          $setUseDurationRaw = $set->use_duration ?? $sessionExercise->use_duration ?? false;
                          $setUseBodyweightRaw = $set->use_bodyweight ?? $sessionExercise->use_bodyweight ?? false;
                          $setUseDuration = $setUseDurationRaw === true || $setUseDurationRaw === 1 || $setUseDurationRaw === '1' || $setUseDurationRaw === 'true';
                          $setUseBodyweight = $setUseBodyweightRaw === true || $setUseBodyweightRaw === 1 || $setUseBodyweightRaw === '1' || $setUseBodyweightRaw === 'true';
                          
                          $durationOrReps = '-';
                          if ($setUseDuration) {
                            $rawDuration = $set->duration ?? $sessionExercise->duration ?? '-';
                            $durationOrReps = formatDuration($rawDuration);
                            $durationOrReps = 'Durée : ' . $durationOrReps;
                          } else {
                            $durationOrReps = $set->repetitions ?? $sessionExercise->repetitions ?? '-';
                          }

                          $charge = '-';
                          if ($setUseBodyweight) {
                            $charge = 'Poids de corps';
                          } else {
                            $charge = !empty($set->weight) ? number_format($set->weight, 2, '.', '') . ' kg' : ($sessionExercise->weight ? number_format($sessionExercise->weight, 2, '.', '') . ' kg' : '-');
                          }

                          $rest = '-';
                          $rawRest = $set->rest_time ?? $sessionExercise->rest_time ?? '-';
                          $rest = formatRestTime($rawRest);
                        @endphp
                        <div class="set-card">
                          <div class="set-data">
                            <div class="set-data-item">
                              <span class="set-data-label">Série</span>
                              <span class="set-data-value">{{ $set->set_number ?? $loop->iteration }}</span>
                            </div>
                            <div class="set-data-item">
                              <span class="set-data-label">{{ $setUseDuration ? 'Durée' : 'Répétitions' }}</span>
                              <span class="set-data-value">{{ $durationOrReps }}</span>
                            </div>
                            <div class="set-data-item">
                              <span class="set-data-label">{{ $setUseBodyweight ? 'Poids de corps' : 'Charge' }}</span>
                              <span class="set-data-value">{{ $charge }}</span>
                            </div>
                            <div class="set-data-item">
                              <span class="set-data-label">Repos</span>
                              <span class="set-data-value">{{ $rest }}</span>
                            </div>
                          </div>
                        </div>
                      @endforeach
                    @else
                      @php
                        $durationOrReps = '-';
                        if ($useDuration) {
                          $rawDuration = $sessionExercise->duration ?? '-';
                          $durationOrReps = formatDuration($rawDuration);
                        } else {
                          $durationOrReps = $sessionExercise->repetitions ?? '-';
                        }

                        $charge = '-';
                        if ($useBodyweight) {
                          $charge = 'Poids de corps';
                        } else {
                          $charge = !empty($sessionExercise->weight) ? number_format($sessionExercise->weight, 2, '.', '') . ' kg' : '-';
                        }

                        $rest = '-';
                        $rawRest = $sessionExercise->rest_time ?? '-';
                        $rest = formatRestTime($rawRest);
                      @endphp
                      <div class="set-card">
                        <div class="set-data">
                          <div class="set-data-item">
                            <span class="set-data-label">Série</span>
                            <span class="set-data-value">{{ $setsCount }}</span>
                          </div>
                          <div class="set-data-item">
                            <span class="set-data-label">{{ $useDuration ? 'Durée' : 'Répétitions' }}</span>
                            <span class="set-data-value">{{ $durationOrReps }}</span>
                          </div>
                          <div class="set-data-item">
                            <span class="set-data-label">{{ $useBodyweight ? 'Poids de corps' : 'Charge' }}</span>
                            <span class="set-data-value">{{ $charge }}</span>
                          </div>
                            <div class="set-data-item">
                              <span class="set-data-label">Repos</span>
                              <span class="set-data-value">{{ $rest }}</span>
                            </div>
                        </div>
                      </div>
                    @endif
                  </div>
                @endif
              @else
                @php
                  $blockExercises   = $item['exercises'];
                  $firstExercise    = $blockExercises[0];
                  $blockDescription = $firstExercise->additional_description ?? '';
                @endphp

                @if($blockDescription)
                  <div class="superset-label">
                    Super set : {{ $blockDescription }}
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
                        $exerciseImage = $firstMedia->getUrl();
                      }
                    }

                    $setsCount    = $sets->count() > 0 ? $sets->count() : ($sessionExercise->sets_count ?? 1);
                    $useDuration  = $sessionExercise->use_duration ?? false;
                    $useBodyweight= $sessionExercise->use_bodyweight ?? false;
                  @endphp

                  @if($exercise)
                    <table class="sub-exercise-table">
                      <tr>
                        <td class="sub-exercise-image-cell">
                          @if($exerciseImage)
                            <div class="exercise-image-small">
                              <img src="{{ $exerciseImage }}" alt="{{ $sessionExercise->custom_exercise_name ?? $exercise->title }}">
                            </div>
                          @endif
                        </td>
                        <td class="sub-exercise-content-cell">
                          <div class="exercise-title-small">{{ $sessionExercise->custom_exercise_name ?? $exercise->title }}</div>
                          
                          <!-- Tableau desktop -->
                          <div class="data-table-wrapper">
                            <table class="data-table">
                              <tr>
                                <th style="width: 70px;">série(s)</th>
                                <th>{{ $useDuration ? 'durée' : 'repets' }}</th>
                                <th>{{ $useBodyweight ? 'Poids de corps' : 'Charge' }}</th>
                                <th>repos</th>
                              </tr>
                            @if($sets->count() > 0)
                              @foreach($sets as $set)
                                @php
                                  // Utiliser use_duration et use_bodyweight du set, sinon ceux de l'exercice
                                  $setUseDurationRaw = $set->use_duration ?? $sessionExercise->use_duration ?? false;
                                  $setUseBodyweightRaw = $set->use_bodyweight ?? $sessionExercise->use_bodyweight ?? false;
                                  $setUseDuration = $setUseDurationRaw === true || $setUseDurationRaw === 1 || $setUseDurationRaw === '1' || $setUseDurationRaw === 'true';
                                  $setUseBodyweight = $setUseBodyweightRaw === true || $setUseBodyweightRaw === 1 || $setUseBodyweightRaw === '1' || $setUseBodyweightRaw === 'true';
                                  
                                  $durationOrReps = '-';
                                  if ($setUseDuration) {
                                    $rawDuration = $set->duration ?? $sessionExercise->duration ?? '-';
                                    $durationOrReps = formatDuration($rawDuration);
                                    $durationOrReps = 'Durée : ' . $durationOrReps;
                                  } else {
                                    $durationOrReps = $set->repetitions ?? $sessionExercise->repetitions ?? '-';
                                  }

                                  $charge = '-';
                                  if ($setUseBodyweight) {
                                    $charge = 'Poids de corps';
                                  } else {
                                    $charge = !empty($set->weight) ? number_format($set->weight, 2, '.', '') . ' kg' : ($sessionExercise->weight ? number_format($sessionExercise->weight, 2, '.', '') . ' kg' : '-');
                                  }

                                  $rest = '-';
                                  $rawRest = $set->rest_time ?? $sessionExercise->rest_time ?? '-';
                                  $rest = formatRestTime($rawRest);
                                @endphp
                                <tr>
                                  <td class="bold">{{ $set->set_number ?? ($loop->iteration) }}</td>
                                  <td>{{ $durationOrReps }}</td>
                                  <td>{{ $charge }}</td>
                                  <td>{{ $rest }}</td>
                                </tr>
                              @endforeach
                            @else
                              @php
                                $durationOrReps = '-';
                                if ($useDuration) {
                                  $rawDuration = $sessionExercise->duration ?? '-';
                                  $durationOrReps = formatDuration($rawDuration);
                                  $durationOrReps = 'Durée : ' . $durationOrReps;
                                } else {
                                  $durationOrReps = $sessionExercise->repetitions ?? '-';
                                }

                                $charge = '-';
                                if ($useBodyweight) {
                                  $charge = 'Poids de corps';
                                } else {
                                  $charge = !empty($sessionExercise->weight) ? number_format($sessionExercise->weight, 2, '.', '') . ' kg' : '-';
                                }

                                $rest = '-';
                                $rawRest = $sessionExercise->rest_time ?? '-';
                                $rest = formatRestTime($rawRest);
                              @endphp
                              <tr>
                                <td class="bold">{{ $setsCount }}</td>
                                <td>{{ $durationOrReps }}</td>
                                <td>{{ $charge }}</td>
                                <td>{{ $rest }}</td>
                              </tr>
                            @endif
                            </table>
                          </div>
                          
                          <!-- Cartes mobile -->
                          <div class="sets-cards-mobile">
                            @if($sets->count() > 0)
                              @foreach($sets as $set)
                                @php
                                  // Utiliser use_duration et use_bodyweight du set, sinon ceux de l'exercice
                                  $setUseDurationRaw = $set->use_duration ?? $sessionExercise->use_duration ?? false;
                                  $setUseBodyweightRaw = $set->use_bodyweight ?? $sessionExercise->use_bodyweight ?? false;
                                  $setUseDuration = $setUseDurationRaw === true || $setUseDurationRaw === 1 || $setUseDurationRaw === '1' || $setUseDurationRaw === 'true';
                                  $setUseBodyweight = $setUseBodyweightRaw === true || $setUseBodyweightRaw === 1 || $setUseBodyweightRaw === '1' || $setUseBodyweightRaw === 'true';
                                  
                                  $durationOrReps = '-';
                                  if ($setUseDuration) {
                                    $rawDuration = $set->duration ?? $sessionExercise->duration ?? '-';
                                    $durationOrReps = formatDuration($rawDuration);
                                    $durationOrReps = 'Durée : ' . $durationOrReps;
                                  } else {
                                    $durationOrReps = $set->repetitions ?? $sessionExercise->repetitions ?? '-';
                                  }

                                  $charge = '-';
                                  if ($setUseBodyweight) {
                                    $charge = 'Poids de corps';
                                  } else {
                                    $charge = !empty($set->weight) ? number_format($set->weight, 2, '.', '') . ' kg' : ($sessionExercise->weight ? number_format($sessionExercise->weight, 2, '.', '') . ' kg' : '-');
                                  }

                                  $rest = '-';
                                  $rawRest = $set->rest_time ?? $sessionExercise->rest_time ?? '-';
                                  $rest = formatRestTime($rawRest);
                                @endphp
                                <div class="set-card">
                                  <div class="set-data">
                                    <div class="set-data-item">
                                      <span class="set-data-label">Série</span>
                                      <span class="set-data-value">{{ $set->set_number ?? $loop->iteration }}</span>
                                    </div>
                                    <div class="set-data-item">
                                      <span class="set-data-label">{{ $setUseDuration ? 'Durée' : 'Répétitions' }}</span>
                                      <span class="set-data-value">{{ $durationOrReps }}</span>
                                    </div>
                                    <div class="set-data-item">
                                      <span class="set-data-label">{{ $setUseBodyweight ? 'Poids de corps' : 'Charge' }}</span>
                                      <span class="set-data-value">{{ $charge }}</span>
                                    </div>
                                    <div class="set-data-item">
                                      <span class="set-data-label">Repos</span>
                                      <span class="set-data-value">{{ $rest }}</span>
                                    </div>
                                  </div>
                                </div>
                              @endforeach
                            @else
                              @php
                                $durationOrReps = '-';
                                if ($useDuration) {
                                  $rawDuration = $sessionExercise->duration ?? '-';
                                  $durationOrReps = formatDuration($rawDuration);
                                  $durationOrReps = 'Durée : ' . $durationOrReps;
                                } else {
                                  $durationOrReps = $sessionExercise->repetitions ?? '-';
                                }

                                $charge = '-';
                                if ($useBodyweight) {
                                  $charge = 'Poids de corps';
                                } else {
                                  $charge = !empty($sessionExercise->weight) ? number_format($sessionExercise->weight, 2, '.', '') . ' kg' : '-';
                                }

                                $rest = '-';
                                $rawRest = $sessionExercise->rest_time ?? '-';
                                $rest = formatRestTime($rawRest);
                              @endphp
                              <div class="set-card">
                                <div class="set-data">
                                  <div class="set-data-item">
                                    <span class="set-data-label">Série</span>
                                    <span class="set-data-value">{{ $setsCount }}</span>
                                  </div>
                                  <div class="set-data-item">
                                    <span class="set-data-label">{{ $useDuration ? 'Durée' : 'Répétitions' }}</span>
                                    <span class="set-data-value">{{ $durationOrReps }}</span>
                                  </div>
                                  <div class="set-data-item">
                                    <span class="set-data-label">{{ $useBodyweight ? 'Poids de corps' : 'Charge' }}</span>
                                    <span class="set-data-value">{{ $charge }}</span>
                                  </div>
                                    <div class="set-data-item">
                                      <span class="set-data-label">Repos</span>
                                      <span class="set-data-value">{{ $rest }}</span>
                                    </div>
                                </div>
                              </div>
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
    <div class="footer-content">
      <span class="footer-text-bold">Fitnessclic.com</span>
      <span class="footer-text">création de séances personnalisées en quelques clics.</span>
      @php
        $logoPath = public_path('assets/logo_fitnessclic.png');
        if (!file_exists($logoPath)) {
          $logoPath = base_path('public/assets/logo_fitnessclic.png');
        }
      @endphp
      @if(file_exists($logoPath))
        <span class="footer-image">
          <img src="{{ asset('assets/logo_fitnessclic.png') }}" alt="FitnessClic">
        </span>
      @endif
    </div>
  </div>
  </div>

  <!-- Scroll to top button -->
  <button class="scroll-to-top" id="scrollToTop" aria-label="Retour en haut de page"></button>

  <script>
    (function() {
      const scrollToTopButton = document.getElementById('scrollToTop');
      const scrollableContainer = document.querySelector('.scrollable-container');

      function toggleScrollButton() {
        if (scrollableContainer.scrollTop > 300) {
          scrollToTopButton.classList.add('visible');
        } else {
          scrollToTopButton.classList.remove('visible');
        }
      }

      scrollableContainer.addEventListener('scroll', toggleScrollButton);

      scrollToTopButton.addEventListener('click', function() {
        scrollableContainer.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      });

      // Vérifier l'état initial
      toggleScrollButton();
    })();
  </script>
</body>
</html>
