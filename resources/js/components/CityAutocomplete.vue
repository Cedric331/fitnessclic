<script setup lang="ts">
import { ref, watch, onBeforeUnmount, type HTMLAttributes } from 'vue';
import { onClickOutside, refDebounced } from '@vueuse/core';
import { MapPin, Loader2 } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import { searchCommunes, type Commune } from '@/composables/useCommuneSearch';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        id?: string;
        placeholder?: string;
        class?: HTMLAttributes['class'];
        showIcon?: boolean;
    }>(),
    {
        modelValue: '',
        placeholder: 'Ville',
        showIcon: true,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'select', commune: Commune): void;
}>();

const query = ref(props.modelValue ?? '');
const debouncedQuery = refDebounced(query, 250);

const suggestions = ref<Commune[]>([]);
const open = ref(false);
const loading = ref(false);
const activeIndex = ref(-1);
const root = ref<HTMLElement | null>(null);

let controller: AbortController | null = null;
// Vrai juste après une sélection, pour éviter de relancer une recherche.
let justSelected = false;

// Synchronise quand la valeur est modifiée depuis le parent (ex. reset).
watch(
    () => props.modelValue,
    (value) => {
        if (value !== query.value) {
            query.value = value ?? '';
        }
    },
);

watch(query, (value) => {
    emit('update:modelValue', value);
});

watch(debouncedQuery, async (value) => {
    if (justSelected) {
        justSelected = false;
        return;
    }

    const term = value.trim();
    if (term.length < 2) {
        suggestions.value = [];
        open.value = false;
        return;
    }

    controller?.abort();
    controller = new AbortController();
    loading.value = true;

    try {
        suggestions.value = await searchCommunes(term, controller.signal);
        activeIndex.value = -1;
        open.value = suggestions.value.length > 0;
    } catch (error) {
        if ((error as Error)?.name !== 'AbortError') {
            suggestions.value = [];
            open.value = false;
        }
    } finally {
        loading.value = false;
    }
});

const select = (commune: Commune) => {
    justSelected = true;
    query.value = commune.city;
    open.value = false;
    suggestions.value = [];
    activeIndex.value = -1;
    emit('select', commune);
};

const onKeydown = (event: KeyboardEvent) => {
    if (!open.value || suggestions.value.length === 0) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex.value = (activeIndex.value + 1) % suggestions.value.length;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value =
            activeIndex.value <= 0 ? suggestions.value.length - 1 : activeIndex.value - 1;
    } else if (event.key === 'Enter') {
        if (activeIndex.value >= 0) {
            event.preventDefault();
            select(suggestions.value[activeIndex.value]);
        }
    } else if (event.key === 'Escape') {
        open.value = false;
    }
};

const onFocus = () => {
    if (suggestions.value.length > 0) {
        open.value = true;
    }
};

const label = (commune: Commune) =>
    commune.postalCode ? `${commune.city} (${commune.postalCode})` : commune.city;

onClickOutside(root, () => {
    open.value = false;
});

onBeforeUnmount(() => controller?.abort());
</script>

<template>
    <div ref="root" class="relative">
        <MapPin
            v-if="showIcon"
            class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <input
            :id="id"
            v-model="query"
            type="text"
            role="combobox"
            autocomplete="off"
            :aria-expanded="open"
            aria-autocomplete="list"
            :placeholder="placeholder"
            :class="
                cn(
                    'placeholder:text-muted-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                    showIcon ? 'pl-9' : '',
                    props.class,
                )
            "
            @keydown="onKeydown"
            @focus="onFocus"
        />
        <Loader2
            v-if="loading"
            class="absolute right-3 top-1/2 size-4 -translate-y-1/2 animate-spin text-muted-foreground"
        />

        <ul
            v-if="open && suggestions.length"
            class="absolute z-50 mt-1 max-h-72 w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
        >
            <li
                v-for="(commune, index) in suggestions"
                :key="`${commune.city}-${commune.postalCode}-${index}`"
                :class="
                    cn(
                        'flex cursor-pointer items-center justify-between gap-2 rounded-sm px-2 py-1.5 text-sm',
                        index === activeIndex ? 'bg-accent text-accent-foreground' : 'hover:bg-accent',
                    )
                "
                @mousedown.prevent="select(commune)"
                @mouseenter="activeIndex = index"
            >
                <span class="truncate">{{ label(commune) }}</span>
                <span
                    v-if="commune.department"
                    class="shrink-0 text-xs text-muted-foreground"
                >
                    {{ commune.department }}
                </span>
            </li>
        </ul>
    </div>
</template>
