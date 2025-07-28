<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { type NavGroup, type NavItem, type UserRole } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Book, BoxIcon, ChevronDown, LayoutGrid, Recycle, Settings, Trash2, User, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLogo from './AppLogo.vue';

const commonNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

// Estado para los menús colapsables
const openAcademico = ref(false);
const openResiduos = ref(false);
const openGroups = ref<{ [key: string]: boolean }>({});

// Estado para el sidebar colapsado
const collapsible = computed(() => 'collapsed'); // Ajusta el valor según tu lógica

// Definición de la navegación del sidebar

const navigationGroups: NavGroup[] = [
    {
        title: 'Gestión Académica',
        icon: Settings,
        items: [
            {
                title: 'Estudiantes',
                href: route('admin.estudiantes'),
                icon: User,
            },
            {
                title: 'Cursos y Materias',
                href: route('admin.cursos.materias'),
                icon: Book,
            },
            {
                title: 'Docentes',
                href: route('admin.docentes'),
                icon: Users,
            },
        ],
    },
    {
        title: 'Gestión de Residuos',
        icon: Trash2,
        items: [
            {
                title: 'Tipos de Basura',
                href: route('admin.tipos-basura.index'),
                icon: Recycle,
            },
            {
                title: 'Basureros',
                href: route('admin.basureros.index'),
                icon: Trash2,
            },
            {
                title: 'Depósitos',
                href: route('admin.depositos.index'),
                icon: BoxIcon,
            },
            {
                title: 'Estadísticas',
                href: route('admin.depositos.estadisticas'),
                icon: BoxIcon,
            },
        ],
    },
];

const page = usePage();
const userRole = page.props.auth?.user?.rol as UserRole;

function setGroupOpen(group: { title: string | number }) {
    openGroups.value[group.title] = true;
}
function isGroupActive(group: { items: Array<{ href: string }> }) {
    // Usar la ruta actual de Inertia para detectar si alguna subruta está activa
    const currentPath = page.url || window.location.pathname;
    return group.items.some((item) => currentPath.startsWith(item.href));
}

// Mantener desplegado el grupo si alguna subruta está activa o el usuario lo abrió manualmente
function isGroupOpen(group: { title: string | number; items: Array<{ href: string }> }) {
    return openGroups.value[group.title] !== undefined ? openGroups.value[group.title] : isGroupActive(group);
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="min-h-screen">
        <SidebarHeader class="pb-0">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')" class="flex items-center justify-center">
                            <AppLogo class="h-6 w-auto" />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="space-y-1">
            <SidebarGroup class="py-1">
                <SidebarGroupLabel class="text-muted-foreground px-3 py-1 text-xs font-medium uppercase"> Sistema de Puntos </SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <!-- Dashboard -->
                        <SidebarMenuItem>
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <SidebarMenuButton as-child>
                                            <Link href="/dashboard" class="flex items-center gap-2 px-2 py-1.5">
                                                <LayoutGrid class="h-4 w-4" />
                                                <span class="sidebar-label">Dashboard</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </TooltipTrigger>
                                    <TooltipContent v-if="collapsible === 'collapsed'" side="right"> Dashboard </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </SidebarMenuItem>

                        <!-- Menús de administrador -->
                        <template v-if="userRole === 'administrador'">
                            <SidebarMenuItem v-for="group in navigationGroups" :key="group.title">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Collapsible
                                                class="w-full"
                                                :open="isGroupOpen(group)"
                                                @update:open="(val) => (openGroups[group.title] = val)"
                                            >
                                                <CollapsibleTrigger asChild>
                                                    <SidebarMenuButton class="flex w-full items-center px-2 py-1.5">
                                                        <component v-if="group.icon" :is="group.icon" class="mr-2 h-4 w-4" />
                                                        <span class="sidebar-label">{{ group.title }}</span>
                                                        <ChevronDown
                                                            class="ml-auto h-4 w-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                                        />
                                                    </SidebarMenuButton>
                                                </CollapsibleTrigger>
                                                <CollapsibleContent>
                                                    <SidebarMenuSub>
                                                        <SidebarMenuSubItem v-for="item in group.items" :key="item.href" class="pl-4">
                                                            <Link
                                                                :href="item.href"
                                                                class="hover:bg-muted flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors"
                                                                @click="setGroupOpen(group)"
                                                            >
                                                                <component v-if="item.icon" :is="item.icon" class="h-4 w-4" />
                                                                <span>{{ item.title }}</span>
                                                            </Link>
                                                        </SidebarMenuSubItem>
                                                    </SidebarMenuSub>
                                                </CollapsibleContent>
                                            </Collapsible>
                                        </TooltipTrigger>
                                        <TooltipContent side="right">
                                            {{ group.title }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </SidebarMenuItem>
                        </template>

                        <!-- Sección de Reportes -->
                        <SidebarMenuItem>
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Collapsible class="w-full" :open="openResiduos" @update:open="(val) => (openResiduos = val)">
                                            <CollapsibleTrigger asChild>
                                                <SidebarMenuButton class="flex w-full items-center px-2 py-1.5">
                                                    <BoxIcon class="mr-2 h-4 w-4" />
                                                    <span class="sidebar-label">Reportes</span>
                                                    <ChevronDown
                                                        class="ml-auto h-4 w-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                                    />
                                                </SidebarMenuButton>
                                            </CollapsibleTrigger>
                                            <CollapsibleContent>
                                                <SidebarMenuSub>
                                                    <SidebarMenuSubItem>
                                                        <Link
                                                            :href="route('admin.reportes.index')"
                                                            class="hover:bg-muted flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors"
                                                        >
                                                            <Icon name="file-text" class="h-4 w-4" />
                                                            <span>Reporte de Depósitos</span>
                                                        </Link>
                                                    </SidebarMenuSubItem>
                                                </SidebarMenuSub>
                                            </CollapsibleContent>
                                        </Collapsible>
                                    </TooltipTrigger>
                                    <TooltipContent side="right"> Reportes </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="pt-0">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>

<style scoped>
/* Oculta el texto cuando el sidebar está colapsado (solo íconos) */
:deep(.sidebar-label) {
    transition: opacity 0.2s;
}
:deep([data-collapsible='icon'] .sidebar-label) {
    opacity: 0;
    pointer-events: none;
    width: 0;
    display: inline-block;
}
</style>
