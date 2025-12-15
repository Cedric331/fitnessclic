<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ArrowLeft, Eye, Download, Printer, Mail, Edit, Trash2, Layout } from 'lucide-vue-next';
import { computed, ref, watch, nextTick } from 'vue';
import type { Customer, TrainingSessionHistory } from './types';
import { useNotifications } from '@/composables/useNotifications';
import CustomerEditDialog from './CustomerEditDialog.vue';
import CustomerDeleteDialog from './CustomerDeleteDialog.vue';
import UpgradeModal from '@/components/UpgradeModal.vue';

interface Props {
    customer: Customer;
    training_sessions: TrainingSessionHistory[];
}

const props = defineProps<Props>();

const pageTitle = computed(() => `${props.customer.first_name} ${props.customer.last_name}`);
const breadcrumbs = computed(() => [
    { title: 'Mes Clients', href: '/customers' },
    { title: pageTitle.value, href: `/customers/${props.customer.id}` },
]);

const sessionHistory = computed(() => props.training_sessions ?? []);
const totalSessions = computed(() => sessionHistory.value.length);
const lastSessionDate = computed(() => {
    const firstSession = sessionHistory.value[0];
    return firstSession?.session_date ?? firstSession?.created_at ?? null;
});

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

const formatSessionDate = (session: TrainingSessionHistory) => {
    const dateValue = session.session_date ?? session.created_at;
    if (!dateValue) {
        return 'Date inconnue';
    }

    return formatDate(dateValue);
};

const page = usePage();
const { success: notifySuccess, error: notifyError } = useNotifications();

// État pour l'envoi d'email
const isSendEmailDialogOpen = ref(false);
const sessionToSend = ref<TrainingSessionHistory | null>(null);
const isSendingEmail = ref(false);

// État pour l'édition et la suppression du client
const isEditDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const isDeleteProcessing = ref(false);
const isUpgradeModalOpen = ref(false);
const isPro = computed(() => {
    const user = (page.props as any).auth?.user;
    return user?.isPro ?? false;
});

// Écouter les messages flash et les convertir en notifications
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

// Actions pour les séances
const handleViewSession = (session: TrainingSessionHistory) => {
    router.visit(`/sessions/${session.id}`);
};

// Recalculate footer position in temporary stage
const recalculateFooterPositionInTempStage = (
    layer: any,
    layout: any,
    footerTextNode: any,
    footerLogoNode: any
) => {
    const footerHeight = 60;
    const footerY = layout.canvas_height - footerHeight;
    const canvasWidth = layout.canvas_width;

    // Recalculer la position du texte du footer
    if (footerTextNode) {
        footerTextNode.x(canvasWidth / 2);
        footerTextNode.y(footerY + footerHeight / 2);
        footerTextNode.offsetX(footerTextNode.width() / 2);
        footerTextNode.offsetY(footerTextNode.height() / 2);
    }

    // Recalculer la position du logo du footer
    if (footerTextNode && footerLogoNode) {
        const textWidth = footerTextNode.width();
        const textX = canvasWidth / 2;
        const textRight = textX + textWidth / 2;
        const newLogoX = textRight + 15;
        footerLogoNode.x(newLogoX);
        const logoHeight = footerLogoNode.height();
        footerLogoNode.y(footerY + (footerHeight - logoHeight) / 2);
    }

    // Recalculer la position du rectangle de fond du footer
    const allNodes = layer.getChildren();
    const footerRect = allNodes.find((node: any) => {
        const nodeId = typeof node.id === 'function' ? node.id() : node.id;
        return nodeId && nodeId.includes && nodeId.includes('footer-bg');
    });
    if (footerRect) {
        footerRect.y(footerY);
        footerRect.width(canvasWidth);
    }

    layer.draw();
};

// Add image to temporary stage
const addImageToTempStage = async (layer: any, element: any, Konva: any): Promise<any> => {
    return new Promise((resolve, reject) => {
        const imageObj = new Image();
        imageObj.crossOrigin = 'anonymous';

        imageObj.onload = () => {
            try {
                const konvaImage = new Konva.Image({
                    x: element.x || 0,
                    y: element.y || 0,
                    image: imageObj,
                    width: element.width || imageObj.width,
                    height: element.height || imageObj.height,
                    rotation: element.rotation || 0,
                    opacity: element.opacity !== undefined ? element.opacity : 1,
                });

                if (element.id) {
                    konvaImage.id(element.id);
                }

                layer.add(konvaImage);

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

// Add text to temporary stage
const addTextToTempStage = (layer: any, element: any, Konva: any): any => {
    const isFooterText = element.id && element.id.includes('footer-text');

    const konvaText = new Konva.Text({
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
        return null;
    }
};

// Add shape to temporary stage
const addShapeToTempStage = (layer: any, element: any, Konva: any) => {
    let konvaShape: any = null;

    switch (element.type) {
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
            break;
        case 'ellipse':
            const radiusX = element.radiusX !== undefined ? element.radiusX : 50;
            const radiusY = element.radiusY !== undefined ? element.radiusY : 50;
            const ellipseGroup = new Konva.Group({
                x: (element.x || 0) - radiusX,
                y: (element.y || 0) - radiusY,
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

// Fonction pour charger les éléments dans un stage Konva temporaire
const loadElementsToTempStage = async (layer: any, layout: any) => {
    if (!layout.layout_data || !Array.isArray(layout.layout_data)) {
        return;
    }

    // Charger Konva depuis CDN si nécessaire
    if (!(window as any).Konva) {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/konva@9/konva.min.js';
        document.head.appendChild(script);
        await new Promise((resolve, reject) => {
            script.onload = resolve;
            script.onerror = reject;
        });
    }

    const Konva = (window as any).Konva;
    let footerTextNode: any = null;
    let footerLogoNode: any = null;

    // Charger tous les éléments
    for (const element of layout.layout_data) {
        try {
            if (element.type === 'image' && element.imageUrl) {
                const result = await addImageToTempStage(layer, element, Konva);
                if (result && element.id && element.id.includes('footer-logo')) {
                    footerLogoNode = result;
                }
            } else if (element.type === 'text' && element.text) {
                const result = addTextToTempStage(layer, element, Konva);
                if (result && element.id && element.id.includes('footer-text')) {
                    footerTextNode = result;
                }
            } else if (['rect', 'ellipse', 'line', 'arrow', 'highlight'].includes(element.type)) {
                addShapeToTempStage(layer, element, Konva);
            }
        } catch (error) {
        }
    }

    // Attendre un peu pour que tous les éléments soient rendus
    await new Promise(resolve => setTimeout(resolve, 100));

    // Recalculer la position du footer
    recalculateFooterPositionInTempStage(layer, layout, footerTextNode, footerLogoNode);
};

// Export PDF for free sessions (using layout)
const exportFreeSessionPdf = async (session: TrainingSessionHistory, shouldPrint: boolean = false) => {
    try {
        // Charger le layout depuis le serveur
        const layoutResponse = await fetch(`/sessions/${session.id}/layout`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });

        if (!layoutResponse.ok) {
            notifyError('Impossible de charger la mise en page de la séance.');
            return;
        }

        const layoutData = await layoutResponse.json();
        const layout = layoutData.layout;

        if (!layout || !layout.layout_data) {
            notifyError('Aucune mise en page disponible pour cette séance.');
            return;
        }

        notifySuccess('Génération du PDF en cours...');

        // Charger Konva depuis CDN si nécessaire
        if (!(window as any).Konva) {
            const konvaScript = document.createElement('script');
            konvaScript.src = 'https://cdn.jsdelivr.net/npm/konva@9/konva.min.js';
            document.head.appendChild(konvaScript);
            await new Promise((resolve, reject) => {
                konvaScript.onload = resolve;
                konvaScript.onerror = reject;
            });
        }

        const Konva = (window as any).Konva;

        // Créer un stage Konva temporaire pour l'export
        const tempContainer = document.createElement('div');
        tempContainer.style.position = 'absolute';
        tempContainer.style.left = '-9999px';
        tempContainer.style.width = `${layout.canvas_width}px`;
        tempContainer.style.height = `${layout.canvas_height}px`;
        document.body.appendChild(tempContainer);

        const tempStage = new Konva.Stage({
            container: tempContainer,
            width: layout.canvas_width,
            height: layout.canvas_height,
        });

        const tempLayer = new Konva.Layer();
        tempStage.add(tempLayer);

        // Charger tous les éléments dans le stage temporaire
        await loadElementsToTempStage(tempLayer, layout);

        // Redessiner le layer
        tempLayer.draw();

        // Convert canvas to image with high quality
        const dataURL = tempStage.toDataURL({
            pixelRatio: 2,
            mimeType: 'image/png',
            quality: 1,
        });

        // Nettoyer le stage temporaire
        tempStage.destroy();
        document.body.removeChild(tempContainer);

        // Load jsPDF from CDN
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

        // Convertir les dimensions du canvas en mm (1px = 0.264583mm à 96 DPI)
        const pxToMm = 0.264583;
        const pdfWidth = layout.canvas_width * pxToMm;
        const pdfHeight = layout.canvas_height * pxToMm;

        // Create PDF with exact canvas dimensions (in mm)
        const pdf = new jsPDF({
            orientation: pdfHeight > pdfWidth ? 'portrait' : 'landscape',
            unit: 'mm',
            format: [pdfWidth, pdfHeight],
        });

        // Les dimensions de l'image correspondent exactement au PDF
        const imgWidth = pdfWidth;
        const imgHeight = pdfHeight;

        // Positionner l'image à (0, 0) pour qu'elle remplisse toute la page
        pdf.addImage(dataURL, 'PNG', 0, 0, imgWidth, imgHeight);

        // Generate filename
        const name = session.name || 'mise-en-page';
        const fileName = `${name.replace(/[^a-z0-9]/gi, '-').toLowerCase()}-${Date.now()}.pdf`;

        if (shouldPrint) {
            // Pour l'impression, ouvrir dans un nouvel onglet et déclencher l'impression
            const pdfBlob = pdf.output('blob');
            const url = window.URL.createObjectURL(pdfBlob);
            const printWindow = window.open(url, '_blank');

            if (printWindow) {
                printWindow.onload = () => {
                    setTimeout(() => {
                        printWindow.print();
                    }, 250);
                };
            } else {
                window.open(url, '_blank');
                notifyError('Veuillez autoriser les popups pour cette fonctionnalité.', 'Information');
            }

            setTimeout(() => {
                window.URL.revokeObjectURL(url);
            }, 1000);
        } else {
            // Download the PDF
            pdf.save(fileName);
        }

        // Remove script tag seulement si on l'a créé
        if (script) {
            document.head.removeChild(script);
        }

        notifySuccess('PDF généré avec succès');
    } catch (error: any) {
        notifyError('Erreur lors de l\'export PDF: ' + (error.message || 'Erreur inconnue'));
    }
};

const handleDownloadPdf = async (session: TrainingSessionHistory) => {
    if (!isPro.value) {
        isUpgradeModalOpen.value = true;
        return;
    }
    
    // Pour les séances libres, générer le PDF depuis le layout
    if (session.has_custom_layout) {
        await exportFreeSessionPdf(session);
        return;
    }
    
    window.open(`/sessions/${session.id}/pdf`, '_blank');
};

const handlePrint = async (session: TrainingSessionHistory) => {
    if (!isPro.value) {
        isUpgradeModalOpen.value = true;
        return;
    }
    
    // Pour les séances libres, générer le PDF et l'imprimer
    if (session.has_custom_layout) {
        await exportFreeSessionPdf(session, true);
        return;
    }
    
    // Récupérer le token CSRF
    const getCsrfToken = () => {
        const propsToken = (page.props as any).csrfToken;
        if (propsToken) return propsToken;
        
        const cookies = document.cookie.split(';');
        for (const cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'XSRF-TOKEN') {
                return decodeURIComponent(value);
            }
        }
        
        return '';
    };
    
    const csrfToken = getCsrfToken();
    
    if (!csrfToken) {
        notifyError('Token CSRF manquant. Veuillez rafraîchir la page.', 'Erreur');
        return;
    }

    // Récupérer le PDF via fetch
    fetch(`/sessions/${session.id}/pdf`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/pdf',
        },
        credentials: 'include',
    })
    .then(async response => {
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Erreur ${response.status}: ${errorText || 'Erreur lors de la génération du PDF'}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && !contentType.includes('application/pdf')) {
            throw new Error('Le serveur n\'a pas renvoyé un PDF valide');
        }
        
        return response.blob();
    })
    .then(blob => {
        if (blob.size === 0) {
            throw new Error('Le PDF généré est vide');
        }
        
        // Créer une URL blob et ouvrir dans un nouvel onglet
        const url = window.URL.createObjectURL(blob);
        const printWindow = window.open(url, '_blank');
        
        if (printWindow) {
            // Attendre que le PDF soit chargé puis déclencher l'impression
            printWindow.onload = () => {
                setTimeout(() => {
                    printWindow.print();
                }, 250);
            };
        } else {
            // Si la popup est bloquée, ouvrir dans le même onglet
            window.open(url, '_blank');
            notifyError('Veuillez autoriser les popups pour cette fonctionnalité.', 'Information');
        }
        
        // Nettoyer l'URL après un délai
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
        }, 1000);
    })
    .catch(error => {
        notifyError(error.message || 'Une erreur est survenue lors de l\'ouverture du PDF.', 'Erreur');
    });
};

const handleSendEmail = (session: TrainingSessionHistory) => {
    sessionToSend.value = session;
    isSendEmailDialogOpen.value = true;
};

const confirmSendEmail = () => {
    if (!sessionToSend.value || isSendingEmail.value) {
        return;
    }

    // Vérifier que le client a un email
    if (!props.customer.email) {
        notifyError('Ce client n\'a pas d\'adresse email.');
        isSendEmailDialogOpen.value = false;
        return;
    }

    isSendingEmail.value = true;

    router.post(`/sessions/${sessionToSend.value.id}/send-email`, {
        customer_id: props.customer.id,
        redirect_to_customer: true,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSendEmailDialogOpen.value = false;
            sessionToSend.value = null;
        },
        onFinish: () => {
            isSendingEmail.value = false;
        },
    });
};

watch(isSendEmailDialogOpen, (open) => {
    if (!open) {
        sessionToSend.value = null;
        isSendingEmail.value = false;
    }
});

// Fonctions pour l'édition et la suppression du client
const handleEditCustomer = () => {
    isEditDialogOpen.value = true;
};

const handleDeleteCustomer = () => {
    isDeleteDialogOpen.value = true;
};

const confirmDeleteCustomer = () => {
    if (isDeleteProcessing.value) {
        return;
    }

    isDeleteProcessing.value = true;

    router.delete(`/customers/${props.customer.id}`, {
        preserveScroll: false,
        onSuccess: () => {
            router.visit('/customers');
        },
        onFinish: () => {
            isDeleteProcessing.value = false;
        },
    });
};

watch(isDeleteDialogOpen, (open) => {
    if (!open) {
        isDeleteProcessing.value = false;
    }
});
</script>

<template>
    <Head :title="`${pageTitle} • Client`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 pt-6 pb-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Profil client
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
                            {{ pageTitle }}
                        </h1>
                        <Badge
                            :variant="props.customer.is_active ? 'default' : 'secondary'"
                            class="text-xs"
                        >
                            {{ props.customer.is_active ? 'Actif' : 'Inactif' }}
                        </Badge>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2"
                        @click="handleEditCustomer"
                    >
                        <Edit class="size-4" />
                        <span>Modifier</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                        @click="handleDeleteCustomer"
                    >
                        <Trash2 class="size-4" />
                        <span>Supprimer</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2"
                        @click="router.visit('/customers')"
                    >
                        <ArrowLeft class="size-4" />
                        <span>Retour</span>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardContent class="space-y-3">
                        <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400">
                            Informations générales
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs uppercase text-slate-500">Email</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{ props.customer.email || '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-slate-500">Téléphone</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{ props.customer.phone || '—' }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs uppercase text-slate-500">Note interne</p>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ props.customer.internal_note || 'Aucune note enregistrée.' }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="space-y-4">
                        <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400">
                            Statistiques
                        </p>
                        <div class="grid gap-4">
                            <div>
                                <p class="text-xs text-slate-500">Séances enregistrées</p>
                                <p class="text-2xl font-semibold text-slate-900 dark:text-white">
                                    {{ totalSessions }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Dernière séance</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{ lastSessionDate ? formatDate(lastSessionDate) : 'Pas encore de séance' }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400">
                                Historique des séances
                            </p>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                {{ totalSessions }} séance{{ totalSessions > 1 ? 's' : '' }}
                            </h2>
                        </div>
                        <Badge variant="outline" class="text-xs text-slate-500">
                            {{ props.customer.is_active ? 'Profil actif' : 'Profil inactif' }}
                        </Badge>
                    </div>

                    <Separator />

                    <div class="space-y-3">
                        <div
                            v-for="session in sessionHistory"
                            :key="session.id"
                            class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm transition-colors hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900/70"
                        >
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex-1">
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        {{ formatSessionDate(session) }}
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <p class="text-base font-semibold text-slate-900 dark:text-white">
                                            {{ session.name || 'Séance sans titre' }}
                                        </p>
                                        <Badge
                                            v-if="session.has_custom_layout"
                                            variant="default"
                                            class="bg-blue-600 text-white text-xs px-2 py-0.5 flex items-center gap-1"
                                        >
                                            <Layout class="size-3" />
                                            Libre
                                        </Badge>
                                    </div>
                                </div>
                                <Badge
                                    v-if="!session.has_custom_layout"
                                    variant="outline"
                                    class="text-xs text-slate-500"
                                >
                                    {{ (session.exercises_count ?? 0) }} exercice{{ (session.exercises_count ?? 0) > 1 ? 's' : '' }}
                                </Badge>
                            </div>
                            <p v-if="session.notes" class="text-sm text-slate-600 dark:text-slate-300 mt-2">
                                {{ session.notes }}
                            </p>
                            <p v-else class="text-xs text-slate-400 mt-2">
                                Aucune note sur cette séance.
                            </p>
                            <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 text-xs"
                                    @click="handleViewSession(session)"
                                >
                                    <Eye class="size-3.5 mr-1.5" />
                                    Consulter
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 text-xs"
                                    @click="handleDownloadPdf(session)"
                                >
                                    <Download class="size-3.5 mr-1.5" />
                                    PDF
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 text-xs"
                                    @click="handlePrint(session)"
                                >
                                    <Printer class="size-3.5 mr-1.5" />
                                    Imprimer
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 text-xs"
                                    @click="handleSendEmail(session)"
                                    :disabled="!props.customer.email"
                                >
                                    <Mail class="size-3.5 mr-1.5" />
                                    Envoyer par mail
                                </Button>
                            </div>
                        </div>

                        <p v-if="sessionHistory.length === 0" class="text-sm text-slate-500 dark:text-slate-400">
                            Aucun entraînement enregistré pour l’instant. Commencez par créer une session depuis l’accueil.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Modal d'envoi par email -->
            <Dialog v-model:open="isSendEmailDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Envoyer la séance par email</DialogTitle>
                        <DialogDescription>
                            La séance "{{ sessionToSend?.name || 'Sans titre' }}" sera envoyée par email à {{ props.customer.first_name }} {{ props.customer.last_name }} ({{ props.customer.email }}).
                        </DialogDescription>
                    </DialogHeader>
                    
                    <DialogFooter>
                        <Button
                            variant="outline"
                            @click="isSendEmailDialogOpen = false"
                            :disabled="isSendingEmail"
                        >
                            Annuler
                        </Button>
                        <Button
                            @click="confirmSendEmail"
                            :disabled="isSendingEmail"
                        >
                            {{ isSendingEmail ? 'Envoi en cours...' : 'Envoyer' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Dialog d'édition du client -->
            <CustomerEditDialog
                v-model:open="isEditDialogOpen"
                :customer="props.customer"
            />

            <!-- Dialog de suppression du client -->
            <CustomerDeleteDialog
                v-model:open="isDeleteDialogOpen"
                :customer="props.customer"
                :loading="isDeleteProcessing"
                @confirm="confirmDeleteCustomer"
            />

            <!-- Modal d'abonnement -->
            <UpgradeModal
                v-model:open="isUpgradeModalOpen"
                feature="L'export PDF est réservé aux abonnés Pro. Passez à Pro pour exporter vos séances en PDF."
            />
        </div>
    </AppLayout>
</template>

