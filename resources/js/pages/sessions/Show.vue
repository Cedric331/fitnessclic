<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ArrowLeft, Edit, Trash2, Download, Calendar, Users, FileText, Layout } from 'lucide-vue-next';
import { computed, ref, watch, nextTick, onMounted, onUnmounted } from 'vue';
import type { BreadcrumbItem } from '@/types';
import SessionDeleteDialog from './SessionDeleteDialog.vue';
import SessionLayoutEditor from './SessionLayoutEditor.vue';
import SessionLayoutViewer from './SessionLayoutViewer.vue';
import type { Session } from './types';
import { useNotifications } from '@/composables/useNotifications';
import Konva from 'konva';

interface Props {
    session: Session;
    exercises?: Array<{
        id: number;
        title: string;
        description?: string;
        image_url?: string;
        suggested_duration?: string;
        user_id?: number;
        categories?: Array<{
            id: number;
            name: string;
        }>;
    }>;
    customers?: Array<{
        id: number;
        first_name: string;
        last_name: string;
        email?: string;
        full_name: string;
    }>;
}

const props = defineProps<Props>();

const page = usePage();
const { success: notifySuccess, error: notifyError } = useNotifications();

const shownFlashMessages = ref(new Set<string>());

watch(() => (page.props as any).flash, (flash) => {
    if (!flash) return;
    
    const successKey = flash.success ? `success-${flash.success}` : null;
    const errorKey = flash.error ? `error-${flash.error}` : null;
    
    if (successKey && !shownFlashMessages.value.has(successKey)) {
        shownFlashMessages.value.add(successKey);
        nextTick(() => {
            setTimeout(() => {
                notifySuccess(flash.success);
            }, 100);
        });
        setTimeout(() => {
            shownFlashMessages.value.delete(successKey);
        }, 4500);
    }
    
    if (errorKey && !shownFlashMessages.value.has(errorKey)) {
        shownFlashMessages.value.add(errorKey);
        nextTick(() => {
            setTimeout(() => {
                notifyError(flash.error);
            }, 100);
        });
        setTimeout(() => {
            shownFlashMessages.value.delete(errorKey);
        }, 6500);
    }
}, { immediate: true });

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mes Séances',
        href: '/sessions',
    },
    {
        title: props.session.name || 'Séance',
        href: `/sessions/${props.session.id}`,
    },
];

const isDeleteDialogOpen = ref(false);
const isDeleteProcessing = ref(false);
const showLayoutEditor = ref(false);
const sessionLayout = ref<any>(null);
const customers = computed(() => props.customers || []);

const formatDate = (value?: string | null) => {
    if (!value) {
        return '—';
    }
    const date = new Date(value);
    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatShortDate = (value?: string | null) => {
    if (!value) {
        return '—';
    }
    const date = new Date(value);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const handleEdit = () => {
    router.visit(`/sessions/${props.session.id}/edit`);
};

const loadLayout = async () => {
    if (!props.session.has_custom_layout) {
        sessionLayout.value = null;
        return;
    }
    
    try {
        const response = await fetch(`/sessions/${props.session.id}/layout`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        if (response.ok) {
            const data = await response.json();
            if (data.layout) {
                sessionLayout.value = {
                    layout_data: data.layout.layout_data || [],
                    canvas_width: data.layout.canvas_width || 800,
                    canvas_height: data.layout.canvas_height || 1000,
                };
            } else {
                sessionLayout.value = null;
            }
        }
    } catch (error) {
        sessionLayout.value = null;
    }
};

const openLayoutEditor = async () => {
    try {
        await loadLayout();
        showLayoutEditor.value = true;
    } catch (error) {
        notifyError('Erreur lors de l\'ouverture de l\'éditeur');
    }
};

const handleLayoutSaved = async (sessionId: number) => {
    await loadLayout();
    router.reload({
        only: ['session'],
    });
};

const handleDelete = () => {
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (isDeleteProcessing.value) {
        return;
    }

    isDeleteProcessing.value = true;

    router.delete(`/sessions/${props.session.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/sessions');
        },
        onFinish: () => {
            isDeleteProcessing.value = false;
        },
    });
};

// Export PDF for free sessions (using layout)
const exportFreeSessionPdf = async () => {
    if (!props.session.layout || !props.session.has_custom_layout) {
        notifyError('Aucune mise en page disponible pour cette séance');
        return;
    }

    try {
        notifySuccess('Génération du PDF en cours...');

        const canvasWidth = props.session.layout.canvas_width ?? 800;
        const canvasHeight = props.session.layout.canvas_height ?? 1000;
        
        const tempContainer = document.createElement('div');
        tempContainer.style.position = 'absolute';
        tempContainer.style.left = '-9999px';
        tempContainer.style.width = `${canvasWidth}px`;
        tempContainer.style.height = `${canvasHeight}px`;
        document.body.appendChild(tempContainer);
        
        const tempStage = new Konva.Stage({
            container: tempContainer,
            width: canvasWidth,
            height: canvasHeight,
        });
        
        const tempLayer = new Konva.Layer();
        tempStage.add(tempLayer);
        
        await loadElementsToTempStage(tempLayer, props.session.layout);
        
        tempLayer.draw();
        
        const dataURL = tempStage.toDataURL({ 
            pixelRatio: 2,
            mimeType: 'image/png',
            quality: 1
        });
        
        tempStage.destroy();
        document.body.removeChild(tempContainer);
        
        let jsPDF: any;
        let script: HTMLScriptElement | null = null;
        
        if ((window as any).jspdf) {
            jsPDF = (window as any).jspdf.jsPDF;
        } else {
            script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            document.head.appendChild(script);
            
            await new Promise((resolve, reject) => {
                script!.onload = () => {
                    jsPDF = (window as any).jspdf.jsPDF;
                    resolve(undefined);
                };
                script!.onerror = reject;
            });
        }
        
        const pxToMm = 0.264583;
        const pdfWidth = canvasWidth * pxToMm;
        const pdfHeight = canvasHeight * pxToMm;
        
        const pdf = new jsPDF({
            orientation: pdfHeight > pdfWidth ? 'portrait' : 'landscape',
            unit: 'mm',
            format: [pdfWidth, pdfHeight]
        });
        
        const imgWidth = pdfWidth;
        const imgHeight = pdfHeight;
        
        pdf.addImage(dataURL, 'PNG', 0, 0, imgWidth, imgHeight);
        
        const name = props.session.name || 'mise-en-page';
        const fileName = `${name.replace(/[^a-z0-9]/gi, '-').toLowerCase()}-${Date.now()}.pdf`;
        
        pdf.save(fileName);
        
        if (script) {
            document.head.removeChild(script);
        }
        
        notifySuccess('PDF téléchargé avec succès');
    } catch (error: any) {
        notifyError('Erreur lors de l\'export PDF: ' + (error.message || 'Erreur inconnue'));
    }
};

// Recalculate footer position in temporary stage
const recalculateFooterPositionInTempStage = (
    layer: Konva.Layer, 
    layout: any, 
    footerTextNode: Konva.Text | null, 
    footerLogoNode: Konva.Image | null
) => {
    const footerHeight = 60;
    const footerY = layout.canvas_height - footerHeight;
    const canvasWidth = layout.canvas_width;
    
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
    
    const allNodes = layer.getChildren();
    const footerRect = allNodes.find((node: any) => {
        const nodeId = typeof node.id === 'function' ? node.id() : node.id;
        return nodeId && nodeId.includes && nodeId.includes('footer-bg');
    }) as Konva.Rect | undefined;
    if (footerRect) {
        footerRect.y(footerY);
        footerRect.width(canvasWidth);
    }
    
    layer.draw();
};

const loadElementsToTempStage = async (layer: Konva.Layer, layout: any) => {
    let footerTextNode: Konva.Text | null = null;
    let footerLogoNode: Konva.Image | null = null;
    
    for (const element of layout.layout_data) {
        try {
            if (element.type === 'image' && element.imageUrl) {
                const result = await addImageToTempStage(layer, element);
                if (result && element.id && element.id.includes('footer-logo')) {
                    footerLogoNode = result;
                }
            } else if (element.type === 'text' && element.text) {
                const result = addTextToTempStage(layer, element);
                if (result && element.id && element.id.includes('footer-text')) {
                    footerTextNode = result;
                }
            } else if (['rect', 'ellipse', 'line', 'arrow', 'highlight'].includes(element.type)) {
                addShapeToTempStage(layer, element);
            }
        } catch (error) {
        }
    }
    
    await new Promise(resolve => setTimeout(resolve, 100));
    
    recalculateFooterPositionInTempStage(layer, layout, footerTextNode, footerLogoNode);
};

const addImageToTempStage = async (layer: Konva.Layer, element: any): Promise<Konva.Image | null> => {
    return new Promise((resolve, reject) => {
        const imageObj = new Image();
        
        imageObj.onload = () => {
            try {
                const imageWidth = element.width || imageObj.width;
                const imageHeight = element.height || imageObj.height;

                // Créer un groupe pour contenir l'image et éventuellement le cadre
                const imageGroup = new Konva.Group({
                    x: element.x,
                    y: element.y,
                    rotation: element.rotation || 0,
                });

                const konvaImage = new Konva.Image({
                    x: 0,
                    y: 0,
                    image: imageObj,
                    width: imageWidth,
                    height: imageHeight,
                });

                // Appliquer l'ombre si nécessaire
                if (element.exerciseData?.imageShadow) {
                    const shadowColor = element.exerciseData.imageShadowColor || '#000000';
                    const shadowBlur = element.exerciseData.imageShadowBlur || 10;
                    const shadowOffsetX = element.exerciseData.imageShadowOffsetX || 5;
                    const shadowOffsetY = element.exerciseData.imageShadowOffsetY || 5;
                    const shadowOpacity = element.exerciseData.imageShadowOpacity !== undefined ? element.exerciseData.imageShadowOpacity : 0.5;
                    
                    konvaImage.shadowColor(shadowColor);
                    konvaImage.shadowBlur(shadowBlur);
                    konvaImage.shadowOffsetX(shadowOffsetX);
                    konvaImage.shadowOffsetY(shadowOffsetY);
                    konvaImage.shadowOpacity(shadowOpacity);
                }

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

                if (element.id) {
                    imageGroup.id(element.id);
                }
                
                layer.add(imageGroup);
                
                if (element.id && element.id.includes('footer-logo')) {
                    resolve(konvaImage);
                } else {
                    resolve(null);
                }
            } catch (error) {
                reject(error);
            }
        };
        
        imageObj.onerror = () => reject(new Error('Failed to load image'));
        
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

const addTextToTempStage = (layer: Konva.Layer, element: any): Konva.Text | null => {
    const isFooterText = element.id && element.id.includes('footer-text');
    
    const konvaText = new Konva.Text({
        x: element.x,
        y: element.y,
        text: element.text || '',
        fontSize: element.fontSize || 16,
        fontFamily: element.fontFamily || 'Arial',
        fill: element.fill || '#000000',
    });
    
    if (element.id) {
        konvaText.id(element.id);
    }
    
    if (isFooterText) {
        layer.add(konvaText);
        layer.draw();
        konvaText.offsetX(konvaText.width() / 2);
        konvaText.offsetY(konvaText.height() / 2);
        layer.draw();
        return konvaText;
    } else {
        layer.add(konvaText);
        layer.draw();
        return null;
    }
};

const addShapeToTempStage = (layer: Konva.Layer, element: any) => {
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
            });
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
        layer.draw();
    }
};

const handleDownloadPdf = () => {
    // Si c'est une séance libre, utiliser l'export basé sur le layout
    if (props.session.has_custom_layout && props.session.layout) {
        exportFreeSessionPdf();
    } else {
        // Pour les séances standard, utiliser l'export serveur
        window.open(`/sessions/${props.session.id}/pdf`, '_blank');
    }
};

watch(isDeleteDialogOpen, (open) => {
    if (!open) {
        isDeleteProcessing.value = false;
    }
});

// PDF preview state
const pdfUrl = ref<string | null>(null);
const pdfLoading = ref(false);
const pdfError = ref<string | null>(null);
const pdfContainer = ref<HTMLDivElement | null>(null);
const pdfData = ref<Uint8Array | null>(null);

let resizeTimeout: number | null = null;
let isPdfRendering = false;

const getPdfContainerWidth = () => {
    const el = pdfContainer.value;
    const width = el?.clientWidth || el?.parentElement?.clientWidth || 0;
    return width > 0 ? width : 800;
};

const handleResize = () => {
    if (props.session.has_custom_layout) return;
    if (resizeTimeout) {
        clearTimeout(resizeTimeout);
    }
    resizeTimeout = window.setTimeout(() => {
        loadPdfPreview({ silent: true });
    }, 150);
};

const loadPdfPreview = async (options: { silent?: boolean } = {}) => {
    if (props.session.has_custom_layout) {
        return;
    }

    if (isPdfRendering) {
        return;
    }

    const silent = options.silent === true;

    if (!silent) {
        pdfLoading.value = true;
    }
    pdfError.value = null;
    isPdfRendering = true;
    
    await nextTick();
    let retries = 0;
    while (!pdfContainer.value && retries < 10) {
        await new Promise(resolve => setTimeout(resolve, 100));
        retries++;
    }
    
    if (!pdfContainer.value) {
        pdfError.value = 'Impossible de charger le conteneur PDF';
        if (!silent) {
            pdfLoading.value = false;
        }
        isPdfRendering = false;
        return;
    }
    
    try {
        if (!(window as any).pdfjsLib) {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
            document.head.appendChild(script);
            
            await new Promise((resolve, reject) => {
                script.onload = () => {
                    (window as any).pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                    resolve(undefined);
                };
                script.onerror = reject;
            });
        }

        let pdfBytes = pdfData.value;

        if (!pdfBytes) {
            const response = await fetch(`/sessions/${props.session.id}/pdf-preview`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
            });
            
            if (!response.ok) {
                let errorMessage = `Erreur ${response.status}: ${response.statusText}`;
                try {
                    const errorText = await response.text();
                    if (errorText) {
                        try {
                            const errorJson = JSON.parse(errorText);
                            errorMessage = errorJson.message || errorJson.error || errorMessage;
                        } catch {
                            errorMessage = errorText.length > 200 
                                ? errorText.substring(0, 200) + '...' 
                                : errorText;
                        }
                    }
                } catch {
                }
                throw new Error(errorMessage);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/pdf')) {
                throw new Error('La réponse du serveur n\'est pas un PDF valide');
            }
            
            const fetchedBuffer = await response.arrayBuffer();
            
            if (fetchedBuffer.byteLength === 0) {
                throw new Error('Le PDF reçu est vide');
            }

            pdfBytes = new Uint8Array(fetchedBuffer);
            pdfData.value = pdfBytes;
        }
        
        let pdf;
        try {
            pdf = await (window as any).pdfjsLib.getDocument({ 
                // pdf.js peut détacher un ArrayBuffer (transfer au worker) : on passe une copie à chaque rendu
                data: pdfBytes.slice()
            }).promise;
        } catch (pdfError: any) {
            throw new Error(`Erreur lors du chargement du PDF: ${pdfError.message || 'Format PDF invalide'}`);
        }
        
        if (pdfContainer.value) {
            pdfContainer.value.innerHTML = '';
            const containerWidth = getPdfContainerWidth();
            
            try {
                for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                    const page = await pdf.getPage(pageNum);
                    const viewport = page.getViewport({ scale: 1.0 });
                    
                    const scale = containerWidth / viewport.width;
                    const scaledViewport = page.getViewport({ scale: scale });
                    
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    
                    if (!context) {
                        throw new Error('Impossible d\'obtenir le contexte canvas pour le rendu PDF');
                    }
                    
                    canvas.width = scaledViewport.width;
                    canvas.height = scaledViewport.height;
                    
                    await page.render({
                        canvasContext: context,
                        viewport: scaledViewport,
                    }).promise;
                    
                    canvas.className = 'mb-4 shadow-sm';
                    canvas.style.width = '100%';
                    canvas.style.height = 'auto';
                    canvas.style.display = 'block';
                    canvas.style.margin = '0 auto';
                    pdfContainer.value.appendChild(canvas);
                }
            } catch (renderError: any) {
                throw new Error(`Erreur lors du rendu du PDF: ${renderError.message || 'Impossible de rendre les pages du PDF'}`);
            }
        }

        if (pdfUrl.value) {
            URL.revokeObjectURL(pdfUrl.value);
        }
        const blobBuffer = pdfBytes.slice().buffer as ArrayBuffer;
        pdfUrl.value = URL.createObjectURL(new Blob([blobBuffer], { type: 'application/pdf' }));
    } catch (error: any) {
        pdfError.value = error.message || 'Erreur lors du chargement du PDF';
    } finally {
        isPdfRendering = false;
        if (!silent) {
            pdfLoading.value = false;
        }
    }
};

onMounted(() => {
    if (props.session.has_custom_layout) {
        loadLayout();
    } else {
        window.addEventListener('resize', handleResize);
        nextTick(() => {
            loadPdfPreview();
        });
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
    if (resizeTimeout) {
        clearTimeout(resizeTimeout);
        resizeTimeout = null;
    }
    if (pdfUrl.value) {
        URL.revokeObjectURL(pdfUrl.value);
    }
});

type SessionExerciseItem = NonNullable<Session['sessionExercises']>[number];

const groupExercisesIntoBlocks = () => {
    if (!props.session.sessionExercises) {
        return { standard: [], set: [] };
    }
    
    const blocksMap = new Map<number, SessionExerciseItem[]>();
    const standardExercises: SessionExerciseItem[] = [];
    
    props.session.sessionExercises.forEach(ex => {
        if (ex.block_id && ex.block_type === 'set') {
            if (!blocksMap.has(ex.block_id)) {
                blocksMap.set(ex.block_id, []);
            }
            blocksMap.get(ex.block_id)!.push(ex);
        } else {
            standardExercises.push(ex);
        }
    });
    
    const setBlocks = Array.from(blocksMap.entries())
        .map(([blockId, exercises]) => ({
            id: blockId,
            type: 'set' as const,
            exercises: exercises.sort((a, b) => 
                (a.position_in_block || 0) - (b.position_in_block || 0)
            ),
            order: exercises[0]?.order || 0,
        }))
        .sort((a, b) => a.order - b.order);
    
    return {
        standard: standardExercises.sort((a, b) => (a.order ?? 0) - (b.order ?? 0)),
        set: setBlocks,
    };
};

const orderedItems = computed(() => {
    const blocks = groupExercisesIntoBlocks();
    const items: Array<{ 
        type: 'standard' | 'set', 
        exercise?: SessionExerciseItem, 
        block?: { id: number; type: 'set'; exercises: SessionExerciseItem[]; order: number },
        key: string, 
        order: number, 
        displayIndex: number 
    }> = [];
    
    const allItems: Array<{ 
        type: 'standard' | 'set', 
        exercise?: SessionExerciseItem, 
        block?: { id: number; type: 'set'; exercises: SessionExerciseItem[]; order: number },
        order: number 
    }> = [];
    
    blocks.standard.forEach(ex => {
        allItems.push({
            type: 'standard',
            exercise: ex,
            order: ex.order ?? 0
        });
    });
    
    blocks.set.forEach(block => {
        allItems.push({
            type: 'set',
            block: block,
            order: block.order
        });
    });
    
    allItems.sort((a, b) => a.order - b.order);
    
    allItems.forEach((item, index) => {
        items.push({
            ...item,
            key: item.type === 'set' ? `set-${item.block!.id}` : `standard-${item.exercise!.id}`,
            displayIndex: index
        });
    });
    
    return items;
});

const sortedExercises = computed(() => {
    if (!props.session.sessionExercises) {
        return [];
    }
    return [...props.session.sessionExercises].sort((a, b) => {
        const orderA = a.order ?? 0;
        const orderB = b.order ?? 0;
        return orderA - orderB;
    });
});

const extractRestSeconds = (restTime: string | null | undefined): '-' | number => {
    if (!restTime || restTime === '-') return '-';
    const secondsMatch = restTime.match(/(\d+)\s*seconde/i);
    if (secondsMatch) {
        return parseInt(secondsMatch[1]);
    }
    const numberMatch = restTime.match(/^(\d+)$/);
    if (numberMatch) {
        return parseInt(numberMatch[1]);
    }
    let totalSeconds = 0;
    const minutesMatch = restTime.match(/(\d+)\s*minute/i);
    if (minutesMatch) totalSeconds += parseInt(minutesMatch[1]) * 60;
    const secondsInMatch = restTime.match(/(\d+)\s*seconde/i);
    if (secondsInMatch) totalSeconds += parseInt(secondsInMatch[1]);
    return totalSeconds > 0 ? totalSeconds : '-';
};

const extractDurationSeconds = (duration: string | null | undefined): '-' | number => {
    if (!duration || duration === '-') return '-';
    const secondsMatch = duration.match(/(\d+)\s*seconde/i);
    if (secondsMatch) {
        return parseInt(secondsMatch[1]);
    }
    const numberMatch = duration.match(/^(\d+)$/);
    if (numberMatch) {
        return parseInt(numberMatch[1]);
    }
    let totalSeconds = 0;
    const minutesMatch = duration.match(/(\d+)\s*minute/i);
    if (minutesMatch) totalSeconds += parseInt(minutesMatch[1]) * 60;
    const secondsInMatch = duration.match(/(\d+)\s*seconde/i);
    if (secondsInMatch) totalSeconds += parseInt(secondsInMatch[1]);
    return totalSeconds > 0 ? totalSeconds : '-';
};

const formatSeriesData = (set: any, sessionExercise: any) => {
    const setNumber = set.set_number || 1;
    
    let repsLabel = 'répétition';
    let repsValue: string | number = '-';
    const useDuration = sessionExercise.use_duration === true || sessionExercise.use_duration === 1 || sessionExercise.use_duration === '1' || sessionExercise.use_duration === 'true';
    
    if (useDuration) {
        const rawDuration = set.duration ?? sessionExercise.duration ?? '-';
        repsValue = extractDurationSeconds(rawDuration);
        repsLabel = 'seconde';
    } else {
        repsValue = set.repetitions ?? sessionExercise.repetitions ?? '-';
    }
    
    let chargeValue = '';
    const useBodyweight = sessionExercise.use_bodyweight === true || sessionExercise.use_bodyweight === 1 || sessionExercise.use_bodyweight === '1' || sessionExercise.use_bodyweight === 'true';
    
    if (useBodyweight) {
        chargeValue = 'poids de corps';
    } else {
        const weight = set.weight ?? sessionExercise.weight ?? '-';
        if (weight !== '-' && weight !== null) {
            const weightValue = typeof weight === 'number' ? Math.round(weight) : weight;
            chargeValue = `${weightValue}kg`;
        } else {
            chargeValue = '-';
        }
    }
    
    const rawRest = set.rest_time ?? sessionExercise.rest_time ?? '-';
    const restSeconds = extractRestSeconds(rawRest);
    const restText = restSeconds !== '-' ? `${restSeconds} seconde${restSeconds > 1 ? 's' : ''}` : '-';
    
    return {
        serie: setNumber,
        reps: { value: repsValue, label: repsLabel },
        charge: chargeValue,
        rest: restText
    };
};

const formatSeriesDataFallback = (sessionExercise: any, setsCount: number) => {
    const useDuration = sessionExercise.use_duration === true || sessionExercise.use_duration === 1 || sessionExercise.use_duration === '1' || sessionExercise.use_duration === 'true';
    const useBodyweight = sessionExercise.use_bodyweight === true || sessionExercise.use_bodyweight === 1 || sessionExercise.use_bodyweight === '1' || sessionExercise.use_bodyweight === 'true';
    
    let repsLabel = 'répétition';
    let repsValue: string | number = '-';
    
    if (useDuration) {
        const rawDuration = sessionExercise.duration ?? '-';
        repsValue = extractDurationSeconds(rawDuration);
        repsLabel = 'seconde';
    } else {
        repsValue = sessionExercise.repetitions ?? '-';
    }
    
    let chargeValue = '';
    if (useBodyweight) {
        chargeValue = 'poids de corps';
    } else {
        const weight = sessionExercise.weight ?? '-';
        if (weight !== '-' && weight !== null) {
            const weightValue = typeof weight === 'number' ? Math.round(weight) : weight;
            chargeValue = `${weightValue}kg`;
        } else {
            chargeValue = '-';
        }
    }
    
    const rawRest = sessionExercise.rest_time ?? '-';
    const restSeconds = extractRestSeconds(rawRest);
    const restText = restSeconds !== '-' ? `${restSeconds} seconde${restSeconds > 1 ? 's' : ''}` : '-';
    
    return {
        serie: setsCount,
        reps: { value: repsValue, label: repsLabel },
        charge: chargeValue,
        rest: restText
    };
};
</script>

<template>
    <Head :title="`${session.name || 'Séance'} • Mes Séances`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 pt-6 pb-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Détails de la séance
                    </p>
                    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
                        {{ session.name || 'Séance sans titre' }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <span v-if="session.session_date" class="flex items-center gap-1">
                            <Calendar class="size-4" />
                            {{ formatShortDate(session.session_date) }}
                        </span>
                        <span v-if="sortedExercises.length" class="flex items-center gap-1">
                            · {{ sortedExercises.length }} exercice{{ sortedExercises.length > 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:flex sm:flex-row gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2 w-full sm:w-auto"
                        @click="router.visit('/sessions')"
                    >
                        <ArrowLeft class="size-4" />
                        <span>Retour</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2 w-full sm:w-auto"
                        @click="handleDownloadPdf"
                    >
                        <Download class="size-4" />
                        <span>PDF</span>
                    </Button>
                    <Button
                        v-if="session.has_custom_layout"
                        variant="outline"
                        size="sm"
                        class="hidden xl:inline-flex items-center gap-2 w-full sm:w-auto"
                        @click="openLayoutEditor"
                    >
                        <Layout class="size-4" />
                        <span>Éditer mise en page</span>
                    </Button>
                    <Button
                        v-if="!session.has_custom_layout"
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2 w-full sm:w-auto"
                        @click="handleEdit"
                    >
                        <Edit class="size-4" />
                        <span>Modifier</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 w-full sm:w-auto"
                        @click="handleDelete"
                    >
                        <Trash2 class="size-4" />
                        <span>Supprimer</span>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-4">
                <!-- Colonne gauche (75% - 3 colonnes sur 4) : Exercices ou Mise en page -->
                <div class="lg:col-span-3">
                    <!-- Affichage de la mise en page personnalisée -->
                    <Card v-if="session.has_custom_layout && session.layout">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Layout class="size-5" />
                                Mise en page personnalisée
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="min-w-0 px-2 sm:px-6">
                            <SessionLayoutViewer
                                :layout="session.layout"
                            />
                        </CardContent>
                    </Card>

                    <!-- Prévisualisation PDF (affichée seulement si pas de mise en page personnalisée) -->
                    <Card v-if="!session.has_custom_layout">
                        <CardHeader>
                            <CardTitle>
                                Prévisualisation PDF
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="w-full" style="min-height: 800px;">
                                <div v-if="pdfLoading" class="flex items-center justify-center h-[800px]">
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Chargement du PDF...</p>
                                </div>
                                <div v-else-if="pdfError" class="flex items-center justify-center h-[800px]">
                                    <p class="text-sm text-red-500">{{ pdfError }}</p>
                                </div>
                                <div
                                    ref="pdfContainer"
                                    class="w-full max-w-5xl mx-auto flex flex-col items-center"
                                    v-show="!pdfError"
                                ></div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Colonne droite (25% - 1 colonne sur 4) : Informations et Détails -->
                <div class="lg:col-span-1">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="size-5" />
                                Informations et Détails
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Clients associés -->
                            <div v-if="session.customers && session.customers.length > 0">
                                <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 flex items-center gap-2">
                                    <Users class="size-4" />
                                    Client{{ session.customers.length > 1 ? 's' : '' }}
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="customer in session.customers"
                                        :key="customer.id"
                                        variant="default"
                                        class="bg-blue-600 text-white"
                                    >
                                        {{ customer.full_name }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Notes (masqué pour les séances personnalisées) -->
                            <div v-if="!session.has_custom_layout">
                                <div v-if="session.notes">
                                    <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2">
                                        Notes
                                    </p>
                                    <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
                                        {{ session.notes }}
                                    </p>
                                </div>
                                <div v-else>
                                    <p class="text-xs text-slate-400 dark:text-slate-500">
                                        Aucune note pour cette séance.
                                    </p>
                                </div>
                            </div>

                            <Separator />

                            <!-- Métadonnées -->
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Date de la séance</p>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ session.session_date ? formatDate(session.session_date) : 'Non définie' }}
                                    </p>
                                </div>
                                <!-- Nombre d'exercices (masqué pour les séances personnalisées) -->
                                <div v-if="!session.has_custom_layout">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Nombre d'exercices</p>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ sortedExercises.length }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Créée le</p>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ formatDate(session.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Modal de suppression -->
            <SessionDeleteDialog
                v-model:open="isDeleteDialogOpen"
                :session="session"
                :loading="isDeleteProcessing"
                @confirm="confirmDelete"
            />

            <!-- Layout Editor -->
            <div v-if="showLayoutEditor" class="fixed inset-0 z-50 bg-white dark:bg-neutral-900">
                <SessionLayoutEditor
                    :session-id="session.id"
                    :exercises="props.exercises || []"
                    :initial-layout="sessionLayout"
                    :customers="customers"
                    :session-name="session.name"
                    :session-customers="session.customers"
                    @close="() => { showLayoutEditor = false; loadLayout(); }"
                    @saved="handleLayoutSaved"
                />
            </div>
        </div>
    </AppLayout>
</template>

