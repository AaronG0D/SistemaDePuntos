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
import { type NavGroup, type UserRole } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Activity, Book, BoxIcon, Calendar, ChevronDown, LayoutGrid, QrCode, Recycle, Settings, Trash2, User, Users } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';

// Estado para los menús colapsables
const openAcademico = ref(false);
const openResiduos = ref(false);
const openGroups = ref<{ [key: string]: boolean }>({});

// Claves de storage para persistencia
const STORAGE_KEY_GROUPS = 'sidebar_open_groups_v1';
const STORAGE_KEY_RESIDUOS = 'sidebar_open_reportes_v1';

// Estado para el sidebar colapsado
const collapsible = computed(() => 'collapsed'); // Ajusta el valor según tu lógica

// Definición de la navegación del sidebar

import { docenteNavigation } from '@/config/navigation';
import AppLogo from './AppLogo.vue';

const navigationGroups: NavGroup[] = [
    {
        title: 'Gestión Académica',
        icon: Settings,
        items: [
            {
                title: 'Períodos Académicos',
                href: route('admin.periodos.index'),
                icon: Calendar, // Asegúrate de importar Calendar de lucide-vue-next
            },
            {
                title: 'Estudiantes',
                href: route('admin.estudiantes'),
                icon: User,
            },
            {
                title: 'Gestión QR',
                href: route('admin.qr-management'),
                icon: QrCode,
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
                title: 'Eventos Raspberry Pi',
                href: route('admin.raspberry.eventos'),
                icon: Activity,
            },
        ],
    },
    {
        title: 'Gestión de Usuarios',
        icon: Users,
        items: [
            {
                title: 'Usuarios',
                href: route('users.index'),
                icon: Users,
            },
            {
                title: 'Crear Usuario',
                href: route('users.create'),
                icon: User,
            },
        ],
    },
];

const page = usePage();
const userRole = page.props.auth?.user?.rol as UserRole;

// Función para verificar si una ruta está activa
function isRouteActive(href: string): boolean {
    const currentPath = page.url || window.location.pathname;
    // Normalizar las rutas removiendo barras finales
    const normalizedCurrent = currentPath.replace(/\/$/, '');
    const normalizedHref = href.replace(/\/$/, '');

    return normalizedCurrent === normalizedHref || normalizedCurrent.startsWith(normalizedHref + '/');
}

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

// Restaurar estado desde localStorage al montar
onMounted(() => {
    try {
        const savedGroups = localStorage.getItem(STORAGE_KEY_GROUPS);
        if (savedGroups) {
            const parsed = JSON.parse(savedGroups);
            if (parsed && typeof parsed === 'object') {
                openGroups.value = parsed;
            }
        }
    } catch (e) {
        // noop
    }

    try {
        const savedResiduos = localStorage.getItem(STORAGE_KEY_RESIDUOS);
        if (savedResiduos !== null) {
            openResiduos.value = JSON.parse(savedResiduos) === true;
        }
    } catch (e) {
        // noop
    }
});

// Persistir cambios de los grupos
watch(
    openGroups,
    (val) => {
        try {
            localStorage.setItem(STORAGE_KEY_GROUPS, JSON.stringify(val || {}));
        } catch (e) {
            // noop
        }
    },
    { deep: true },
);

// Persistir cambio de Reportes
watch(openResiduos, (val) => {
    try {
        localStorage.setItem(STORAGE_KEY_RESIDUOS, JSON.stringify(!!val));
    } catch (e) {
        // noop
    }
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="min-h-screen">
        <SidebarHeader class="pb-0">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('admin.dashboard')" class="flex items-center justify-center">
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
                        <!-- Dashboard (solo admin) -->
                        <SidebarMenuItem v-if="userRole === 'administrador'">
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <SidebarMenuButton as-child>
                                            <Link
                                                :href="route('admin.dashboard')"
                                                :class="[
                                                    'flex items-center gap-2 rounded-md px-2 py-1.5 transition-colors',
                                                    isRouteActive(route('admin.dashboard'))
                                                        ? 'border-l-4 border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300'
                                                        : 'hover:bg-slate-100 dark:hover:bg-slate-800',
                                                ]"
                                            >
                                                <LayoutGrid class="h-4 w-4" />
                                                <span class="sidebar-label">Dashboard</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </TooltipTrigger>
                                    <TooltipContent v-if="collapsible === 'collapsed'" side="right"> Dashboard </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </SidebarMenuItem>

                        <!-- Menús según rol -->
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
                                                <!-- 🔥 FIX: Grupo resaltado si alguna subruta está activa -->
                                                <CollapsibleTrigger asChild>
                                                    <SidebarMenuButton
                                                        class="flex w-full items-center px-2 py-1.5"
                                                        :class="[
                                                            isGroupActive(group)
                                                                ? 'border-l-4 border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300'
                                                                : 'hover:bg-slate-100 dark:hover:bg-slate-800',
                                                        ]"
                                                    >
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
                                                                :class="[
                                                                    'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors',
                                                                    isRouteActive(item.href)
                                                                        ? 'border-l-4 border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300'
                                                                        : 'hover:bg-slate-100 dark:hover:bg-slate-800',
                                                                ]"
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

                        <!-- Menús de docente -->
                        <template v-if="userRole === 'docente'">
                            <SidebarMenuItem v-for="group in docenteNavigation" :key="group.title">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Collapsible
                                                class="w-full"
                                                :open="isGroupOpen(group)"
                                                @update:open="(val) => (openGroups[group.title] = val)"
                                            >
                                                <!-- 🔥 FIX también para docentes -->
                                                <CollapsibleTrigger asChild>
                                                    <SidebarMenuButton
                                                        class="flex w-full items-center px-2 py-1.5"
                                                        :class="[
                                                            isGroupActive(group)
                                                                ? 'active-sidebar-item'
                                                                : 'hover:bg-accent hover:text-accent-foreground',
                                                        ]"
                                                    >
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
                                                                :class="[
                                                                    'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors',
                                                                    isRouteActive(item.href)
                                                                        ? 'border-l-4 border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300'
                                                                        : 'hover:bg-slate-100 dark:hover:bg-slate-800',
                                                                ]"
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

                        <!-- Sección de Reportes (solo admin) -->
                        <SidebarMenuItem v-if="userRole === 'administrador'">
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Collapsible class="w-full" :open="openResiduos" @update:open="(val) => (openResiduos = val)">
                                            <!-- 🔥 FIX: Resaltar reportes activos -->
                                            <CollapsibleTrigger asChild>
                                                <SidebarMenuButton
                                                    class="flex w-full items-center px-2 py-1.5"
                                                    :class="[
                                                        isRouteActive(route('admin.reportes.index'))
                                                            ? 'active-sidebar-item'
                                                            : 'hover:bg-accent hover:text-accent-foreground',
                                                    ]"
                                                >
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
                                                            :class="[
                                                                'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors',
                                                                isRouteActive(route('admin.reportes.index'))
                                                                    ? 'active-sidebar-item'
                                                                    : 'hover:bg-muted hover:text-foreground',
                                                            ]"
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

/* Estilos para elementos activos del sidebar */
.active-sidebar-item {
    background-color: #64748b !important;
    color: white !important;
    font-weight: 600 !important;
    opacity: 1 !important;
    position: relative;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
}

/* Barra lateral izquierda para elementos activos */
.active-sidebar-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background-color: #475569;
    border-radius: 0 2px 2px 0;
}

/* Items inactivos */
.inactive-sidebar-item {
    opacity: 0.7;
    transition: all 0.2s ease;
}

.inactive-sidebar-item:hover {
    background-color: rgba(255, 255, 255, 0.08) !important;
    opacity: 1;
}

/* Grupos activos */
.active-sidebar-group {
    background-color: #64748b !important;
    color: white !important;
    font-weight: 600 !important;
    position: relative;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
}

.active-sidebar-group::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background-color: #475569;
    border-radius: 0 2px 2px 0;
}
</style>
