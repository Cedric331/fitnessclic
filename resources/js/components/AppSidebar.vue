<script setup lang="ts">
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarGroup,
    SidebarGroupLabel,
} from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem, type User } from '@/types';
import { Link, usePage, router, type InertiaLinkProps } from '@inertiajs/vue3';
import { computed, toRef, ref, onMounted, onBeforeUnmount } from 'vue';
import {
    Plus,
    Dumbbell,
    User as UserIcon,
    Library,
    Tag,
    Star,
    Settings,
    LogOut,
    FileText,
    Layout,
} from 'lucide-vue-next';
import { useInitials } from '@/composables/useInitials';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import { dashboard } from '@/routes';

const page = usePage();
const user = computed(() => page.props.auth.user as User | null);
const { getInitials } = useInitials();

// Vérifier si l'utilisateur a un abonnement actif
const hasActiveSubscription = computed(() => {
    const value = user.value?.hasActiveSubscription ?? false;
    return value;
});

const mainNavItems: NavItem[] = [
    {
        title: 'Créer une Séance',
        href: '/sessions/create',
        icon: Plus,
    },
    {
        title: 'Mes Séances',
        href: '/sessions',
        icon: Dumbbell,
    },
    {
        title: 'Mes Clients',
        href: '/customers',
        icon: UserIcon,
    },
    {
        title: 'Bibliothèque',
        href: '/exercises',
        icon: Library,
    },
    {
        title: 'Catégories',
        href: '/categories',
        icon: Tag,
    },
    {
        title: 'Abonnement',
        href: '/subscription',
        icon: Star,
    },
];

const subscriptionTitle = computed(() => {
    if (user.value?.isAdmin) {
        return 'Administrateur';
    }
    return hasActiveSubscription.value ? 'Abonnement' : 'Passer à FitnessClicPro';
});

const isUpgradeButton = computed(() => {
    return !hasActiveSubscription.value;
});

const filteredNavItems = computed(() => {
    return mainNavItems;
});

const isCurrentRoute = computed(
    () => (url: NonNullable<InertiaLinkProps['href']>) => urlIsActive(url, page.url),
);

const DESKTOP_MIN_WIDTH = 1400; // 2xl breakpoint
const DESKTOP_MEDIA_QUERY = `(min-width: ${DESKTOP_MIN_WIDTH}px)`;
const isDesktopWidth = () => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia(DESKTOP_MEDIA_QUERY).matches;
};

const getStoredMode = (): 'standard' | 'libre' => {
    if (typeof window !== 'undefined') {
        // Sur tablette/mobile, forcer le mode standard (mode libre réservé au desktop)
        if (!isDesktopWidth()) {
            return 'standard';
        }
        const stored = localStorage.getItem('editMode');
        if (stored === 'libre' || stored === 'standard') {
            return stored;
        }
    }
    return 'standard';
};

const setStoredMode = (mode: 'standard' | 'libre') => {
    if (typeof window !== 'undefined') {
        localStorage.setItem('editMode', mode);
    }
};

const currentEditMode = ref<'standard' | 'libre'>(getStoredMode());

let desktopMq: MediaQueryList | null = null;
let desktopMqHandler: ((e: MediaQueryListEvent) => void) | null = null;

const enforceStandardOnNonDesktop = () => {
    if (typeof window === 'undefined') return;

    if (!isDesktopWidth()) {
        // S'assure que tout le reste de l'app (ex: /sessions/create) retombe en standard sur tablette
        setStoredMode('standard');
        currentEditMode.value = 'standard';
        return;
    }

    // Desktop: resynchroniser depuis le stockage
    currentEditMode.value = getStoredMode();
};

onMounted(() => {
    enforceStandardOnNonDesktop();

    desktopMq = window.matchMedia(DESKTOP_MEDIA_QUERY);
    desktopMqHandler = () => enforceStandardOnNonDesktop();

    // Support Safari ancien
    if (desktopMq.addEventListener) {
        desktopMq.addEventListener('change', desktopMqHandler);
    } else {
        (desktopMq as unknown as { addListener: (cb: (e: any) => void) => void }).addListener(desktopMqHandler);
    }
});

onBeforeUnmount(() => {
    if (!desktopMq || !desktopMqHandler) return;
    if (desktopMq.removeEventListener) {
        desktopMq.removeEventListener('change', desktopMqHandler);
    } else {
        (desktopMq as unknown as { removeListener: (cb: (e: any) => void) => void }).removeListener(desktopMqHandler);
    }
    desktopMq = null;
    desktopMqHandler = null;
});

const switchMode = (mode: 'standard' | 'libre') => {
    // Mode libre réservé au desktop
    if (mode === 'libre' && !isDesktopWidth()) {
        setStoredMode('standard');
        currentEditMode.value = 'standard';
        return;
    }

    setStoredMode(mode);
    currentEditMode.value = mode;
    
    if (typeof window !== 'undefined') {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/sessions/create')) {
            window.location.reload();
        }
    }
};

const handleLogout = () => {
    router.post(logout.url());
};
</script>

<template>
    <Sidebar 
        collapsible="icon" 
        variant="inset" 
        class="bg-slate-900 [&_[data-sidebar=sidebar]]:bg-slate-900 [&_[data-mobile=true]]:bg-slate-900"
    >
        <SidebarHeader class="border-b border-slate-700 pb-4">
            <SidebarMenu>
                <SidebarMenuItem>
                    <Link
                        :href="dashboard.url()"
                        class="flex items-center gap-3 px-2 py-4"
                    >
                        <!-- Logo FitnessClic -->
                        <div
                            class="flex size-10 items-center justify-center rounded-lg group-data-[collapsible=icon]:size-12"
                        >
                            <img
                                src="/assets/logo_fitnessclic.png"
                                alt="FitnessClic Logo"
                                class="h-full w-full object-contain"
                            />
                        </div>
                        <div class="flex flex-col group-data-[collapsible=icon]:hidden">
                            <span class="text-lg font-bold text-white">FitnessClic</span>
                            <span class="text-xs text-slate-400"
                                >créateur de séances</span
                            >
                        </div>
                    </Link>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="px-2 overflow-y-auto">
            <SidebarGroup>
                <SidebarGroupLabel class="text-xs uppercase text-slate-400 px-2 py-3 mb-2">
                    NAVIGATION
                </SidebarGroupLabel>
                <SidebarMenu class="space-y-1.5">
                    <!-- Items de navigation -->
                    <SidebarMenuItem
                        v-for="item in filteredNavItems"
                        :key="item.title"
                        class="py-0.5"
                    >
                        <SidebarMenuButton
                            v-if="item.title === 'Abonnement'"
                            as-child
                            :is-active="isCurrentRoute(item.href)"
                            :tooltip="subscriptionTitle"
                            :class="[
                                'h-10 px-3',
                                isUpgradeButton
                                    ? 'bg-gradient-to-r from-yellow-600/20 to-yellow-500/20 hover:from-yellow-600/30 hover:to-yellow-500/30 text-yellow-400 border border-yellow-500/30 data-[active=true]:from-yellow-600/30 data-[active=true]:to-yellow-500/30'
                                    : 'text-white hover:text-white hover:bg-slate-800 data-[active=true]:text-white data-[active=true]:bg-blue-600'
                            ]"
                        >
                            <Link :href="item.href" class="flex items-center gap-3 group-data-[collapsible=icon]:justify-center">
                                <!-- Étoile jaune remplie si abonné, sinon étoile blanche normale -->
                                <Star
                                    :class="[
                                        'size-4',
                                        hasActiveSubscription
                                            ? 'text-yellow-400 fill-yellow-400'
                                            : 'text-white fill-white'
                                    ]"
                                />
                                <span :class="['group-data-[collapsible=icon]:hidden', 'font-semibold']">{{ subscriptionTitle }}</span>
                            </Link>
                        </SidebarMenuButton>
                        <SidebarMenuButton
                            v-else
                            as-child
                            :is-active="isCurrentRoute(item.href)"
                            :tooltip="item.title"
                            class="text-white hover:bg-slate-800 data-[active=true]:text-white data-[active=true]:bg-blue-600 h-10 px-3"
                        >
                            <Link :href="item.href" class="flex items-center gap-3 group-data-[collapsible=icon]:justify-center">
                                <component
                                    :is="item.icon"
                                    :class="['size-4', 'text-white']"
                                />
                                <span :class="['group-data-[collapsible=icon]:hidden', 'text-white']">{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <!-- Toggle Mode d'édition -->
            <div class="hidden lg:block px-2 py-4 border-t border-slate-700">
                <div class="flex gap-2">
                    <Button
                        :variant="currentEditMode === 'standard' ? 'default' : 'outline'"
                        :class="[
                            'flex-1 h-9 text-sm',
                            currentEditMode === 'standard' 
                                ? 'bg-blue-600 hover:bg-blue-700 text-white border-blue-500' 
                                : 'bg-transparent hover:bg-slate-800 hover:text-white text-slate-400 border-slate-600'
                        ]"
                        @click="switchMode('standard')"
                    >
                        <FileText class="size-3.5 mr-1.5 group-data-[collapsible=icon]:mr-0" />
                        <span class="group-data-[collapsible=icon]:hidden">Standard</span>
                    </Button>
                    <Button
                        :variant="currentEditMode === 'libre' ? 'default' : 'outline'"
                        :class="[
                            'flex-1 h-9 text-sm',
                            currentEditMode === 'libre' 
                                ? 'bg-blue-600 hover:bg-blue-700 text-white border-blue-500' 
                                : 'bg-transparent hover:bg-slate-800 hover:text-white text-slate-400 border-slate-600'
                        ]"
                        @click="switchMode('libre')"
                    >
                        <Layout class="size-3.5 mr-1.5 group-data-[collapsible=icon]:mr-0" />
                        <span class="group-data-[collapsible=icon]:hidden">Libre</span>
                    </Button>
                </div>
            </div>
        </SidebarContent>

        <SidebarFooter class="border-t border-slate-700 px-2 py-4">
            <SidebarMenu class="space-y-2">
                <!-- Informations utilisateur -->
                <SidebarMenuItem class="pb-2">
                    <div class="flex items-center gap-3 px-2 py-2">
                        <Avatar class="size-8 bg-blue-600">
                            <AvatarFallback class="bg-blue-600 text-white">
                                {{ getInitials(user?.name ?? '') }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="flex flex-col group-data-[collapsible=icon]:hidden">
                            <span class="text-sm font-medium text-white">{{
                                user?.email ?? ''
                            }}</span>
                        </div>
                    </div>
                </SidebarMenuItem>

                <!-- Paramètres -->
                <SidebarMenuItem class="py-0.5">
                    <SidebarMenuButton
                        as-child
                        :is-active="urlIsActive(edit.url(), page.url)"
                        class="text-white hover:bg-slate-800 data-[active=true]:text-white data-[active=true]:bg-blue-600 h-10 px-3"
                    >
                    <Link :href="edit.url()" class="flex items-center gap-3 group-data-[collapsible=icon]:justify-center">
                            <Settings class="size-4 text-white" />
                            <span class="text-white group-data-[collapsible=icon]:hidden">Paramètres</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <!-- Déconnexion -->
                <SidebarMenuItem class="py-0.5">
                    <SidebarMenuButton
                        as="button"
                        @click="handleLogout"
                        class="text-white hover:bg-slate-800 w-full h-10 px-3"
                    >
                        <div class="flex items-center gap-3 group-data-[collapsible=icon]:justify-center">
                            <LogOut class="size-4 text-white" />
                            <span class="text-white group-data-[collapsible=icon]:hidden">Déconnexion</span>
                        </div>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <!-- Support -->
                <SidebarMenuItem class="pt-2">
                    <div class="px-3 group-data-[collapsible=icon]:hidden">
                        <p class="text-xs text-slate-400">Support : fitnessclic@gmail.com</p>
                    </div>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
