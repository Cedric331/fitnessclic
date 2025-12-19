<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import Konva from 'konva';

interface ExerciseInstructionRow {
    series?: number;
    reps?: number;
    duration?: number; // en secondes
    useDuration?: boolean; // true = durée, false = répets
    recovery?: number;
    load?: number;
    useBodyweight?: boolean; // true = poids de corps, false = charge
}

interface TableData {
    rows: ExerciseInstructionRow[];
}

interface ExerciseData {
    imageFrame?: boolean;
    imageFrameColor?: string;
    imageFrameWidth?: number;
    [key: string]: any;
}

interface LayoutElement {
    id: string;
    type: 'image' | 'text' | 'rect' | 'ellipse' | 'line' | 'arrow' | 'highlight' | 'table';
    x: number;
    y: number;
    width?: number;
    height?: number;
    scaleX?: number;
    scaleY?: number;
    rotation?: number;
    imageUrl?: string;
    exerciseId?: number;
    exerciseData?: ExerciseData;
    tableData?: TableData;
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

    const viewportWidth =
        (window as any).visualViewport?.width ??
        document.documentElement.clientWidth ??
        window.innerWidth ??
        0;

    const rect = el.getBoundingClientRect();
    const visibleLeft = Math.max(rect.left, 0);
    const visibleRight = Math.min(rect.right, viewportWidth);
    const visibleWidth = Math.max(visibleRight - visibleLeft, 0);

    const contentWidth = visibleWidth - paddingLeft - paddingRight;
    return contentWidth > 0 ? contentWidth : 0;
};

const ensureInitialScale = async () => {
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

    const nextScale = Math.min(wrapperWidth / props.layout.canvas_width, 1);
    scale.value = nextScale;

    const scaledW = Math.round(props.layout.canvas_width * nextScale);
    const scaledH = Math.round(props.layout.canvas_height * nextScale);

    stage.scale({ x: nextScale, y: nextScale });

    stage.size({ width: scaledW, height: scaledH });

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

    if (typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(() => {
            handleResize();
        });
        const observeEl = wrapperRef.value ?? containerRef.value?.parentElement ?? null;
        if (observeEl) resizeObserver.observe(observeEl);
    }

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
            } else if (element.type === 'table' && element.tableData) {
                addTableToCanvas(element);
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

    updateStageScale();
};

const addTableToCanvas = (element: LayoutElement) => {
    if (!layer || !element.tableData) return;

    const cellPadding = 5;
    const cellHeight = 28;
    const colWidths = {
        series: 55,
        reps: 55,
        recovery: 55,
        load: 55
    };
    const tableWidth = Object.values(colWidths).reduce((a, b) => a + b, 0) + cellPadding * 5;
    const headerHeight = 28;
    const tableHeight = headerHeight + (element.tableData.rows.length * cellHeight) + cellPadding * 2;

    const tableGroup = new Konva.Group({
        x: element.x,
        y: element.y,
        draggable: false,
        id: element.id,
    });

    tableGroup.scaleX(element.scaleX ?? 1);
    tableGroup.scaleY(element.scaleY ?? 1);

    const tableBg = new Konva.Rect({
        x: 0,
        y: 0,
        width: tableWidth,
        height: tableHeight,
        fill: '#ffffff',
        stroke: '#e5e7eb',
        strokeWidth: 1,
        cornerRadius: 4,
        shadowColor: 'rgba(0, 0, 0, 0.15)',
        shadowBlur: 8,
        shadowOffset: { x: 0, y: 2 },
        shadowOpacity: 1,
    });
    tableGroup.add(tableBg);

    const headerBg = new Konva.Rect({
        x: 0,
        y: 0,
        width: tableWidth,
        height: headerHeight,
        fill: '#E0F2FE',
        cornerRadius: 4,
    });
    tableGroup.add(headerBg);

    const firstRow = element.tableData.rows[0];
    const repsLabel = firstRow?.useDuration ? 'durée (s)' : 'répets';
    const loadLabel = firstRow?.useBodyweight ? 'poids de corps' : 'charge';

    const headers = [
        { text: 'série(s)', x: cellPadding, width: colWidths.series },
        { text: repsLabel, x: cellPadding + colWidths.series, width: colWidths.reps },
        { text: 'récup', x: cellPadding + colWidths.series + colWidths.reps, width: colWidths.recovery },
        { text: loadLabel, x: cellPadding + colWidths.series + colWidths.reps + colWidths.recovery, width: colWidths.load }
    ];

    const headerLine = new Konva.Line({
        points: [0, headerHeight, tableWidth, headerHeight],
        stroke: '#d1d5db',
        strokeWidth: 1,
    });
    tableGroup.add(headerLine);

    headers.forEach(header => {
        const headerText = new Konva.Text({
            x: header.x,
            y: cellPadding + 3,
            text: header.text,
            fontSize: 10,
            fontFamily: 'Arial',
            fill: '#374151',
            width: header.width,
            align: 'center',
            fontStyle: 'bold',
        });
        tableGroup.add(headerText);
    });

    element.tableData.rows.forEach((row, rowIndex) => {
        const rowY = headerHeight + (rowIndex * cellHeight) + cellPadding;

        if (rowIndex > 0) {
            const rowLine = new Konva.Line({
                points: [0, rowY - cellPadding, tableWidth, rowY - cellPadding],
                stroke: '#e5e7eb',
                strokeWidth: 0.5,
            });
            tableGroup.add(rowLine);
        }

        const repsValue = row.useDuration ? (row.duration?.toString() || '') : (row.reps?.toString() || '');
        const loadValue = row.useBodyweight ? 'Pdc' : (row.load?.toString() || '');

        const cells = [
            { text: row.series?.toString() || '', x: cellPadding, width: colWidths.series },
            { text: repsValue, x: cellPadding + colWidths.series, width: colWidths.reps },
            { text: row.recovery?.toString() || '', x: cellPadding + colWidths.series + colWidths.reps, width: colWidths.recovery },
            { text: loadValue, x: cellPadding + colWidths.series + colWidths.reps + colWidths.recovery, width: colWidths.load }
        ];

        cells.forEach(cell => {
            const cellText = new Konva.Text({
                x: cell.x,
                y: rowY + 5,
                text: cell.text,
                fontSize: 10,
                fontFamily: 'Arial',
                fill: '#111827',
                width: cell.width,
                align: 'center',
            });
            tableGroup.add(cellText);
        });
    });

    layer.add(tableGroup);
};

const addImageToCanvas = async (element: LayoutElement) => {
    if (!layer) return;
    const localLayer = layer;

    return new Promise<void>((resolve, reject) => {
        const imageObj = new Image();
        
        imageObj.onload = () => {
            try {
                if (!localLayer) {
                    resolve();
                    return;
                }

                const imageWidth = element.width || imageObj.width;
                const imageHeight = element.height || imageObj.height;

                // Créer un groupe pour contenir l'image et éventuellement le cadre
                const imageGroup = new Konva.Group({
                    x: element.x,
                    y: element.y,
                    rotation: element.rotation || 0,
                    draggable: false,
                });

                const konvaImage = new Konva.Image({
                    x: 0,
                    y: 0,
                    image: imageObj,
                    width: imageWidth,
                    height: imageHeight,
                });

                imageGroup.add(konvaImage);

                // Ajouter le cadre si nécessaire
                if (element.exerciseData?.imageFrame) {
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

                localLayer.add(imageGroup);
                
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
        draggable: false,
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

