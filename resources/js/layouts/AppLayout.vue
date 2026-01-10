<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import NotificationTemplate from '@/components/NotificationTemplate.vue';
import AnnouncementModal from '@/components/AnnouncementModal.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
    <!-- Modal d'annonces pour les mises à jour -->
    <AnnouncementModal />
    <!-- Composant de notifications global avec template personnalisé -->
    <notifications position="top right" :width="400">
        <template #body="props">
            <NotificationTemplate
                :type="props.item.type || 'info'"
                :title="props.item.title"
                :text="props.item.text"
                @close="props.close"
            />
        </template>
    </notifications>
</template>
