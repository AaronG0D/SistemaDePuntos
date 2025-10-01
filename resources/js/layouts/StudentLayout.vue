<template>
    <div class="min-h-screen bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <!-- Navigation Header -->
        <nav class="sticky top-0 z-50 border-b border-green-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-12">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo and Title -->
                    <div class="flex items-center space-x-4">
                        <img src="/img/LogoDario.png" alt="Logo Darío Montaño" class="h-10 w-10 rounded-full" />
                        <div class="hidden sm:block">
                            <h1 class="text-lg font-bold text-green-800 dark:text-green-400">Sistema de Puntos</h1>
                            <p class="text-sm text-green-600 dark:text-green-500">Darío Montaño</p>
                        </div>
                    </div>

                    <!-- Navigation Menu -->
                    <div class="hidden items-center space-x-1 md:flex">
                        <Button
                            variant="ghost"
                            size="sm"
                            :class="
                                isCurrentRoute('students.dashboard')
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                    : 'text-gray-600 hover:bg-green-50 hover:text-green-700 dark:text-gray-300 dark:hover:bg-green-900 dark:hover:text-green-400'
                            "
                            @click="router.visit(route('students.dashboard'))"
                        >
                            <Home class="mr-2 h-4 w-4" />
                            Inicio
                        </Button>

                        <Button
                            variant="ghost"
                            size="sm"
                            :class="
                                isCurrentRoute('students.points-history')
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                    : 'text-gray-600 hover:bg-green-50 hover:text-green-700 dark:text-gray-300 dark:hover:bg-green-900 dark:hover:text-green-400'
                            "
                            @click="router.visit(route('students.points-history'))"
                        >
                            <BarChart3 class="mr-2 h-4 w-4" />
                            Mis Puntos
                        </Button>

                        <Button
                            variant="ghost"
                            size="sm"
                            :class="
                                isCurrentRoute('students.profile')
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                    : 'text-gray-600 hover:bg-green-50 hover:text-green-700 dark:text-gray-300 dark:hover:bg-green-900 dark:hover:text-green-400'
                            "
                            @click="router.visit(route('students.profile'))"
                        >
                            <User class="mr-2 h-4 w-4" />
                            Mi Perfil
                        </Button>

                        <Button
                            variant="ghost"
                            size="sm"
                            :class="
                                isCurrentRoute('students.ranking')
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                    : 'text-gray-600 hover:bg-green-50 hover:text-green-700 dark:text-gray-300 dark:hover:bg-green-900 dark:hover:text-green-400'
                            "
                            @click="router.visit(route('students.ranking'))"
                        >
                            <Trophy class="mr-2 h-4 w-4" />
                            Ranking
                        </Button>
                    </div>

                    <!-- Mobile Menu Button -->
                    <div class="md:hidden">
                        <Button variant="ghost" size="sm" @click="mobileMenuOpen = !mobileMenuOpen" class="text-green-700 dark:text-green-400">
                            <Menu class="h-5 w-5" />
                        </Button>
                    </div>

                    <!-- User Info and Menu -->
                    <div class="hidden items-center space-x-4 lg:flex">
                        <!-- Theme Toggle -->
                        <div class="flex items-center space-x-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="toggleTheme"
                                class="text-gray-600 hover:bg-green-50 hover:text-green-700 dark:text-gray-300 dark:hover:bg-green-900 dark:hover:text-green-400"
                            >
                                <Sun v-if="appearance === 'light'" class="h-4 w-4" />
                                <Moon v-else-if="appearance === 'dark'" class="h-4 w-4" />
                                <Monitor v-else class="h-4 w-4" />
                            </Button>
                        </div>

                        <!-- User Dropdown -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" class="flex items-center space-x-3 hover:bg-green-50 dark:hover:bg-green-900">
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ student?.nombres }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ student?.curso?.nombre || 'Sin curso' }} - {{ student?.paralelo?.nombre || 'Sin paralelo' }}
                                        </p>
                                    </div>
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-green-400 to-emerald-600 text-sm font-bold text-white"
                                    >
                                        {{ getInitials(student?.nombres || '', student?.apellidos || '') }}
                                    </div>
                                    <ChevronDown class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <DropdownMenuLabel class="font-normal">
                                    <div class="flex flex-col space-y-1">
                                        <p class="text-sm font-medium leading-none">{{ student?.nombres }} {{ student?.apellidos }}</p>
                                        <p class="text-xs leading-none text-muted-foreground">
                                            {{ student?.codigo_estudiante || 'Sin código' }}
                                        </p>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuGroup>
                                    <DropdownMenuItem @click="router.visit(route('students.profile'))">
                                        <User class="mr-2 h-4 w-4" />
                                        <span>Mi Perfil</span>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem>
                                        <Settings class="mr-2 h-4 w-4" />
                                        <span>Configuración</span>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                                <DropdownMenuSeparator />
                                <DropdownMenuLabel class="font-normal">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm">Tema</span>
                                        <div class="flex items-center space-x-1">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="updateAppearance('light')"
                                                :class="appearance === 'light' ? 'bg-green-100 text-green-800' : ''"
                                            >
                                                <Sun class="h-3 w-3" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="updateAppearance('dark')"
                                                :class="appearance === 'dark' ? 'bg-green-100 text-green-800' : ''"
                                            >
                                                <Moon class="h-3 w-3" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="updateAppearance('system')"
                                                :class="appearance === 'system' ? 'bg-green-100 text-green-800' : ''"
                                            >
                                                <Monitor class="h-3 w-3" />
                                            </Button>
                                        </div>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="handleLogout" class="text-red-600">
                                    <LogOut class="mr-2 h-4 w-4" />
                                    <span>Cerrar Sesión</span>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <!-- Mobile Menu -->
                <div v-if="mobileMenuOpen" class="border-t border-green-200 dark:border-gray-700 py-4 md:hidden">
                    <div class="space-y-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            class="w-full justify-start"
                            :class="isCurrentRoute('students.dashboard') ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'text-gray-600 dark:text-gray-300'"
                            @click="navigateAndCloseMobile('students.dashboard')"
                        >
                            <Home class="mr-2 h-4 w-4" />
                            Inicio
                        </Button>

                        <Button
                            variant="ghost"
                            size="sm"
                            class="w-full justify-start"
                            :class="isCurrentRoute('students.points-history') ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'text-gray-600 dark:text-gray-300'"
                            @click="navigateAndCloseMobile('students.points-history')"
                        >
                            <BarChart3 class="mr-2 h-4 w-4" />
                            Mis Puntos
                        </Button>

                        <Button
                            variant="ghost"
                            size="sm"
                            class="w-full justify-start"
                            :class="isCurrentRoute('students.profile') ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'text-gray-600 dark:text-gray-300'"
                            @click="navigateAndCloseMobile('students.profile')"
                        >
                            <User class="mr-2 h-4 w-4" />
                            Mi Perfil
                        </Button>

                        <Button
                            variant="ghost"
                            size="sm"
                            class="w-full justify-start"
                            :class="isCurrentRoute('students.ranking') ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'text-gray-600 dark:text-gray-300'"
                            @click="navigateAndCloseMobile('students.ranking')"
                        >
                            <Trophy class="mr-2 h-4 w-4" />
                            Ranking
                        </Button>
                        
                        <!-- Separador -->
                        <div class="border-t border-green-200 dark:border-gray-700 my-2"></div>
                        
                        <!-- Theme Toggle Mobile -->
                        <div class="px-3 py-2">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Tema</p>
                            <div class="flex items-center space-x-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="updateAppearance('light')"
                                    :class="appearance === 'light' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'text-gray-600 dark:text-gray-300'"
                                >
                                    <Sun class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="updateAppearance('dark')"
                                    :class="appearance === 'dark' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'text-gray-600 dark:text-gray-300'"
                                >
                                    <Moon class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="updateAppearance('system')"
                                    :class="appearance === 'system' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'text-gray-600 dark:text-gray-300'"
                                >
                                    <Monitor class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                        
                        <!-- Logout Mobile -->
                        <Button
                            variant="ghost"
                            size="sm"
                            class="w-full justify-start text-red-600"
                            @click="handleLogout"
                        >
                            <LogOut class="mr-2 h-4 w-4" />
                            Cerrar Sesión
                        </Button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-16 border-t border-green-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-6 py-8 sm:px-8 lg:px-12">
                <div class="flex flex-col items-center justify-between space-y-4 sm:flex-row sm:space-y-0">
                    <div class="flex items-center space-x-4">
                        <img src="/img/LogoDario.png" alt="Logo Darío Montaño" class="h-8 w-8 rounded-full" />
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Sistema de Puntos Ecológicos</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Colegio Darío Montaño</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
                        <div class="flex items-center space-x-2">
                            <Leaf class="h-4 w-4 text-green-500" />
                            <span>Cuidando el planeta</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Recycle class="h-4 w-4 text-blue-500" />
                            <span>Reciclaje responsable</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Heart class="h-4 w-4 text-red-500" />
                            <span>Con amor por la naturaleza</span>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuGroup, 
    DropdownMenuItem, 
    DropdownMenuLabel, 
    DropdownMenuSeparator, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import { Link, router, usePage } from '@inertiajs/vue3';
import { 
    BarChart3, 
    ChevronDown, 
    Heart, 
    Home, 
    Leaf, 
    LogOut, 
    Menu, 
    Monitor, 
    Moon, 
    Recycle, 
    Settings, 
    Sun, 
    Trophy, 
    User 
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    student?: {
        id?: number;
        nombres: string;
        apellidos: string;
        codigo_estudiante?: string;
        curso?: {
            id?: number;
            nombre: string;
        };
        paralelo?: {
            id?: number;
            nombre: string;
        };
    };
}

defineProps<Props>();

const mobileMenuOpen = ref(false);
const page = usePage();

// Theme functionality
const { appearance, updateAppearance } = useAppearance();

// Toggle between light/dark/system
const toggleTheme = () => {
    if (appearance.value === 'light') {
        updateAppearance('dark');
    } else if (appearance.value === 'dark') {
        updateAppearance('system');
    } else {
        updateAppearance('light');
    }
};

// Logout functionality
const handleLogout = () => {
    router.post(route('logout'), {}, {
        onFinish: () => {
            router.flushAll();
        }
    });
};

const isCurrentRoute = (routeName: string) => {
    // Use Ziggy's current route check for reliability
    return route().current(routeName);
};

const navigateAndCloseMobile = (routeName: string) => {
    mobileMenuOpen.value = false;
    router.visit(route(routeName)); // Usar router en lugar de window.$inertia
};

const getInitials = (nombres: string, apellidos: string) => {
    const firstInitial = nombres.charAt(0).toUpperCase();
    const lastInitial = apellidos.charAt(0).toUpperCase();
    return `${firstInitial}${lastInitial}`;
};
</script>
