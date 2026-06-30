<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ConversationList from '@/components/messaging/ConversationList.vue';
import UpgradeModal from '@/components/UpgradeModal.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Check, SendHorizontal, UserPlus } from 'lucide-vue-next';
import { useNotifications } from '@/composables/useNotifications';

type ChatMessage = {
    id: number;
    body: string;
    mine: boolean;
    sender_name: string | null;
    created_at: string;
    read: boolean;
    read_at: string | null;
};

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

const props = defineProps<{
    conversations: ConversationItem[];
    contacts: Contact[];
    conversation: {
        id: number;
        other_name: string | null;
        other_avatar: string | null;
        is_coach?: boolean;
        is_customer?: boolean;
    };
    messages: ChatMessage[];
}>();

const page = usePage();
const { success: notifySuccess, error: notifyError } = useNotifications();

// L'utilisateur connecté est-il un coach (côté coach de cette conversation) ?
const isPro = computed(() => (page.props.auth as any)?.user?.isPro ?? false);
const canAddCustomer = computed(() => props.conversation.is_coach && !props.conversation.is_customer);
const isUpgradeModalOpen = ref(false);
const addingCustomer = ref(false);

const addCustomer = () => {
    if (!isPro.value) {
        isUpgradeModalOpen.value = true;
        return;
    }
    if (addingCustomer.value) return;
    addingCustomer.value = true;
    router.post(
        `/messages/${props.conversation.id}/add-customer`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                addingCustomer.value = false;
            },
        },
    );
};

// Écouter les messages flash et les convertir en notifications
const shownFlashMessages = ref(new Set<string>());

watch(
    () => (page.props as any).flash,
    (flash) => {
        if (!flash) return;

        const successKey = flash.success ? `success-${flash.success}` : null;
        const errorKey = flash.error ? `error-${flash.error}` : null;

        if (successKey && !shownFlashMessages.value.has(successKey)) {
            shownFlashMessages.value.add(successKey);
            nextTick(() => notifySuccess(flash.success));
            setTimeout(() => shownFlashMessages.value.delete(successKey), 4500);
        }

        if (errorKey && !shownFlashMessages.value.has(errorKey)) {
            shownFlashMessages.value.add(errorKey);
            nextTick(() => notifyError(flash.error));
            setTimeout(() => shownFlashMessages.value.delete(errorKey), 6500);
        }
    },
    { immediate: true },
);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Messagerie', href: '/messages' },
    { title: props.conversation.other_name ?? 'Conversation', href: `/messages/${props.conversation.id}` },
];

const form = useForm({ body: '' });
const threadEnd = ref<HTMLElement | null>(null);

const scrollToBottom = () => {
    nextTick(() => threadEnd.value?.scrollIntoView({ behavior: 'smooth' }));
};

onMounted(scrollToBottom);
watch(() => props.messages.length, scrollToBottom);
watch(() => props.conversation.id, scrollToBottom);

const send = () => {
    if (!form.body.trim()) return;
    form.post(`/messages/${props.conversation.id}/reply`, {
        preserveScroll: true,
        onSuccess: () => form.reset('body'),
    });
};

const initials = (name: string | null) =>
    (name ?? '?')
        .split(' ')
        .map((p) => p.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();

const otherInitials = computed(() => initials(props.conversation.other_name));

// Index du dernier message envoyé par moi et lu par l'autre (indicateur « Vu »).
const lastSeenMineIndex = computed(() => {
    for (let i = props.messages.length - 1; i >= 0; i--) {
        if (props.messages[i].mine && props.messages[i].read) return i;
    }
    return -1;
});

const sameDay = (a: string, b: string) =>
    new Date(a).toDateString() === new Date(b).toDateString();

const isFirstOfGroup = (i: number) => {
    const prev = props.messages[i - 1];
    return !prev || prev.mine !== props.messages[i].mine;
};
const isLastOfGroup = (i: number) => {
    const next = props.messages[i + 1];
    return !next || next.mine !== props.messages[i].mine;
};
const showDaySeparator = (i: number) => {
    const prev = props.messages[i - 1];
    return !prev || !sameDay(prev.created_at, props.messages[i].created_at);
};

const dayLabel = (iso: string) => {
    const d = new Date(iso);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return "Aujourd'hui";
    if (d.toDateString() === yesterday.toDateString()) return 'Hier';
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
};

const timeLabel = (iso: string) =>
    new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Conversation avec ${conversation.other_name}`" />

        <div class="h-[calc(100vh-7rem)] w-full p-4">
            <div class="flex h-full overflow-hidden rounded-2xl border bg-card">
                <!-- Liste (masquée sur mobile) -->
                <aside class="hidden w-[360px] shrink-0 flex-col border-r md:flex lg:w-[400px]">
                    <ConversationList
                        :conversations="conversations"
                        :contacts="contacts"
                        :active-id="conversation.id"
                    />
                </aside>

                <!-- Fil de discussion -->
                <section class="flex min-w-0 flex-1 flex-col">
                    <!-- En-tête -->
                    <header class="flex items-center gap-3 border-b px-4 py-3">
                        <Link
                            href="/messages"
                            class="rounded-full p-1.5 text-muted-foreground transition hover:bg-accent hover:text-foreground md:hidden"
                        >
                            <ArrowLeft class="size-5" />
                        </Link>
                        <div class="size-10 overflow-hidden rounded-full">
                            <img
                                v-if="conversation.other_avatar"
                                :src="conversation.other_avatar"
                                :alt="conversation.other_name ?? 'Coach'"
                                class="size-full object-cover"
                            />
                            <div
                                v-else
                                class="flex size-full items-center justify-center bg-gradient-to-br from-blue-500 to-blue-600 text-sm font-semibold text-white"
                            >
                                {{ otherInitials }}
                            </div>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold leading-tight">{{ conversation.other_name }}</p>
                            <p class="text-xs text-muted-foreground">Messagerie</p>
                        </div>

                        <!-- Ajouter le client (coach uniquement) -->
                        <Button
                            v-if="canAddCustomer"
                            size="sm"
                            class="ml-auto shrink-0 bg-blue-600 text-white hover:bg-blue-700"
                            :disabled="addingCustomer"
                            @click="addCustomer"
                        >
                            <Spinner v-if="addingCustomer" class="mr-2 size-4" />
                            <UserPlus v-else class="mr-2 size-4" />
                            Ajouter le client
                        </Button>
                        <span
                            v-else-if="conversation.is_coach && conversation.is_customer"
                            class="ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400"
                        >
                            <Check class="size-3.5" />
                            Dans vos clients
                        </span>
                    </header>

                    <!-- Messages -->
                    <div class="flex-1 space-y-1 overflow-y-auto bg-muted/20 px-4 py-4">
                        <div
                            v-if="!messages.length"
                            class="flex h-full flex-col items-center justify-center gap-2 text-center text-sm text-muted-foreground"
                        >
                            <div class="size-16 overflow-hidden rounded-full">
                                <img
                                    v-if="conversation.other_avatar"
                                    :src="conversation.other_avatar"
                                    :alt="conversation.other_name ?? 'Coach'"
                                    class="size-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex size-full items-center justify-center bg-gradient-to-br from-blue-500 to-blue-600 text-xl font-semibold text-white"
                                >
                                    {{ otherInitials }}
                                </div>
                            </div>
                            <p class="mt-1 font-medium text-foreground">{{ conversation.other_name }}</p>
                            <p>Écrivez le premier message pour démarrer la conversation.</p>
                        </div>

                        <template v-for="(m, i) in messages" :key="m.id">
                            <div v-if="showDaySeparator(i)" class="flex justify-center py-3">
                                <span class="rounded-full bg-background px-3 py-1 text-xs font-medium text-muted-foreground shadow-sm">
                                    {{ dayLabel(m.created_at) }}
                                </span>
                            </div>

                            <div
                                class="flex items-end gap-2"
                                :class="[m.mine ? 'justify-end' : 'justify-start', isLastOfGroup(i) ? 'mb-2' : 'mb-0.5']"
                            >
                                <div v-if="!m.mine" class="size-7 shrink-0">
                                    <template v-if="isLastOfGroup(i)">
                                        <img
                                            v-if="conversation.other_avatar"
                                            :src="conversation.other_avatar"
                                            :alt="conversation.other_name ?? 'Coach'"
                                            class="size-7 rounded-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex size-7 items-center justify-center rounded-full bg-gradient-to-br from-zinc-400 to-zinc-500 text-[10px] font-semibold text-white"
                                        >
                                            {{ otherInitials }}
                                        </div>
                                    </template>
                                </div>

                                <div
                                    class="max-w-[72%] px-3.5 py-2 text-sm leading-relaxed shadow-sm"
                                    :class="[
                                        m.mine ? 'bg-blue-600 text-white' : 'border bg-card text-foreground',
                                        m.mine
                                            ? [
                                                  'rounded-2xl rounded-r-md',
                                                  isFirstOfGroup(i) ? 'rounded-tr-2xl' : 'rounded-tr-md',
                                                  isLastOfGroup(i) ? 'rounded-br-2xl' : 'rounded-br-md',
                                              ]
                                            : [
                                                  'rounded-2xl rounded-l-md',
                                                  isFirstOfGroup(i) ? 'rounded-tl-2xl' : 'rounded-tl-md',
                                                  isLastOfGroup(i) ? 'rounded-bl-2xl' : 'rounded-bl-md',
                                              ],
                                    ]"
                                    :title="timeLabel(m.created_at)"
                                >
                                    <p class="whitespace-pre-line break-words">{{ m.body }}</p>
                                </div>
                            </div>

                            <div
                                v-if="isLastOfGroup(i)"
                                class="px-1 text-[10px] text-muted-foreground"
                                :class="m.mine ? 'text-right' : 'pl-10 text-left'"
                            >
                                {{ timeLabel(m.created_at) }}
                            </div>

                            <!-- Indicateur « Vu » sous le dernier message lu que j'ai envoyé -->
                            <div
                                v-if="i === lastSeenMineIndex"
                                class="px-1 pb-1 text-right text-[10px] font-semibold text-blue-600"
                            >
                                Vu{{ m.read_at ? ' à ' + timeLabel(m.read_at) : '' }}
                            </div>
                        </template>

                        <div ref="threadEnd" />
                    </div>

                    <!-- Composer -->
                    <div class="border-t bg-background px-3 py-3">
                        <form class="flex items-end gap-2" @submit.prevent="send">
                            <div class="flex flex-1 items-end rounded-3xl border bg-muted/40 px-4 py-2 focus-within:ring-2 focus-within:ring-blue-500/40">
                                <textarea
                                    v-model="form.body"
                                    rows="1"
                                    placeholder="Écrivez un message…"
                                    class="max-h-32 w-full resize-none bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                                    @keydown.enter.exact.prevent="send"
                                    @input="(e) => {
                                        const el = e.target as HTMLTextAreaElement;
                                        el.style.height = 'auto';
                                        el.style.height = Math.min(el.scrollHeight, 128) + 'px';
                                    }"
                                />
                            </div>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.body.trim()"
                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Envoyer"
                            >
                                <Spinner v-if="form.processing" class="size-4" />
                                <SendHorizontal v-else class="size-5" />
                            </button>
                        </form>
                    </div>
                </section>
            </div>
        </div>

        <UpgradeModal
            v-model:open="isUpgradeModalOpen"
            feature="L'ajout de clients est réservé aux abonnés Pro. Passez à Pro pour ajouter ce client à votre liste."
        />
    </AppLayout>
</template>
