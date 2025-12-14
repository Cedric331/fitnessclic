<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>Séance - {{ $session->name ?? 'Nouvelle Séance' }}</title>
  <style>
    @page {
      margin: 0;
      size: {{ $layout->canvas_width * 0.75 }}pt {{ $layout->canvas_height * 0.75 }}pt;
    }
    
    body {
      font-family: DejaVu Sans, sans-serif;
      margin: 0;
      padding: 0;
      width: {{ $layout->canvas_width * 0.75 }}pt;
      height: {{ $layout->canvas_height * 0.75 }}pt;
      position: relative;
      background-color: #ffffff;
      overflow: hidden;
    }

    .layout-element {
      position: absolute;
    }

    .layout-text {
      font-size: 12px;
      color: #000000;
    }

    .layout-image {
      max-width: 100%;
      height: auto;
    }

    .layout-rect {
      border: 2px solid #000000;
      background-color: transparent;
    }

    .layout-ellipse {
      border: 2px solid #000000;
      background-color: transparent;
      border-radius: 50%;
    }

    .layout-line {
      border: none;
      background-color: #000000;
    }
  </style>
</head>
<body>
  @php
    // Utiliser layoutData passé depuis le contrôleur, ou décoder depuis layout
    $layoutData = $layoutData ?? [];
    if (empty($layoutData) && isset($layout->layout_data)) {
      if (is_string($layout->layout_data)) {
        $decoded = json_decode($layout->layout_data, true);
        $layoutData = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
      } elseif (is_array($layout->layout_data)) {
        $layoutData = $layout->layout_data;
      } else {
        $layoutData = [];
      }
    }
    
    $scale = 0.75; // Conversion px vers points
    $footerHeight = 60;
    $footerY = $layout->canvas_height - $footerHeight;
    $canvasWidth = $layout->canvas_width;
    
    // Recalculer les positions du footer
    $footerTextNode = null;
    $footerLogoNode = null;
    $footerRect = null;
    
    foreach ($layoutData as &$element) {
      if (!is_array($element) || !isset($element['id'])) {
        continue;
      }
      
      $elementId = $element['id'] ?? '';
      
      // Recalculer le rectangle de fond du footer
      if (strpos($elementId, 'footer-bg') !== false) {
        $element['x'] = 0; // Toujours à x = 0
        $element['y'] = $footerY; // En bas du canvas
        $element['width'] = $canvasWidth; // Toute la largeur
        $element['height'] = $footerHeight;
        $footerRect = &$element;
      }
      
      // Recalculer le texte du footer
      if (strpos($elementId, 'footer-text') !== false) {
        $element['x'] = $canvasWidth / 2;
        $element['y'] = $footerY + $footerHeight / 2;
        $footerTextNode = &$element;
      }
      
      // Recalculer le logo du footer
      if (strpos($elementId, 'footer-logo') !== false) {
        $footerLogoNode = &$element;
      }
    }
    unset($element);
    
    // Ajuster la position du logo par rapport au texte
    if ($footerTextNode && $footerLogoNode) {
      // Estimer la largeur du texte (approximatif)
      $textLength = strlen($footerTextNode['text'] ?? '');
      $fontSize = $footerTextNode['fontSize'] ?? 12;
      $estimatedTextWidth = $textLength * ($fontSize * 0.6); // Approximation
      $textX = $canvasWidth / 2;
      $textRight = $textX + $estimatedTextWidth / 2;
      $newLogoX = $textRight + 15;
      $footerLogoNode['x'] = $newLogoX;
      $logoHeight = $footerLogoNode['height'] ?? 30;
      $footerLogoNode['y'] = $footerY + ($footerHeight - $logoHeight) / 2;
    }
  @endphp

  @if(empty($layoutData))
    <div style="padding: 50px; text-align: center; color: #666;">
      <p>Aucun contenu dans cette séance libre.</p>
    </div>
  @else
    @foreach($layoutData as $element)
    @if(!is_array($element))
      @continue
    @endif
    @if($element['type'] === 'text')
      @php
        $isFooterText = isset($element['id']) && strpos($element['id'], 'footer-text') !== false;
        $textX = $element['x'] ?? 0;
        $textY = $element['y'] ?? 0;
        $textWidth = isset($element['width']) && $element['width'] ? ($element['width'] * $scale) . 'pt' : 'auto';
        $textHeight = isset($element['height']) && $element['height'] ? ($element['height'] * $scale) . 'pt' : 'auto';
        
        // Pour le footer, ajuster la position pour le centrage
        if ($isFooterText) {
          // Le x est déjà centré (canvasWidth / 2), on utilise transform pour centrer parfaitement
          $textStyle = 'text-align: center; transform: translate(-50%, -50%);';
        } else {
          $textStyle = '';
        }
      @endphp
      <div class="layout-element layout-text" style="
        left: {{ $textX * $scale }}pt;
        top: {{ $textY * $scale }}pt;
        width: {{ $textWidth }};
        height: {{ $textHeight }};
        font-size: {{ ($element['fontSize'] ?? 16) * $scale }}pt;
        font-family: {{ $element['fontFamily'] ?? 'Arial' }};
        color: {{ $element['fill'] ?? '#000000' }};
        opacity: {{ $element['opacity'] ?? 1 }};
        white-space: pre-wrap;
        word-wrap: break-word;
        {{ $textStyle }}
      ">
        {!! htmlspecialchars($element['text'] ?? '', ENT_QUOTES, 'UTF-8') !!}
      </div>
    @elseif($element['type'] === 'image' && isset($element['imageUrl']))
      @php
        $scale = 0.75; // Conversion px vers points
        $imageUrl = $element['imageUrl'];
        // Convertir les URLs relatives en absolues si nécessaire
        if (!str_starts_with($imageUrl, 'http://') && !str_starts_with($imageUrl, 'https://')) {
          if (str_starts_with($imageUrl, '/')) {
            $imageUrl = url($imageUrl);
          } else {
            $imageUrl = asset($imageUrl);
          }
        }
      @endphp
      <img src="{{ $imageUrl }}" class="layout-element layout-image" style="
        left: {{ ($element['x'] ?? 0) * $scale }}pt;
        top: {{ ($element['y'] ?? 0) * $scale }}pt;
        width: {{ isset($element['width']) && $element['width'] ? ($element['width'] * $scale) . 'pt' : 'auto' }};
        height: {{ isset($element['height']) && $element['height'] ? ($element['height'] * $scale) . 'pt' : 'auto' }};
        opacity: {{ $element['opacity'] ?? 1 }};
      " alt="" />
    @elseif($element['type'] === 'rect' || $element['type'] === 'highlight')
      @php
        $isFooterBg = isset($element['id']) && strpos($element['id'], 'footer-bg') !== false;
      @endphp
      <div class="layout-element layout-rect" style="
        left: {{ ($element['x'] ?? 0) * $scale }}pt;
        top: {{ ($element['y'] ?? 0) * $scale }}pt;
        width: {{ ($element['width'] ?? 100) * $scale }}pt;
        height: {{ ($element['height'] ?? 100) * $scale }}pt;
        @if($isFooterBg)
        border: none;
        background-color: {{ $element['fill'] ?? '#E3F2FD' }};
        @else
        border: {{ ($element['strokeWidth'] ?? 2) * $scale }}pt solid {{ $element['stroke'] ?? '#000000' }};
        background-color: {{ $element['type'] === 'highlight' ? '#FFFF00' : ($element['fill'] ?? 'transparent') }};
        @endif
        opacity: {{ $element['type'] === 'highlight' ? 0.3 : ($element['opacity'] ?? 1) }};
        @if(!$isFooterBg)
        transform: rotate({{ $element['rotation'] ?? 0 }}deg);
        @endif
      "></div>
    @elseif($element['type'] === 'ellipse')
      @php
        $radiusX = $element['radiusX'] ?? 50;
        $radiusY = $element['radiusY'] ?? 50;
        $centerX = $element['x'] ?? 0;
        $centerY = $element['y'] ?? 0;
        // Dans Konva, l'ellipse est dans un groupe positionné à (x - radiusX, y - radiusY)
        // L'ellipse elle-même est positionnée à (radiusX, radiusY) dans le groupe
        // Position du coin supérieur gauche du rectangle englobant
        $ellipseX = ($centerX - $radiusX) * $scale;
        $ellipseY = ($centerY - $radiusY) * $scale;
        $ellipseWidth = ($radiusX * 2) * $scale;
        $ellipseHeight = ($radiusY * 2) * $scale;
      @endphp
      <svg class="layout-element" style="
        left: {{ $ellipseX }}pt;
        top: {{ $ellipseY }}pt;
        width: {{ $ellipseWidth }}pt;
        height: {{ $ellipseHeight }}pt;
        position: absolute;
        @if(($element['rotation'] ?? 0) != 0)
        transform: rotate({{ $element['rotation'] }}deg);
        transform-origin: {{ $radiusX * $scale }}pt {{ $radiusY * $scale }}pt;
        @endif
      " xmlns="http://www.w3.org/2000/svg">
        <ellipse 
          cx="{{ $radiusX * $scale }}pt"
          cy="{{ $radiusY * $scale }}pt"
          rx="{{ $radiusX * $scale }}pt"
          ry="{{ $radiusY * $scale }}pt"
          fill="{{ isset($element['fill']) && $element['fill'] ? $element['fill'] : 'none' }}"
          stroke="{{ $element['stroke'] ?? '#000000' }}"
          stroke-width="{{ ($element['strokeWidth'] ?? 2) * $scale }}pt"
          opacity="{{ $element['opacity'] ?? 1 }}"
        />
      </svg>
    @elseif($element['type'] === 'line' && isset($element['points']) && is_array($element['points']) && count($element['points']) >= 4)
      @php
        $scale = 0.75; // Conversion px vers points
        // Pour les lignes, créer un SVG simple
        $points = $element['points'];
        $xCoords = [];
        $yCoords = [];
        for ($i = 0; $i < count($points); $i += 2) {
          if (isset($points[$i])) $xCoords[] = $points[$i];
          if (isset($points[$i+1])) $yCoords[] = $points[$i+1];
        }
        $minX = !empty($xCoords) ? min($xCoords) : 0;
        $minY = !empty($yCoords) ? min($yCoords) : 0;
        $maxX = !empty($xCoords) ? max($xCoords) : 100;
        $maxY = !empty($yCoords) ? max($yCoords) : 100;
        $width = max(1, $maxX - $minX);
        $height = max(1, $maxY - $minY);
        $pointsString = '';
        for ($i = 0; $i < count($points); $i += 2) {
          if (isset($points[$i]) && isset($points[$i+1])) {
            $pointsString .= (($points[$i] - $minX) * $scale) . ',' . (($points[$i+1] - $minY) * $scale) . ' ';
          }
        }
      @endphp
      <svg class="layout-element" style="
        left: {{ $minX * $scale }}pt;
        top: {{ $minY * $scale }}pt;
        width: {{ $width * $scale }}pt;
        height: {{ $height * $scale }}pt;
        position: absolute;
      " xmlns="http://www.w3.org/2000/svg">
        <polyline 
          points="{{ trim($pointsString) }}"
          fill="none"
          stroke="{{ $element['stroke'] ?? '#000000' }}"
          stroke-width="{{ ($element['strokeWidth'] ?? 2) * $scale }}"
          opacity="{{ $element['opacity'] ?? 1 }}"
        />
      </svg>
    @elseif($element['type'] === 'arrow' && isset($element['points']) && is_array($element['points']) && count($element['points']) >= 4)
      @php
        // Pour les flèches, créer un SVG avec la pointe dessinée manuellement
        $points = $element['points'];
        $xCoords = [];
        $yCoords = [];
        for ($i = 0; $i < count($points); $i += 2) {
          if (isset($points[$i])) $xCoords[] = $points[$i];
          if (isset($points[$i+1])) $yCoords[] = $points[$i+1];
        }
        $minX = !empty($xCoords) ? min($xCoords) : 0;
        $minY = !empty($yCoords) ? min($yCoords) : 0;
        $maxX = !empty($xCoords) ? max($xCoords) : 100;
        $maxY = !empty($yCoords) ? max($yCoords) : 100;
        $width = max(1, $maxX - $minX);
        $height = max(1, $maxY - $minY);
        
        // Construire la chaîne de points pour la ligne (coordonnées relatives au SVG)
        $pointsString = '';
        for ($i = 0; $i < count($points); $i += 2) {
          if (isset($points[$i]) && isset($points[$i+1])) {
            $pointsString .= (($points[$i] - $minX) * $scale) . ',' . (($points[$i+1] - $minY) * $scale) . ' ';
          }
        }
        
        // Calculer la pointe de la flèche (dernier point)
        $pointCount = count($points);
        $arrowPoints = '';
        if ($pointCount >= 4 && trim($pointsString)) {
          // Coordonnées dans le système SVG (relatives au minX, minY)
          $lastX = ($points[$pointCount - 2] - $minX) * $scale;
          $lastY = ($points[$pointCount - 1] - $minY) * $scale;
          
          // Trouver le point précédent pour calculer la direction
          $prevX = $lastX;
          $prevY = $lastY;
          if ($pointCount >= 6) {
            $prevX = ($points[$pointCount - 4] - $minX) * $scale;
            $prevY = ($points[$pointCount - 3] - $minY) * $scale;
          } elseif ($pointCount >= 4) {
            // Si seulement 2 points, utiliser le premier point
            $prevX = ($points[0] - $minX) * $scale;
            $prevY = ($points[1] - $minY) * $scale;
          }
          
          // Calculer l'angle de la flèche (direction du dernier segment)
          $dx = $lastX - $prevX;
          $dy = $lastY - $prevY;
          $distance = sqrt($dx * $dx + $dy * $dy);
          
          if ($distance > 0.1) {
            $angle = atan2($dy, $dx);
            
            $arrowLength = 15 * $scale;
            $arrowWidth = 10 * $scale;
            
            // Points de la pointe de flèche (triangle)
            // La pointe est au dernier point
            $arrowTipX = $lastX;
            $arrowTipY = $lastY;
            
            // Calculer les deux autres points du triangle
            // Point gauche (perpendiculaire à gauche de la direction)
            $arrowLeftX = $arrowTipX - $arrowLength * cos($angle) - $arrowWidth * sin($angle);
            $arrowLeftY = $arrowTipY - $arrowLength * sin($angle) + $arrowWidth * cos($angle);
            
            // Point droit (perpendiculaire à droite de la direction)
            $arrowRightX = $arrowTipX - $arrowLength * cos($angle) + $arrowWidth * sin($angle);
            $arrowRightY = $arrowTipY - $arrowLength * sin($angle) - $arrowWidth * cos($angle);
            
            $arrowPoints = $arrowLeftX . ',' . $arrowLeftY . ' ' . $arrowTipX . ',' . $arrowTipY . ' ' . $arrowRightX . ',' . $arrowRightY;
          }
        }
      @endphp
      @if(trim($pointsString))
      <svg class="layout-element" style="
        left: {{ $minX * $scale }}pt;
        top: {{ $minY * $scale }}pt;
        width: {{ max($width * $scale + 30, 30) }}pt;
        height: {{ max($height * $scale + 30, 30) }}pt;
        position: absolute;
        overflow: visible;
      " xmlns="http://www.w3.org/2000/svg">
        <polyline 
          points="{{ trim($pointsString) }}"
          fill="none"
          stroke="{{ $element['stroke'] ?? '#000000' }}"
          stroke-width="{{ ($element['strokeWidth'] ?? 2) * $scale }}pt"
          opacity="{{ $element['opacity'] ?? 1 }}"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
        @if($arrowPoints)
        <polygon 
          points="{{ $arrowPoints }}"
          fill="{{ $element['fill'] ?? $element['stroke'] ?? '#000000' }}"
          stroke="none"
          opacity="{{ $element['opacity'] ?? 1 }}"
        />
        @endif
      </svg>
      @endif
    @endif
    @endforeach
  @endif
</body>
</html>

