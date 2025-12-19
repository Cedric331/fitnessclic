<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Génération PDF - {{ $session->name ?? 'Séance' }}</title>
    <script src="https://cdn.jsdelivr.net/npm/konva@9/konva.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        #canvas-container {
            position: absolute;
            left: -9999px;
        }
    </style>
</head>
<body>
    <div id="canvas-container"></div>
    
    <script>
        const layoutData = @json($layoutData ?? []);
        const canvasWidth = {{ $layout->canvas_width ?? 800 }};
        const canvasHeight = {{ $layout->canvas_height ?? 1140 }};
        
        // Créer le stage Konva
        const stage = new Konva.Stage({
            container: 'canvas-container',
            width: canvasWidth,
            height: canvasHeight,
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
        
        // Variables pour le footer
        let footerTextNode = null;
        let footerLogoNode = null;
        let footerRect = null;
        
        // Fonction pour recalculer la position du footer
        function recalculateFooterPosition() {
            const footerHeight = 60;
            const footerY = canvasHeight - footerHeight;
            
            if (footerRect) {
                footerRect.x(0);
                footerRect.y(footerY);
                footerRect.width(canvasWidth);
                footerRect.height(footerHeight);
            }
            
            if (footerTextNode) {
                footerTextNode.x(canvasWidth / 2);
                footerTextNode.y(footerY + footerHeight / 2);
                footerTextNode.offsetX(footerTextNode.width() / 2);
                footerTextNode.offsetY(footerTextNode.height() / 2);
            }
            
            if (footerTextNode && footerLogoNode) {
                const textWidth = footerTextNode.width();
                const textX = canvasWidth / 2;
                const textRight = textX + textWidth / 2;
                const newLogoX = textRight + 15;
                footerLogoNode.x(newLogoX);
                const logoHeight = footerLogoNode.height();
                footerLogoNode.y(footerY + (footerHeight - logoHeight) / 2);
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
                                x: element.x || 0,
                                y: element.y || 0,
                                text: element.text || '',
                                fontSize: element.fontSize || 16,
                                fontFamily: element.fontFamily || 'Arial',
                                fill: element.fill || '#000000',
                                opacity: element.opacity !== undefined ? element.opacity : 1,
                                width: element.width,
                                height: element.height,
                            });
                            
                            if (elementId.includes('footer-text')) {
                                footerTextNode = konvaShape;
                                layer.add(konvaShape);
                                layer.draw();
                                konvaShape.offsetX(konvaShape.width() / 2);
                                konvaShape.offsetY(konvaShape.height() / 2);
                                continue;
                            }
                            break;
                            
                        case 'image':
                            if (element.imageUrl) {
                                try {
                                    const imageObj = await loadImage(element.imageUrl);
                                    const imageWidth = element.width || imageObj.width;
                                    const imageHeight = element.height || imageObj.height;
                                    
                                    // Créer un groupe pour contenir l'image et éventuellement le cadre
                                    const imageGroup = new Konva.Group({
                                        x: element.x || 0,
                                        y: element.y || 0,
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
                                    
                                    imageGroup.add(konvaImage);
                                    
                                    // Ajouter le cadre si nécessaire
                                    if (element.exerciseData && element.exerciseData.imageFrame) {
                                        const frameColor = element.exerciseData.imageFrameColor || '#000000';
                                        const frameWidth = element.exerciseData.imageFrameWidth || 2;
                                        
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
                                x: element.x || 0,
                                y: element.y || 0,
                                width: element.width || 100,
                                height: element.height || 100,
                                fill: element.type === 'highlight' ? '#FFFF00' : (element.fill || undefined),
                                opacity: element.type === 'highlight' ? 0.3 : (element.opacity !== undefined ? element.opacity : 1),
                                stroke: element.type === 'highlight' ? undefined : (element.stroke !== undefined ? element.stroke : '#000000'),
                                strokeWidth: element.strokeWidth !== undefined ? element.strokeWidth : 2,
                                rotation: element.rotation || 0,
                            });
                            
                            if (elementId.includes('footer-bg')) {
                                footerRect = konvaShape;
                            }
                            break;
                            
                        case 'ellipse':
                            const radiusX = element.radiusX !== undefined ? element.radiusX : 50;
                            const radiusY = element.radiusY !== undefined ? element.radiusY : 50;
                            
                            const ellipseGroup = new Konva.Group({
                                x: element.x - radiusX,
                                y: element.y - radiusY,
                                rotation: element.rotation || 0,
                            });
                            
                            const ellipseShape = new Konva.Ellipse({
                                x: radiusX,
                                y: radiusY,
                                radiusX: radiusX,
                                radiusY: radiusY,
                                fill: element.fill || undefined,
                                stroke: element.stroke !== undefined ? element.stroke : '#000000',
                                strokeWidth: element.strokeWidth !== undefined ? element.strokeWidth : 2,
                                opacity: element.opacity !== undefined ? element.opacity : 1,
                            });
                            
                            ellipseGroup.add(ellipseShape);
                            konvaShape = ellipseGroup;
                            break;
                            
                        case 'line':
                            if (element.points && element.points.length >= 4) {
                                konvaShape = new Konva.Line({
                                    points: element.points,
                                    stroke: element.stroke !== undefined ? element.stroke : '#000000',
                                    strokeWidth: element.strokeWidth !== undefined ? element.strokeWidth : 2,
                                    opacity: element.opacity !== undefined ? element.opacity : 1,
                                    lineCap: 'round',
                                    lineJoin: 'round',
                                });
                            }
                            break;
                            
                        case 'arrow':
                            if (element.points && element.points.length >= 4) {
                                konvaShape = new Konva.Arrow({
                                    points: element.points,
                                    stroke: element.stroke !== undefined ? element.stroke : '#000000',
                                    strokeWidth: element.strokeWidth !== undefined ? element.strokeWidth : 2,
                                    fill: element.fill || element.stroke || '#000000',
                                    opacity: element.opacity !== undefined ? element.opacity : 1,
                                    pointerLength: element.pointerLength || 10,
                                    pointerWidth: element.pointerWidth || 10,
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
            
            recalculateFooterPosition();
            
            // Générer le PDF
            layer.draw();
            
            // Attendre un peu pour que tout soit rendu
            setTimeout(() => {
                const dataURL = stage.toDataURL({ 
                    pixelRatio: 2,
                    mimeType: 'image/png',
                    quality: 1
                });
                
                const { jsPDF } = window.jspdf;
                
                const pxToMm = 0.264583;
                const pdfWidth = canvasWidth * pxToMm;
                const pdfHeight = canvasHeight * pxToMm;
                
                const pdf = new jsPDF({
                    orientation: pdfHeight > pdfWidth ? 'portrait' : 'landscape',
                    unit: 'mm',
                    format: [pdfWidth, pdfHeight]
                });
                
                pdf.addImage(dataURL, 'PNG', 0, 0, pdfWidth, pdfHeight);
                
                // Envoyer le PDF au serveur
                const pdfBlob = pdf.output('blob');
                const formData = new FormData();
                formData.append('pdf', pdfBlob, 'session.pdf');
                
                fetch('{{ route("sessions.layout.pdf-generate", $session->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).then(() => {
                    window.close();
                });
            }, 500);
        }
        
        loadElements();
    </script>
</body>
</html>

