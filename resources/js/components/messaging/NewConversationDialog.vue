<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Search } from 'lucide-vue-next';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Contact = {
    user_id: number;
    name: string | null;
    avatar: string | null;
};

const props = defineProps<{
    open: boolean;
    contacts: Contact[];
    /** Libellé adapté au rôle (ex. « coach » ou « client »). */
    targetLabel?: string;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const search = ref('');
const starting = ref<number | null>(null);

watch(
    () => props.open,
    (open) => {
        if (open) search.value = '';
    },
);

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return props.contacts;
    return props.contacts.filter((c) => (c.name ?? '').toLowerCase().includes(term));
});

const initials = (name: string | null) =>
    (name ?? '?')
        .split(' ')
        .map((p) => p.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();

const start = (contact: Contact) => {
    if (starting.value !== null) return;
    starting.value = contact.user_id;
    router.post(
        `/messages/with/${contact.user_id}`,
        {},
        {
            onFinish: () => {
                starting.value = null;
                emit('update:open', false);
            },
        },
    );
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Nouveau message</DialogTitle>
                <DialogDescription>
                    Choisissez un {{ targetLabel ?? 'contact' }} dans votre liste pour démarrer
                    une conversation.
                </DialogDescription>
            </DialogHeader>

            <div class="relative">
                <Search
                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    v-model="search"
                    type="search"
                    :placeholder="`Rechercher un ${targetLabel ?? 'contact'}…`"
                    class="w-full rounded-full border bg-muted/40 py-2 pl-9 pr-3 text-sm outline-none focus:ring-2 focus:ring-blue-500/40"
                />
            </div>

            <div class="max-h-80 overflow-y-auto">
                <template v-if="filtered.length">
                    <button
                        v-for="c in filtered"
                        :key="c.user_id"
                        type="button"
                        :disabled="starting !== null"
                        class="flex w-full items-center gap-3 rounded-2xl p-2.5 text-left transition hover:bg-accent disabled:opacity-60"
                        @click="start(c)"
                    >
                        <div class="size-10 shrink-0 overflow-hidden rounded-full">
                            <img
                                v-if="c.avatar"
                                :src="c.avatar"
                                :alt="c.name ?? ''"
                                class="size-full object-cover"
                            />
                            <div
                                v-else
                                class="flex size-full items-center justify-center bg-gradient-to-br from-zinc-400 to-zinc-500 text-sm font-semibold text-white"
                            >
                                {{ initials(c.name) }}
                            </div>
                        </div>
                        <span class="min-w-0 flex-1 truncate font-medium">{{ c.name }}</span>
                    </button>
                </template>

                <div
                    v-else-if="contacts.length"
                    class="px-3 py-8 text-center text-sm text-muted-foreground"
                >
                    Aucun résultat pour « {{ search }} ».
                </div>

                <div v-else class="px-3 py-8 text-center text-sm text-muted-foreground">
                    Aucun contact disponible pour le moment.
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
