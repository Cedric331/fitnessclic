<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import Konva from 'konva';

interface LayoutElement {
    id: string;
    type: 'image' | 'text';
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
}

interface Props {
    layout: {
        layout_data: LayoutElement[];
        canvas_width: number;
        canvas_height: number;
    };
}

const props = defineProps<Props>();

const containerRef = ref<HTMLDivElement | null>(null);
let stage: Konva.Stage | null = null;
let layer: Konva.Layer | null = null;

onMounted(() => {
    if (!containerRef.value) return;

    // Create Konva stage
    stage = new Konva.Stage({
        container: containerRef.value,
        width: props.layout.canvas_width,
        height: props.layout.canvas_height,
    });

    layer = new Konva.Layer();
    stage.add(layer);

    // Load elements to canvas
    loadElementsToCanvas();
});

onUnmounted(() => {
    if (stage) {
        stage.destroy();
    }
});

// Load elements to canvas
const loadElementsToCanvas = async () => {
    if (!layer) return;

    for (const element of props.layout.layout_data) {
        try {
            if (element.type === 'image' && element.imageUrl) {
                await addImageToCanvas(element);
            } else if (element.type === 'text' && element.text) {
                addTextToCanvas(element);
            }
        } catch (error) {
            console.error('Error loading element:', error, element);
        }
    }
    
    if (layer) {
        layer.draw();
    }
};

// Add image to canvas
const addImageToCanvas = async (element: LayoutElement) => {
    if (!layer) return;

    return new Promise<void>((resolve, reject) => {
        const imageObj = new Image();
        
        imageObj.onload = () => {
            try {
                const konvaImage = new Konva.Image({
                    x: element.x,
                    y: element.y,
                    image: imageObj,
                    width: element.width || imageObj.width,
                    height: element.height || imageObj.height,
                    rotation: element.rotation || 0,
                    draggable: false, // Lecture seule
                });

                layer.add(konvaImage);
                layer.draw();
                resolve();
            } catch (error) {
                console.error('Error creating Konva image:', error);
                reject(error);
            }
        };
        
        imageObj.onerror = (error) => {
            console.error('Error loading image:', error, element.imageUrl);
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

// Add text to canvas
const addTextToCanvas = (element: LayoutElement) => {
    if (!layer) return;

    const konvaText = new Konva.Text({
        x: element.x,
        y: element.y,
        text: element.text || '',
        fontSize: element.fontSize || 16,
        fontFamily: element.fontFamily || 'Arial',
        fill: element.fill || '#000000',
        draggable: false, // Lecture seule
    });

    layer.add(konvaText);
    layer.draw();
};
</script>

<template>
    <div class="flex justify-center overflow-auto bg-neutral-100 dark:bg-neutral-800 p-4 rounded-lg">
        <div 
            ref="containerRef"
            class="bg-white shadow-lg"
            :style="{ 
                width: `${layout.canvas_width}px`, 
                height: `${layout.canvas_height}px`,
                minWidth: `${layout.canvas_width}px`,
                minHeight: `${layout.canvas_height}px`
            }"
        ></div>
    </div>
</template>

