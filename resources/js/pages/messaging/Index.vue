<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ConversationList from '@/components/messaging/ConversationList.vue';
import { type BreadcrumbItem } from '@/types';
import { MessageCircle } from 'lucide-vue-next';

type ConversationItem = {
    id: number;
    other_name: string | null;
    last_message: string | null;
    last_message_at: string | null;
    unread_count: number;
};

type Contact = {
    user_id: number;
    name: string | null;
    avatar: string | null;
};

defineProps<{ conversations: ConversationItem[]; contacts: Contact[] }>();

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Messagerie', href: '/messages' }];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Messagerie" />

        <div class="h-[calc(100vh-7rem)] w-full p-4">
            <div class="flex h-full overflow-hidden rounded-2xl border bg-card">
                <!-- Liste (pleine largeur sur mobile) -->
                <aside class="flex w-full flex-col md:w-[360px] md:shrink-0 md:border-r lg:w-[400px]">
                    <ConversationList :conversations="conversations" :contacts="contacts" />
                </aside>

                <!-- Panneau droit : invite à sélectionner (desktop) -->
                <section
                    class="hidden flex-1 flex-col items-center justify-center gap-3 bg-muted/20 p-8 text-center text-muted-foreground md:flex"
                >
                    <div class="flex size-16 items-center justify-center rounded-full bg-muted">
                        <MessageCircle class="size-8" />
                    </div>
                    <p class="font-medium text-foreground">Vos messages</p>
                    <p class="max-w-xs text-sm">
                        Sélectionnez une conversation à gauche pour afficher les messages.
                    </p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
