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
import { type NavItem } from '@/types';
import { Link, usePage, router, type InertiaLinkProps } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Plus,
    Dumbbell,
    User,
    Library,
    Tag,
    Star,
    Settings,
    LogOut,
} from 'lucide-vue-next';
import { useInitials } from '@/composables/useInitials';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import { dashboard } from '@/routes';

const page = usePage();
const user = page.props.auth.user;
const { getInitials } = useInitials();

const mainNavItems: NavItem[] = [
    {
        title: 'Mes Séances',
        href: '/client/sessions',
        icon: Dumbbell,
    },
    {
        title: 'Mes Clients',
        href: '/customers',
        icon: User,
    },
    {
        title: 'Bibliothèque',
        href: '/client/library',
        icon: Library,
    },
    {
        title: 'Catégories',
        href: '/client/categories',
        icon: Tag,
    },
    {
        title: 'Abonnement',
        href: '/client/subscription',
        icon: Star,
    },
];

const isCurrentRoute = computed(
    () => (url: NonNullable<InertiaLinkProps['href']>) => urlIsActive(url, page.url),
);

const handleLogout = () => {
    router.post(logout.url());
};
</script>

<template>
    <Sidebar 
        collapsible="icon" 
        variant="inset" 
        class="bg-white dark:bg-slate-900 [&_[data-sidebar=sidebar]]:bg-white [&_[data-sidebar=sidebar]]:dark:bg-slate-900"
    >
        <SidebarHeader class="border-b border-slate-200 dark:border-slate-700">
            <SidebarMenu>
                <SidebarMenuItem>
                    <div class="flex items-center gap-3 px-2 py-4">
                        <!-- Logo FitnessClic avec deux personnages -->
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-blue-500"
                        >
                            <svg
                                width="28"
                                height="28"
                                viewBox="0 0 28 28"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <!-- Personnage masculin (rouge) à gauche -->
                                <circle cx="9" cy="10" r="3.5" fill="#ef4444" />
                                <path
                                    d="M6 16.5C6 14.5 7.5 13 9.5 13C11.5 13 13 14.5 13 16.5V20H6V16.5Z"
                                    fill="#ef4444"
                                />
                                <!-- Bras croisés pour le personnage masculin -->
                                <path
                                    d="M5 12L7 10M11 10L13 12"
                                    stroke="#ef4444"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                                <!-- Personnage féminin (bleu clair) à droite -->
                                <circle cx="19" cy="10" r="3.5" fill="#93c5fd" />
                                <path
                                    d="M16 16.5C16 14.5 17.5 13 19.5 13C21.5 13 23 14.5 23 16.5V20H16V16.5Z"
                                    fill="#93c5fd"
                                />
                                <!-- Bras croisés pour le personnage féminin -->
                                <path
                                    d="M15 12L17 10M21 10L23 12"
                                    stroke="#93c5fd"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-lg font-bold text-slate-900 dark:text-white">FitnessClic</span>
                            <span class="text-xs text-slate-600 dark:text-slate-400"
                                >créateur de séances</span
                            >
                        </div>
                    </div>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="px-2">
            <SidebarGroup>
                <SidebarGroupLabel class="text-xs uppercase text-slate-500 dark:text-slate-400 px-2 py-3 mb-2">
                    NAVIGATION
                </SidebarGroupLabel>
                <SidebarMenu class="space-y-1.5">
                    <!-- Bouton Créer une Séance -->
                    <SidebarMenuItem class="mb-2">
                        <Button
                            as-child
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white justify-start gap-2 h-10"
                        >
                            <Link href="/client/sessions/create">
                                <Plus class="size-4" />
                                <span>Créer une Séance</span>
                            </Link>
                        </Button>
                    </SidebarMenuItem>

                    <!-- Items de navigation -->
                    <SidebarMenuItem
                        v-for="item in mainNavItems"
                        :key="item.title"
                        class="py-0.5"
                    >
                        <SidebarMenuButton
                            as-child
                            :is-active="isCurrentRoute(item.href)"
                            :tooltip="item.title"
                            class="text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-800 data-[active=true]:bg-slate-100 dark:data-[active=true]:bg-slate-800 h-10 px-3"
                        >
                            <Link :href="item.href" class="flex items-center gap-3">
                                <component :is="item.icon" class="size-4 text-slate-700 dark:text-white" />
                                <span class="text-slate-900 dark:text-white">{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="border-t border-slate-200 dark:border-slate-700 px-2 py-4">
            <SidebarMenu class="space-y-2">
                <!-- Informations utilisateur -->
                <SidebarMenuItem class="pb-2">
                    <div class="flex items-center gap-3 px-2 py-2">
                        <Avatar class="size-8 bg-blue-600">
                            <AvatarFallback class="bg-blue-600 text-white">
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">{{
                                user.email
                            }}</span>
                        </div>
                    </div>
                </SidebarMenuItem>

                <!-- Paramètres -->
                <SidebarMenuItem class="py-0.5">
                    <SidebarMenuButton
                        as-child
                        :is-active="urlIsActive(edit(), page.url)"
                        class="text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-800 h-10 px-3"
                    >
                        <Link :href="edit()" class="flex items-center gap-3">
                            <Settings class="size-4 text-slate-700 dark:text-white" />
                            <span class="text-slate-900 dark:text-white">Paramètres</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <!-- Déconnexion -->
                <SidebarMenuItem class="py-0.5">
                    <SidebarMenuButton
                        as="button"
                        @click="handleLogout"
                        class="text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-800 w-full h-10 px-3"
                    >
                        <div class="flex items-center gap-3">
                            <LogOut class="size-4 text-slate-700 dark:text-white" />
                            <span class="text-slate-900 dark:text-white">Déconnexion</span>
                        </div>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
