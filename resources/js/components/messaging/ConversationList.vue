<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { PenSquare, Search } from 'lucide-vue-next';
import NewConversationDialog from '@/components/messaging/NewConversationDialog.vue';
import type { AppPageProps } from '@/types';

type ConversationItem = {
    id: number;
    other_name: string | null;
    other_avatar: string | null;
    last_message: string | null;
    last_message_at: string | null;
    unread_count: number;
};

type Contact = {
    user_id: number;
    name: string | null;
    avatar: string | null;
};

const props = withDefaults(
    defineProps<{
        conversations: ConversationItem[];
        contacts?: Contact[];
        activeId?: number | null;
    }>(),
    { contacts: () => [] },
);

const page = usePage<AppPageProps>();
const targetLabel = computed(() =>
    page.props.auth?.user?.role === 'coach' ? 'client' : 'coach',
);

const search = ref('');
const newConversationOpen = ref(false);

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return props.conversations;
    return props.conversations.filter(
        (c) =>
            (c.other_name ?? '').toLowerCase().includes(term) ||
            (c.last_message ?? '').toLowerCase().includes(term),
    );
});

const formatTime = (iso: string | null) => {
    if (!iso) return '';
    const d = new Date(iso);
    const sameDay = d.toDateString() === new Date().toDateString();
    return sameDay
        ? d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
        : d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
};

const initials = (name: string | null) =>
    (name ?? '?')
        .split(' ')
        .map((p) => p.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();
</script>

<template>
    <div class="flex h-full flex-col">
        <!-- En-tête + recherche -->
        <div class="space-y-3 border-b p-4">
            <div class="flex items-center justify-between gap-2">
                <h1 class="text-xl font-bold tracking-tight">Messagerie</h1>
                <button
                    type="button"
                    class="flex items-center gap-1.5 rounded-full bg-blue-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-blue-700"
                    @click="newConversationOpen = true"
                >
                    <PenSquare class="size-4" />
                    <span class="hidden sm:inline">Nouveau</span>
                </button>
            </div>
            <div class="relative">
                <Search
                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Rechercher une conversation…"
                    class="w-full rounded-full border bg-muted/40 py-2 pl-9 pr-3 text-sm outline-none focus:ring-2 focus:ring-blue-500/40"
                />
            </div>
        </div>

        <!-- Liste -->
        <div class="flex-1 overflow-y-auto p-2">
            <template v-if="filtered.length">
                <Link
                    v-for="c in filtered"
                    :key="c.id"
                    :href="`/messages/${c.id}`"
                    class="flex items-center gap-3 rounded-2xl p-2.5 transition"
                    :class="
                        c.id === activeId
                            ? 'bg-blue-50 dark:bg-blue-950/40'
                            : 'hover:bg-accent'
                    "
                >
                    <div class="relative shrink-0">
                        <div class="size-12 overflow-hidden rounded-full">
                            <img
                                v-if="c.other_avatar"
                                :src="c.other_avatar"
                                :alt="c.other_name ?? 'Coach'"
                                class="size-full object-cover"
                            />
                            <div
                                v-else
                                class="flex size-full items-center justify-center text-sm font-semibold text-white"
                                :class="
                                    c.unread_count > 0
                                        ? 'bg-gradient-to-br from-blue-500 to-blue-600'
                                        : 'bg-gradient-to-br from-zinc-400 to-zinc-500'
                                "
                            >
                                {{ initials(c.other_name) }}
                            </div>
                        </div>
                        <span
                            v-if="c.unread_count > 0"
                            class="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-blue-600 px-1.5 text-[11px] font-bold text-white ring-2 ring-background"
                        >
                            {{ c.unread_count }}
                        </span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <span
                                class="truncate"
                                :class="c.unread_count > 0 ? 'font-semibold' : 'font-medium'"
                            >
                                {{ c.other_name }}
                            </span>
                            <span
                                class="shrink-0 text-xs"
                                :class="
                                    c.unread_count > 0
                                        ? 'font-semibold text-blue-600'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ formatTime(c.last_message_at) }}
                            </span>
                        </div>
                        <p
                            class="truncate text-sm"
                            :class="
                                c.unread_count > 0
                                    ? 'font-medium text-foreground'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ c.last_message || 'Démarrez la conversation…' }}
                        </p>
                    </div>
                </Link>
            </template>

            <div
                v-else-if="conversations.length"
                class="px-3 py-8 text-center text-sm text-muted-foreground"
            >
                Aucune conversation ne correspond à « {{ search }} ».
            </div>

            <div v-else class="px-3 py-10 text-center text-sm text-muted-foreground">
                <p>Aucune conversation pour le moment.</p>
                <button
                    type="button"
                    class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-blue-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-blue-700"
                    @click="newConversationOpen = true"
                >
                    <PenSquare class="size-4" />
                    Démarrer une conversation
                </button>
            </div>
        </div>

        <NewConversationDialog
            v-model:open="newConversationOpen"
            :contacts="contacts"
            :target-label="targetLabel"
        />
    </div>
</template>
