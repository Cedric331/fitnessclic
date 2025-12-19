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
      margin: 0;
      padding: 0;
    }
    
    body.pdf-view {
      background-color: #525252;
      overflow: hidden;
      height: 100vh;
    }

    .main-content {
      max-width: 100%;
      width: 100%;
      background-color: #ffffff;
      padding: 30px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      box-sizing: border-box;
    }
    
    @media (min-width: 769px) {
      .main-content {
        max-width: 800px;
      }
    }

    .session-header {
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid #e5e7eb;
    }

    .session-header h1 {
      font-size: 28px;
      font-weight: bold;
      color: #212121;
      margin-bottom: 10px;
    }

    .session-header p {
      color: #6b7280;
      font-size: 14px;
    }

    .layout-container {
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 400px;
      background-color: #ffffff;
    }

    #layout-canvas {
      max-width: 100%;
      height: auto;
    }

    .pdf-container {
      width: 100%;
      height: 100vh;
      background-color: #525252;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      box-sizing: border-box;
      overflow: hidden;
    }

    .pdf-canvas-container {
      width: 100%;
      height: 100%;
      max-width: 100%;
      max-height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 20px;
      overflow: auto;
    }

    .pdf-canvas {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      background-color: #ffffff;
      max-width: 100%;
      max-height: 100%;
      width: auto;
      height: auto;
      display: block;
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/konva@9/konva.min.js"></script>
</head>
<body>
  @if($layout->pdf_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($layout->pdf_path))
    {{-- Afficher le PDF stocké en plein écran --}}
    <script>
      document.body.classList.add('pdf-view');
    </script>
    <div class="pdf-container">
      <div class="pdf-canvas-container" id="pdf-container"></div>
    </div>
  @else
    {{-- Fallback : générer avec Konva --}}
    <div class="main-content">
      <div class="session-header">
        <h1>{{ $session->name ?? 'Nouvelle Séance' }}</h1>
        @if($session->session_date)
          <p>Date : {{ \Carbon\Carbon::parse($session->session_date)->format('d/m/Y') }}</p>
        @endif
        @if(isset($customer) && $customer)
          <p>Client : {{ $customer->full_name ?? ($customer->first_name . ' ' . $customer->last_name) }}</p>
        @endif
        @if($session->notes)
          <p style="margin-top: 10px; color: #92400e; background-color: #fef3c7; padding: 10px; border-radius: 4px;">
            Note : {{ $session->notes }}
          </p>
        @endif
      </div>

      <div class="layout-container">
        <div id="layout-canvas"></div>
      </div>
    </div>
  @endif

  @if($layout->pdf_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($layout->pdf_path))
  {{-- Charger et afficher le PDF avec PDF.js --}}
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
  <script>
    (async function() {
      // Configurer PDF.js
      pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
      
      const pdfUrl = '{{ route("public.session.pdf", ["shareToken" => $session->share_token]) }}';
      const container = document.getElementById('pdf-container');
      
      try {
        // Charger le PDF
        const loadingTask = pdfjsLib.getDocument(pdfUrl);
        const pdf = await loadingTask.promise;
        
        // Calculer la hauteur disponible (hauteur de l'écran moins les paddings)
        const availableHeight = window.innerHeight - 40; // 20px de padding en haut et en bas
        const availableWidth = window.innerWidth - 40; // 20px de padding de chaque côté
        
        // Pour chaque page du PDF
        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
          const page = await pdf.getPage(pageNum);
          
          // Obtenir les dimensions de la page
          const viewport = page.getViewport({ scale: 1.0 });
          
          // Calculer l'échelle pour s'adapter à la largeur
          const scaleByWidth = availableWidth / viewport.width;
          
          // Calculer l'échelle pour s'adapter à la hauteur
          const scaleByHeight = availableHeight / viewport.height;
          
          // Utiliser la plus petite échelle pour que le PDF tienne dans l'écran
          const scale = Math.min(scaleByWidth, scaleByHeight);
          
          const scaledViewport = page.getViewport({ scale: scale });
          
          // Créer un canvas pour la page
          const canvas = document.createElement('canvas');
          const context = canvas.getContext('2d');
          canvas.height = scaledViewport.height;
          canvas.width = scaledViewport.width;
          canvas.className = 'pdf-canvas';
          
          // Rendre la page sur le canvas
          await page.render({
            canvasContext: context,
            viewport: scaledViewport
          }).promise;
          
          // Ajouter le canvas au conteneur
          container.appendChild(canvas);
        }
      } catch (error) {
        console.error('Erreur lors du chargement du PDF:', error);
        container.innerHTML = '<p style="color: white; text-align: center; padding: 20px;">Erreur lors du chargement du PDF</p>';
      }
    })();
  </script>
  @else
  <script>
    const layoutData = @json($layout->layout_data ?? []);
    const canvasWidth = {{ $layout->canvas_width ?? 800 }};
    const canvasHeight = {{ $layout->canvas_height ?? 1140 }};

    // Ajuster la taille du canvas pour qu'il s'adapte à l'écran
    const container = document.getElementById('layout-canvas');
    const maxWidth = container.parentElement.offsetWidth - 60; // padding
    const scale = Math.min(1, maxWidth / canvasWidth);
    const displayWidth = canvasWidth * scale;
    const displayHeight = canvasHeight * scale;

    const stage = new Konva.Stage({
      container: 'layout-canvas',
      width: displayWidth,
      height: displayHeight,
    });

    const layer = new Konva.Layer();
    stage.add(layer);

    // Fonction pour charger une image
    function loadImage(url) {
      return new Promise((resolve, reject) => {
        const imageObj = new Image();
        imageObj.crossOrigin = 'anonymous';
        imageObj.onload = () => resolve(imageObj);
        imageObj.onerror = reject;
        imageObj.src = url;
      });
    }

    // Variables pour stocker les références du footer
    let footerTextNode = null;
    let footerLogoNode = null;
    let footerRect = null;

    // Fonction pour recalculer la position du footer
    function recalculateFooterPosition() {
      const footerHeight = 60;
      const footerY = canvasHeight - footerHeight;

      // Recalculer la position du texte du footer (en utilisant les dimensions originales)
      if (footerTextNode) {
        footerTextNode.x((canvasWidth / 2) * scale);
        footerTextNode.y((footerY + footerHeight / 2) * scale);
        footerTextNode.offsetX(footerTextNode.width() / 2);
        footerTextNode.offsetY(footerTextNode.height() / 2);
      }

      // Recalculer la position du logo du footer
      if (footerTextNode && footerLogoNode) {
        const textWidth = footerTextNode.width();
        const textX = (canvasWidth / 2) * scale;
        const textRight = textX + textWidth / 2;
        const newLogoX = textRight + (15 * scale);
        footerLogoNode.x(newLogoX);
        const logoHeight = footerLogoNode.height();
        footerLogoNode.y((footerY + (footerHeight - logoHeight) / 2) * scale);
      }

      // Recalculer la position du rectangle de fond du footer
      if (footerRect) {
        footerRect.x(0);
        footerRect.y(footerY * scale);
        footerRect.width(canvasWidth * scale);
        footerRect.height(footerHeight * scale);
      }

      layer.draw();
    }

    // Charger tous les éléments
    async function loadElements() {
      for (const element of layoutData) {
        try {
          let konvaShape = null;
          const elementId = element.id || '';

          switch (element.type) {
            case 'text':
              konvaShape = new Konva.Text({
                x: (element.x || 0) * scale,
                y: (element.y || 0) * scale,
                text: element.text || '',
                fontSize: (element.fontSize || 16) * scale,
                fontFamily: element.fontFamily || 'Arial',
                fill: element.fill || '#000000',
                opacity: element.opacity !== undefined ? element.opacity : 1,
                width: element.width ? element.width * scale : undefined,
                height: element.height ? element.height * scale : undefined,
              });
              
              // Si c'est le texte du footer, stocker la référence et appliquer le centrage
              if (elementId.includes('footer-text')) {
                footerTextNode = konvaShape;
                // Appliquer les offsets pour centrer le texte
                layer.add(konvaShape);
                layer.draw();
                konvaShape.offsetX(konvaShape.width() / 2);
                konvaShape.offsetY(konvaShape.height() / 2);
                continue; // Ne pas ajouter deux fois
              }
              break;

            case 'image':
              if (element.imageUrl) {
                try {
                  const imageObj = await loadImage(element.imageUrl);
                  const imageWidth = (element.width || imageObj.width) * scale;
                  const imageHeight = (element.height || imageObj.height) * scale;
                  
                  // Créer un groupe pour contenir l'image et éventuellement le cadre
                  const imageGroup = new Konva.Group({
                    x: (element.x || 0) * scale,
                    y: (element.y || 0) * scale,
                    rotation: element.rotation || 0,
                  });
                  
                  const konvaImage = new Konva.Image({
                    x: 0,
                    y: 0,
                    image: imageObj,
                    width: imageWidth,
                    height: imageHeight,
                    opacity: element.opacity !== undefined ? element.opacity : 1,
                  });
                  
                  // Appliquer l'ombre si nécessaire
                  if (element.exerciseData && element.exerciseData.imageShadow) {
                    const shadowColor = element.exerciseData.imageShadowColor || '#000000';
                    const shadowBlur = (element.exerciseData.imageShadowBlur || 10) * scale;
                    const shadowOffsetX = (element.exerciseData.imageShadowOffsetX || 5) * scale;
                    const shadowOffsetY = (element.exerciseData.imageShadowOffsetY || 5) * scale;
                    const shadowOpacity = element.exerciseData.imageShadowOpacity !== undefined ? element.exerciseData.imageShadowOpacity : 0.5;
                    
                    konvaImage.shadowColor(shadowColor);
                    konvaImage.shadowBlur(shadowBlur);
                    konvaImage.shadowOffsetX(shadowOffsetX);
                    konvaImage.shadowOffsetY(shadowOffsetY);
                    konvaImage.shadowOpacity(shadowOpacity);
                  }
                  
                  imageGroup.add(konvaImage);
                  
                  // Ajouter le cadre si nécessaire
                  if (element.exerciseData && element.exerciseData.imageFrame) {
                    const frameColor = element.exerciseData.imageFrameColor || '#000000';
                    const frameWidth = (element.exerciseData.imageFrameWidth || 2) * scale;
                    
                    const imageFrame = new Konva.Rect({
                      x: 0,
                      y: 0,
                      width: imageWidth,
                      height: imageHeight,
                      fill: undefined,
                      stroke: frameColor,
                      strokeWidth: frameWidth,
                      cornerRadius: 0,
                    });
                    
                    imageFrame.moveToBottom();
                    imageGroup.add(imageFrame);
                  }
                  
                  konvaShape = imageGroup;
                  
                  // Si c'est le logo du footer, stocker la référence
                  if (elementId.includes('footer-logo')) {
                    footerLogoNode = konvaImage;
                  }
                } catch (e) {
                  console.error('Error loading image:', e);
                }
              }
              break;

            case 'rect':
            case 'highlight':
              konvaShape = new Konva.Rect({
                x: (element.x || 0) * scale,
                y: (element.y || 0) * scale,
                width: (element.width || 100) * scale,
                height: (element.height || 100) * scale,
                fill: element.type === 'highlight' ? '#FFFF00' : (element.fill || undefined),
                opacity: element.type === 'highlight' ? 0.3 : (element.opacity !== undefined ? element.opacity : 1),
                stroke: element.type === 'highlight' ? undefined : (element.stroke !== undefined ? element.stroke : '#000000'),
                strokeWidth: (element.strokeWidth !== undefined ? element.strokeWidth : 2) * scale,
                rotation: element.rotation || 0,
              });
              
              // Si c'est le rectangle de fond du footer, stocker la référence
              if (elementId.includes('footer-bg')) {
                footerRect = konvaShape;
              }
              break;

            case 'ellipse':
              const radiusX = (element.radiusX !== undefined ? element.radiusX : 50) * scale;
              const radiusY = (element.radiusY !== undefined ? element.radiusY : 50) * scale;
              const ellipseGroup = new Konva.Group({
                x: ((element.x || 0) - (element.radiusX || 50)) * scale,
                y: ((element.y || 0) - (element.radiusY || 50)) * scale,
                rotation: element.rotation || 0,
              });
              const ellipseShape = new Konva.Ellipse({
                x: radiusX,
                y: radiusY,
                radiusX: radiusX,
                radiusY: radiusY,
                fill: element.fill || undefined,
                stroke: element.stroke !== undefined ? element.stroke : '#000000',
                strokeWidth: (element.strokeWidth !== undefined ? element.strokeWidth : 2) * scale,
                opacity: element.opacity !== undefined ? element.opacity : 1,
              });
              ellipseGroup.add(ellipseShape);
              konvaShape = ellipseGroup;
              break;

            case 'line':
              if (element.points && element.points.length >= 4) {
                const scaledPoints = element.points.map((p, i) => i % 2 === 0 ? p * scale : p * scale);
                konvaShape = new Konva.Line({
                  points: scaledPoints,
                  stroke: element.stroke !== undefined ? element.stroke : '#000000',
                  strokeWidth: (element.strokeWidth !== undefined ? element.strokeWidth : 2) * scale,
                  opacity: element.opacity !== undefined ? element.opacity : 1,
                  lineCap: 'round',
                  lineJoin: 'round',
                });
              }
              break;

            case 'arrow':
              if (element.points && element.points.length >= 4) {
                const scaledPoints = element.points.map((p, i) => i % 2 === 0 ? p * scale : p * scale);
                konvaShape = new Konva.Arrow({
                  points: scaledPoints,
                  stroke: element.stroke !== undefined ? element.stroke : '#000000',
                  strokeWidth: (element.strokeWidth !== undefined ? element.strokeWidth : 2) * scale,
                  fill: element.fill || element.stroke || '#000000',
                  opacity: element.opacity !== undefined ? element.opacity : 1,
                  pointerLength: (element.pointerLength || 10) * scale,
                  pointerWidth: (element.pointerWidth || 10) * scale,
                });
              }
              break;
          }

          if (konvaShape) {
            if (element.id) {
              konvaShape.id(element.id);
            }
            layer.add(konvaShape);
          }
        } catch (error) {
          console.error('Error loading element:', error, element);
        }
      }

      // Recalculer la position du footer après avoir chargé tous les éléments
      recalculateFooterPosition();
    }

    // Charger les éléments au chargement de la page
    loadElements();
  </script>
  @endif
</body>
</html>

