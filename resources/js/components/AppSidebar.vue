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
import { type NavGroup, type NavItem, type UserRole } from '@/types';
import type { Page } from '@inertiajs/core';
import { Link, usePage } from '@inertiajs/vue3';
import { Book, BoxIcon, Briefcase, ChevronDown, LayoutGrid, Recycle, Settings, Trash2, User, Users } from 'lucide-vue-next';
import { ref } from 'vue';
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

// Definición de la navegación del sidebar

const navigationGroups: NavGroup[] = [
    {
        title: 'Gestión Académica',
        icon: Settings,
        items: [
            {
                title: 'Estudiantes',
                href: '/admin/estudiantes',
                icon: User,
            },
            {
                title: 'Materias',
                href: '/materias',
                icon: Book,
            },
            {
                title: 'Cursos',
                href: '/cursos',
                icon: Briefcase,
            },
            {
                title: 'Docentes',
                href: '/docentes',
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
                href: '/tipos-basura',
                icon: Recycle,
            },
            {
                title: 'Basureros',
                href: '/basureros',
                icon: Trash2,
            },
            {
                title: 'Depósitos',
                href: '/depositos',
                icon: BoxIcon,
            },
        ],
    },
];

const page = usePage();
const userRole = page.props.auth?.user?.rol as UserRole;
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
                <SidebarGroupLabel class="px-3 py-1 text-xs font-medium text-muted-foreground uppercase">
                    Sistema de Puntos
                </SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <!-- Dashboard -->
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link href="/dashboard" class="flex items-center gap-2 px-2 py-1.5">
                                    <LayoutGrid class="h-4 w-4" />
                                    Dashboard
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>

                        <!-- Menús de administrador -->
                        <template v-if="userRole === 'administrador'">
                            <SidebarMenuItem v-for="group in navigationGroups" :key="group.title">
                                <Collapsible class="w-full">
                                    <CollapsibleTrigger asChild>
                                        <SidebarMenuButton class="flex w-full items-center px-2 py-1.5">
                                            <component :is="group.icon" class="mr-2 h-4 w-4" />
                                            {{ group.title }}
                                            <ChevronDown 
                                                class="ml-auto h-4 w-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" 
                                            />
                                        </SidebarMenuButton>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <SidebarMenuSub>
                                            <SidebarMenuSubItem 
                                                v-for="item in group.items" 
                                                :key="item.href" 
                                                class="pl-4"
                                            >
                                                <Link
                                                    :href="item.href"
                                                    class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-muted"
                                                >
                                                    <component :is="item.icon" class="h-4 w-4" />
                                                    <span>{{ item.title }}</span>
                                                </Link>
                                            </SidebarMenuSubItem>
                                        </SidebarMenuSub>
                                    </CollapsibleContent>
                                </Collapsible>
                            </SidebarMenuItem>
                        </template>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="pt-0">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
