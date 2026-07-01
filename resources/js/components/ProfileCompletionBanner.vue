<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { UserCog, X } from 'lucide-vue-next';

const page = usePage();

const user = computed(() => (page.props.auth as any)?.user ?? null);
const isCoach = computed(() => user.value?.isCoach ?? false);
const completion = computed(() => user.value?.profileCompletion ?? null);

// Rappel masqué manuellement pour la session en cours.
const dismissed = ref(false);

// Ne pas afficher le rappel sur la page d'édition du profil elle-même.
const onProfilePage = computed(() =>
    typeof window !== 'undefined' && window.location.pathname.startsWith('/mon-profil-coach'),
);

const visible = computed(
    () =>
        isCoach.value &&
        completion.value !== null &&
        !completion.value.is_complete &&
        !dismissed.value &&
        !onProfilePage.value,
);
</script>

<template>
    <div
        v-if="visible"
        class="flex items-center gap-3 border-b border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200"
    >
        <UserCog class="size-4 shrink-0" />
        <span class="flex-1">
            Votre profil coach est complété à {{ completion?.percentage }}%.
            <Link href="/mon-profil-coach" class="font-medium underline underline-offset-2">
                Complétez-le
            </Link>
            pour être visible dans l'annuaire.
        </span>
        <button
            type="button"
            class="shrink-0 rounded p-1 hover:bg-amber-100 dark:hover:bg-amber-900/50"
            aria-label="Masquer"
            @click="dismissed = true"
        >
            <X class="size-4" />
        </button>
    </div>
</template>
