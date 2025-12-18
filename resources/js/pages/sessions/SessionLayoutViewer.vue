<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import Konva from 'konva';

interface LayoutElement {
    id: string;
    type: 'image' | 'text' | 'rect' | 'ellipse' | 'line' | 'arrow' | 'highlight';
    x: number;
    y: number;
    width?: number;
    height?: number;
    rotation?: number;
    imageUrl?: string;
    exerciseId?: number;
    text?: string;
    fontSize?: number;
    fontFamily?: string;
    fill?: string;
    stroke?: string;
    strokeWidth?: number;
    opacity?: number;
    points?: number[];
    radiusX?: number;
    radiusY?: number;
    pointerLength?: number;
    pointerWidth?: number;
}

interface Props {
    layout: {
        layout_data: LayoutElement[];
        canvas_width: number;
        canvas_height: number;
    };
}

const props = defineProps<Props>();

const wrapperRef = ref<HTMLDivElement | null>(null);
const containerRef = ref<HTMLDivElement | null>(null);
let stage: Konva.Stage | null = null;
let layer: Konva.Layer | null = null;

const scale = ref(1);

let resizeTimeout: number | null = null;
let resizeObserver: ResizeObserver | null = null;

const getWrapperContentWidth = () => {
    const el = wrapperRef.value ?? containerRef.value?.parentElement ?? null;
    if (!el) return 0;

    const styles = window.getComputedStyle(el);
    const paddingLeft = parseFloat(styles.paddingLeft || '0') || 0;
    const paddingRight = parseFloat(styles.paddingRight || '0') || 0;

    // Important: sur mobile, certains layouts peuvent avoir un wrapper "large" (ex: min-width desktop),
    // mais on veut fitter sur la largeur *visible* de l'écran.
    const viewportWidth =
        (window as any).visualViewport?.width ??
        document.documentElement.clientWidth ??
        window.innerWidth ??
        0;

    const rect = el.getBoundingClientRect();
    // Largeur réellement visible = intersection [rect.left, rect.right] ∩ [0, viewportWidth]
    const visibleLeft = Math.max(rect.left, 0);
    const visibleRight = Math.min(rect.right, viewportWidth);
    const visibleWidth = Math.max(visibleRight - visibleLeft, 0);

    const contentWidth = visibleWidth - paddingLeft - paddingRight;
    return contentWidth > 0 ? contentWidth : 0;
};

const ensureInitialScale = async () => {
    // Sur mobile (et parfois lors d'un premier paint), la largeur peut être 0 au moment du mounted.
    // On retente quelques fois pour garantir un scale correct.
    await nextTick();
    for (let i = 0; i < 10; i++) {
        if (getWrapperContentWidth() > 0) {
            updateStageScale();
            return;
        }
        await new Promise(resolve => setTimeout(resolve, 50));
    }
    updateStageScale();
};

const updateStageScale = () => {
    if (!stage || !containerRef.value) return;

    const wrapperWidth = getWrapperContentWidth();
    if (!wrapperWidth) return;

    // On évite l'upscale (souvent flou) : le canvas ne dépassera pas sa taille native.
    const nextScale = Math.min(wrapperWidth / props.layout.canvas_width, 1);
    scale.value = nextScale;

    const scaledW = Math.round(props.layout.canvas_width * nextScale);
    const scaledH = Math.round(props.layout.canvas_height * nextScale);

    // 1) On scale le stage (coordonnées)
    stage.scale({ x: nextScale, y: nextScale });

    // 2) On aligne la taille DOM du stage sur la taille scalée (sinon décalage/clipping)
    stage.size({ width: scaledW, height: scaledH });

    // 3) On ajuste la taille du conteneur DOM pour matcher exactement
    containerRef.value.style.width = `${scaledW}px`;
    containerRef.value.style.height = `${scaledH}px`;

    stage.batchDraw();
};

const handleResize = () => {
    if (resizeTimeout) {
        clearTimeout(resizeTimeout);
    }
    resizeTimeout = window.setTimeout(() => {
        updateStageScale();
    }, 150);
};

onMounted(() => {
    if (!containerRef.value) return;

    stage = new Konva.Stage({
        container: containerRef.value,
        width: props.layout.canvas_width,
        height: props.layout.canvas_height,
    });

    layer = new Konva.Layer();
    stage.add(layer);

    window.addEventListener('resize', handleResize);

    // Plus robuste que window.resize (sidebar, layout, etc.)
    if (typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(() => {
            handleResize();
        });
        const observeEl = wrapperRef.value ?? containerRef.value?.parentElement ?? null;
        if (observeEl) resizeObserver.observe(observeEl);
    }

    // Charge d'abord le contenu, puis fit (évite des comportements "fantômes")
    loadElementsToCanvas().finally(() => {
        ensureInitialScale();
    });
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
    if (resizeTimeout) {
        clearTimeout(resizeTimeout);
        resizeTimeout = null;
    }
    if (resizeObserver) {
        resizeObserver.disconnect();
        resizeObserver = null;
    }
    if (stage) {
        stage.destroy();
    }
});

const footerTextNode = ref<Konva.Text | null>(null);
const footerLogoNode = ref<Konva.Image | null>(null);

const adjustFooterLogoPosition = () => {
    if (!layer || !footerTextNode.value || !footerLogoNode.value) return;
    
    const textWidth = footerTextNode.value.width();
    const canvasWidth = props.layout.canvas_width;
    const textX = canvasWidth / 2;
    const textRight = textX + textWidth / 2;
    const newLogoX = textRight + 15;
    
    footerLogoNode.value.x(newLogoX);
    layer.draw();
};

const loadElementsToCanvas = async () => {
    if (!layer) return;

    for (const element of props.layout.layout_data) {
        try {
            if (element.type === 'image' && element.imageUrl) {
                await addImageToCanvas(element);
            } else if (element.type === 'text' && element.text) {
                addTextToCanvas(element);
            } else if (element.type === 'rect' || element.type === 'ellipse' || element.type === 'line' || element.type === 'arrow' || element.type === 'highlight') {
                addShapeToCanvas(element);
            }
        } catch (error) {
        }
    }
    
    if (footerTextNode.value && footerLogoNode.value) {
        adjustFooterLogoPosition();
    }
    
    if (layer) {
        layer.draw();
    }

    // Fit après chargement (images/texte)
    updateStageScale();
};

const addImageToCanvas = async (element: LayoutElement) => {
    if (!layer) return;
    const localLayer = layer;

    return new Promise<void>((resolve, reject) => {
        const imageObj = new Image();
        
        imageObj.onload = () => {
            try {
                // Le composant peut être démonté avant la fin du chargement de l'image
                if (!localLayer) {
                    resolve();
                    return;
                }

                const konvaImage = new Konva.Image({
                    x: element.x,
                    y: element.y,
                    image: imageObj,
                    width: element.width || imageObj.width,
                    height: element.height || imageObj.height,
                    rotation: element.rotation || 0,
                    draggable: false, // Lecture seule
                });

                localLayer.add(konvaImage);
                
                const isFooterLogo = element.id && element.id.includes('footer-logo');
                if (isFooterLogo) {
                    footerLogoNode.value = konvaImage;
                }
                
                localLayer.draw();
                resolve();
            } catch (error) {
                reject(error);
            }
        };
        
        imageObj.onerror = (error) => {
            reject(new Error('Failed to load image'));
        };
        
        if (element.imageUrl) {
            let imageUrl = element.imageUrl;
            if (!imageUrl.startsWith('http://') && !imageUrl.startsWith('https://')) {
                if (!imageUrl.startsWith('/')) {
                    imageUrl = '/' + imageUrl;
                }
                if (!imageUrl.startsWith('http')) {
                    imageUrl = window.location.origin + (imageUrl.startsWith('/') ? imageUrl : '/' + imageUrl);
                }
            }
            imageObj.src = imageUrl;
        } else {
            reject(new Error('No image URL provided'));
        }
    });
};

const addTextToCanvas = (element: LayoutElement) => {
    if (!layer) return;

    const isFooterElement = element.id && element.id.includes('footer');
    const isFooterText = isFooterElement && element.id.includes('footer-text');
    
    const konvaText = new Konva.Text({
        x: element.x,
        y: element.y,
        text: element.text || '',
        fontSize: element.fontSize || 16,
        fontFamily: element.fontFamily || 'Arial',
        fill: element.fill || '#000000',
        draggable: false, // Lecture seule
    });

    if (isFooterText) {
        layer.add(konvaText);
        layer.draw();
        konvaText.offsetX(konvaText.width() / 2);
        konvaText.offsetY(konvaText.height() / 2);
        footerTextNode.value = konvaText;
        layer.draw();
    } else {
        layer.add(konvaText);
        layer.draw();
    }
};

const addShapeToCanvas = (element: LayoutElement) => {
    if (!layer) return;

    let konvaShape: Konva.Shape | Konva.Group | null = null;

    switch (element.type) {
        case 'rect':
        case 'highlight':
            konvaShape = new Konva.Rect({
                x: element.x,
                y: element.y,
                width: element.width || 100,
                height: element.height || 100,
                fill: element.type === 'highlight' ? '#FFFF00' : element.fill || undefined,
                opacity: element.type === 'highlight' ? 0.3 : (element.opacity !== undefined ? element.opacity : 1),
                stroke: element.type === 'highlight' ? undefined : (element.stroke !== undefined ? element.stroke : '#000000'),
                strokeWidth: element.strokeWidth !== undefined ? element.strokeWidth : 2,
                rotation: element.rotation || 0,
                draggable: false,
            });
            break;
        case 'ellipse':
            const radiusX = element.radiusX !== undefined ? element.radiusX : 50;
            const radiusY = element.radiusY !== undefined ? element.radiusY : 50;
            
            const ellipseGroup = new Konva.Group({
                x: element.x - radiusX,
                y: element.y - radiusY,
                rotation: element.rotation || 0,
                draggable: false,
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
                    draggable: false,
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
                    draggable: false,
                });
            }
            break;
    }

    if (konvaShape) {
        layer.add(konvaShape);
        layer.draw();
    }
};
</script>

<template>
    <div
        ref="wrapperRef"
        class="w-full min-w-0 max-w-5xl mx-auto flex justify-center overflow-hidden bg-neutral-100 dark:bg-neutral-800 p-4 rounded-lg"
    >
        <div ref="containerRef" class="bg-white shadow-lg w-full"></div>
    </div>
</template>

