<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ArrowRight, ChartBar, GraduationCap, Medal, Recycle, Star, Trash2, Trophy, 
    Users, BarChart3, Target, Calendar, Phone, Mail, MapPin, Award,
    TrendingUp, Activity, Zap, Globe, Heart, Shield
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3'

const page = usePage()

// Propiedades para recibir el ranking y los filtros
const props = defineProps<{
    topEstudiantes?: {
        user: {
            puntaje: any;
            nombres: string;
            primerApellido: string;
        };
        curso_paralelo: {
            curso: { nombre: string };
            paralelo: { nombre: string };
        };
        puntaje: {
            puntajeTotal: number;
        };
    }[];
    cursos: {
        idCurso: number;
        nombre: string;
    }[];
    paralelos: {
        idParalelo: number;
        nombre: string;
    }[];
    estadisticas?: {
        totalEstudiantes: number;
        totalDepositos: number;
        totalPuntos: number;
        totalBasureros: number;
        tiposBasura: number;
        depositosHoy: number;
        puntosHoy: number;
        cursoMasActivo?: {
            curso_nombre: string;
            paralelo_nombre: string;
            total_depositos: number;
            total_puntos: number;
        };
        tipoBasuraMasComun?: {
            nombre: string;
            descripcion: string;
            total_depositos: number;
            total_puntos: number;
        };
    };
}>();

// Estado para los filtros
const cursoSeleccionado = ref('all');
const paraleloSeleccionado = ref('all');

// Estado para navegación
const activeSection = ref('inicio');

// Función para aplicar filtros
function aplicarFiltros() {
    router.get(
        '/',
        {
            curso: cursoSeleccionado.value,
            paralelo: paraleloSeleccionado.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

// Función para scroll suave a secciones
function scrollToSection(sectionId: string) {
    activeSection.value = sectionId;
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

// Función para obtener la ruta del dashboard según el rol
function getDashboardRoute() {
    const user = page.props.auth.user as any;
    if (!user) return route('login');
    
    // Verificar el rol del usuario
    const roles = user.rol || [];
    
    if (roles === 'administrador') {
        return route('admin.dashboard');
    } else if (roles === 'docente') {
        return route('docente.dashboard');
    } else if (roles === 'estudiante') {
        return route('students.dashboard');
    }
    
    // Por defecto, redirigir al dashboard de admin
    return route('admin.dashboard');
}

// Computed para formatear números
const formatNumber = (num: number) => {
    return new Intl.NumberFormat('es-ES').format(num);
};
</script>

<template>
    <Head title="EcoPoints - Sistema de Gestión de Residuos">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="min-h-screen bg-gradient-to-b from-green-50 to-white dark:from-green-950 dark:to-gray-900">
        <!-- Navegación -->
        <header class="fixed top-0 z-50 w-full border-b border-green-100 bg-white/95 backdrop-blur-sm dark:border-green-900 dark:bg-gray-900/95">
            <nav class="container mx-auto flex items-center justify-between p-4">
                <div class="flex items-center gap-2">
                    <Recycle class="h-8 w-8 text-green-600 dark:text-green-400" />
                    <span class="text-xl font-bold text-green-800 dark:text-green-200">EcoPoints</span>
                </div>
                
                <!-- Navegación central -->
                <div class="hidden md:flex items-center gap-6">
                    <button
                        @click="scrollToSection('inicio')"
                        :class="['px-3 py-2 rounded-lg font-medium transition', activeSection === 'inicio' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'text-gray-600 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400']"
                    >
                        Inicio
                    </button>
                    <button
                        @click="scrollToSection('nosotros')"
                        :class="['px-3 py-2 rounded-lg font-medium transition', activeSection === 'nosotros' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'text-gray-600 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400']"
                    >
                        Nosotros
                    </button>
                    <button
                        @click="scrollToSection('ranking')"
                        :class="['px-3 py-2 rounded-lg font-medium transition', activeSection === 'ranking' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'text-gray-600 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400']"
                    >
                        Ranking
                    </button>
                    <button
                        @click="scrollToSection('contacto')"
                        :class="['px-3 py-2 rounded-lg font-medium transition', activeSection === 'contacto' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'text-gray-600 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400']"
                    >
                        Contacto
                    </button>
                </div>

                <div class="flex items-center gap-4">
                    <Link
                        v-if="page.props.auth.user"
                        :href="getDashboardRoute()"
                        class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 font-medium text-white transition hover:bg-green-700"
                    >
                        Dashboard
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-lg px-4 py-2 font-medium text-green-700 transition hover:bg-green-100 dark:text-green-300 dark:hover:bg-green-900"
                        >
                            Iniciar Sesión
                        </Link>
                        <Link
                            :href="route('register')"
                            class="rounded-lg bg-green-600 px-4 py-2 font-medium text-white transition hover:bg-green-700"
                        >
                            Registrarse
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section id="inicio" class="container mx-auto px-4 pt-24 pb-16 lg:py-32">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div class="space-y-6">
                    <h1 class="text-4xl font-bold text-gray-900 lg:text-5xl dark:text-white">Gestiona tus Residuos de Manera Inteligente</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-300">
                       Registra y monitorea tus depósitos de residuos de manera eficiente y organizada.
Tu basurero inteligente detecta el tipo de basura, asigna puntos ecológicos por cada depósito y envía la información al sistema en tiempo real para fomentar el reciclaje responsable.
                    </p>
                    <div class="flex gap-4">
                        <Link
                            :href="route('register')"
                            class="flex items-center gap-2 rounded-lg bg-green-600 px-6 py-3 font-medium text-white transition hover:bg-green-700"
                        >
                            Comenzar Ahora
                            <ArrowRight class="h-5 w-5" />
                        </Link>
                        <button
                            @click="scrollToSection('nosotros')"
                            class="flex items-center gap-2 rounded-lg border border-green-200 px-6 py-3 font-medium text-green-700 transition hover:bg-green-50 dark:border-green-800 dark:text-green-300 dark:hover:bg-green-900"
                        >
                            Saber Más
                        </button>
                    </div>
                </div>
                <div class="relative">
                    <div class="aspect-square rounded-3xl bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900 dark:to-green-800">
                        <Recycle class="absolute inset-0 m-auto h-32 w-32 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Estadísticas Generales -->
        <section v-if="estadisticas" class="border-t border-green-100 bg-white dark:border-green-900 dark:bg-gray-900">
            <div class="container mx-auto px-4 py-16">
                <h2 class="mb-12 text-center text-3xl font-bold text-gray-900 dark:text-white">Impacto en Tiempo Real</h2>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Estudiantes -->
                    <div class="rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 p-6 dark:from-blue-900/20 dark:to-blue-800/20">
                        <div class="flex items-center gap-4">
                            <div class="rounded-lg bg-blue-500 p-3">
                                <Users class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(estadisticas.totalEstudiantes) }}</p>
                                <p class="text-sm text-blue-600/70 dark:text-blue-400/70">Estudiantes Activos</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Depósitos -->
                    <div class="rounded-xl bg-gradient-to-br from-green-50 to-green-100 p-6 dark:from-green-900/20 dark:to-green-800/20">
                        <div class="flex items-center gap-4">
                            <div class="rounded-lg bg-green-500 p-3">
                                <Trash2 class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatNumber(estadisticas.totalDepositos) }}</p>
                                <p class="text-sm text-green-600/70 dark:text-green-400/70">Depósitos Totales</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Puntos -->
                    <div class="rounded-xl bg-gradient-to-br from-yellow-50 to-yellow-100 p-6 dark:from-yellow-900/20 dark:to-yellow-800/20">
                        <div class="flex items-center gap-4">
                            <div class="rounded-lg bg-yellow-500 p-3">
                                <Trophy class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ formatNumber(estadisticas.totalPuntos) }}</p>
                                <p class="text-sm text-yellow-600/70 dark:text-yellow-400/70">Puntos Generados</p>
                            </div>
                        </div>
                    </div>

                    <!-- Depósitos Hoy -->
                    <div class="rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 p-6 dark:from-purple-900/20 dark:to-purple-800/20">
                        <div class="flex items-center gap-4">
                            <div class="rounded-lg bg-purple-500 p-3">
                                <TrendingUp class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ formatNumber(estadisticas.depositosHoy) }}</p>
                                <p class="text-sm text-purple-600/70 dark:text-purple-400/70">Depósitos Hoy</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Destacadas -->
                <div v-if="estadisticas.cursoMasActivo || estadisticas.tipoBasuraMasComun" class="mt-12 grid gap-6 lg:grid-cols-2">
                    <!-- Curso Más Activo -->
                    <div v-if="estadisticas.cursoMasActivo" class="rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100 p-6 dark:from-indigo-900/20 dark:to-indigo-800/20">
                        <div class="flex items-start gap-4">
                            <div class="rounded-lg bg-indigo-500 p-3">
                                <Award class="h-6 w-6 text-white" />
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-100">Curso Más Activo</h3>
                                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ estadisticas.cursoMasActivo.curso_nombre }} {{ estadisticas.cursoMasActivo.paralelo_nombre }}
                                </p>
                                <div class="mt-2 flex gap-4 text-sm text-indigo-600/70 dark:text-indigo-400/70">
                                    <span>{{ formatNumber(estadisticas.cursoMasActivo.total_depositos) }} depósitos</span>
                                    <span>{{ formatNumber(estadisticas.cursoMasActivo.total_puntos) }} puntos</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tipo de Basura Más Común -->
                    <div v-if="estadisticas.tipoBasuraMasComun" class="rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 p-6 dark:from-emerald-900/20 dark:to-emerald-800/20">
                        <div class="flex items-start gap-4">
                            <div class="rounded-lg bg-emerald-500 p-3">
                                <Target class="h-6 w-6 text-white" />
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">Residuo Más Reciclado</h3>
                                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ estadisticas.tipoBasuraMasComun.nombre }}
                                </p>
                                <div class="mt-2 flex gap-4 text-sm text-emerald-600/70 dark:text-emerald-400/70">
                                    <span>{{ formatNumber(estadisticas.tipoBasuraMasComun.total_depositos) }} depósitos</span>
                                    <span>{{ formatNumber(estadisticas.tipoBasuraMasComun.total_puntos) }} puntos</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Características -->
        <section class="border-t border-green-100 bg-green-50 dark:border-green-900 dark:bg-green-950">
            <div class="container mx-auto px-4 py-16">
                <h2 class="mb-12 text-center text-3xl font-bold text-gray-900 dark:text-white">Características Principales</h2>
                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Característica 1 -->
                    <div class="rounded-xl bg-white p-6 shadow-lg dark:bg-gray-800">
                        <div class="mb-4 inline-block rounded-lg bg-green-100 p-3 dark:bg-green-900">
                            <Trash2 class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                        <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">Gestión de Residuos</h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Registra y monitorea tus depósitos de residuos de manera eficiente y organizada.
                        </p>
                    </div>

                    <!-- Característica 2 -->
                    <div class="rounded-xl bg-white p-6 shadow-lg dark:bg-gray-800">
                        <div class="mb-4 inline-block rounded-lg bg-green-100 p-3 dark:bg-green-900">
                            <Trophy class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                        <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">Sistema de Puntos</h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Gana puntos por tus acciones ecológicas y contribuye al cuidado del medio ambiente.
                        </p>
                    </div>

                    <!-- Característica 3 -->
                    <div class="rounded-xl bg-white p-6 shadow-lg dark:bg-gray-800">
                        <div class="mb-4 inline-block rounded-lg bg-green-100 p-3 dark:bg-green-900">
                            <ChartBar class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                        <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">Estadísticas Detalladas</h3>
                        <p class="text-gray-600 dark:text-gray-300">Visualiza tu impacto ambiental con estadísticas y reportes detallados.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección Nosotros -->
        <section id="nosotros" class="border-t border-green-100 bg-white dark:border-green-900 dark:bg-gray-900">
            <div class="container mx-auto px-4 py-16">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Sobre EcoPoints</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                        Somos una plataforma innovadora que combina tecnología y conciencia ambiental para crear un futuro más sostenible.
                    </p>
                </div>

                <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                    <div class="space-y-6">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Nuestra Misión</h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Transformar la gestión de residuos en las instituciones educativas mediante un sistema inteligente 
                            que motiva a los estudiantes a adoptar prácticas sostenibles a través de gamificación y reconocimiento.
                        </p>
                        
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="flex items-start gap-3">
                                <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900">
                                    <Shield class="h-5 w-5 text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Confiable</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Sistema seguro y preciso</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3">
                                <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900">
                                    <Zap class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Innovador</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Tecnología de vanguardia</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3">
                                <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900">
                                    <Heart class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Sostenible</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Compromiso ambiental</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3">
                                <div class="rounded-lg bg-orange-100 p-2 dark:bg-orange-900">
                                    <Globe class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Global</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Impacto mundial</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="relative">
                        <div class="aspect-square rounded-3xl bg-gradient-to-br from-blue-100 to-purple-200 dark:from-blue-900 dark:to-purple-800 p-8">
                            <div class="grid grid-cols-2 gap-4 h-full">
                                <div class="flex items-center justify-center rounded-2xl bg-white/50 dark:bg-gray-800/50">
                                    <Activity class="h-12 w-12 text-green-600 dark:text-green-400" />
                                </div>
                                <div class="flex items-center justify-center rounded-2xl bg-white/50 dark:bg-gray-800/50">
                                    <BarChart3 class="h-12 w-12 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="flex items-center justify-center rounded-2xl bg-white/50 dark:bg-gray-800/50">
                                    <Trophy class="h-12 w-12 text-yellow-600 dark:text-yellow-400" />
                                </div>
                                <div class="flex items-center justify-center rounded-2xl bg-white/50 dark:bg-gray-800/50">
                                    <Target class="h-12 w-12 text-purple-600 dark:text-purple-400" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ranking de Estudiantes -->
        <section id="ranking" class="border-t border-green-100 bg-green-50 dark:border-green-900 dark:bg-green-950">
            <div class="container mx-auto px-4 py-16">
                <div class="text-center">
                    <h2 class="mb-4 text-3xl font-bold text-gray-900 dark:text-white">Top Estudiantes EcoPoints</h2>
                    <p class="mb-6 text-gray-600 dark:text-gray-300">Conoce a nuestros estudiantes más comprometidos con el medio ambiente</p>

                    <!-- Debug info -->
                    <div class="mb-4 text-sm">
                        <p>Cursos disponibles: {{ cursos?.length || 0 }}</p>
                        <p>Paralelos disponibles: {{ paralelos?.length || 0 }}</p>
                        <p>Estudiantes mostrados: {{ topEstudiantes?.length || 0 }}</p>
                    </div>

                    <!-- Filtros -->
                    <div class="mb-8 flex flex-wrap justify-center gap-4">
                        <select
                            v-model="cursoSeleccionado"
                            @change="aplicarFiltros"
                            class="rounded-lg border border-green-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-green-50 dark:border-green-800 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-green-900"
                        >
                            <option value="all">Todos los cursos</option>
                            <option v-for="curso in cursos" :key="curso.idCurso" :value="curso.idCurso">
                                {{ curso.nombre }}
                            </option>
                        </select>

                        <select
                            v-model="paraleloSeleccionado"
                            @change="aplicarFiltros"
                            class="rounded-lg border border-green-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-green-50 dark:border-green-800 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-green-900"
                        >
                            <option value="all">Todos los paralelos</option>
                            <option v-for="paralelo in paralelos" :key="paralelo.idParalelo" :value="paralelo.idParalelo">
                                {{ paralelo.nombre }}
                            </option>
                        </select>
                    </div>
                </div>

                <div v-if="topEstudiantes && topEstudiantes.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(estudiante, index) in topEstudiantes"
                        :key="index"
                        class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-2xl dark:bg-gray-800"
                        :class="{
                            'ring-4 ring-yellow-400/50 bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20': index === 0,
                            'ring-4 ring-gray-400/50 bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900/20 dark:to-slate-900/20': index === 1,
                            'ring-4 ring-orange-400/50 bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20': index === 2,
                        }"
                    >
                        <!-- Efecto de brillo para los primeros 3 -->
                        <div v-if="index < 3" class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -skew-x-12 transform translate-x-full group-hover:translate-x-[-200%] transition-transform duration-1000"></div>
                        
                        <!-- Posición destacada -->
                        <div class="absolute -top-2 -right-2">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full text-lg font-bold text-white shadow-lg"
                                :class="{
                                    'bg-gradient-to-br from-yellow-400 to-yellow-600': index === 0,
                                    'bg-gradient-to-br from-gray-400 to-gray-600': index === 1,
                                    'bg-gradient-to-br from-orange-400 to-orange-600': index === 2,
                                    'bg-gradient-to-br from-green-400 to-green-600': index >= 3,
                                }"
                            >
                                {{ index + 1 }}
                            </div>
                        </div>

                        <!-- Medalla para los tres primeros lugares -->
                        <div v-if="index < 3" class="absolute -top-1 -left-1">
                            <div
                                class="rounded-full p-2 shadow-lg"
                                :class="{
                                    'bg-gradient-to-br from-yellow-400 to-yellow-600': index === 0,
                                    'bg-gradient-to-br from-gray-400 to-gray-600': index === 1,
                                    'bg-gradient-to-br from-orange-400 to-orange-600': index === 2,
                                }"
                            >
                                <Medal class="h-5 w-5 text-white" />
                            </div>
                        </div>

                        <div class="relative z-10">
                            <div class="mb-4 flex items-center gap-4">
                                <div 
                                    class="flex h-14 w-14 items-center justify-center rounded-full shadow-lg"
                                    :class="{
                                        'bg-gradient-to-br from-yellow-100 to-yellow-200 dark:from-yellow-900 dark:to-yellow-800': index === 0,
                                        'bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-900 dark:to-gray-800': index === 1,
                                        'bg-gradient-to-br from-orange-100 to-orange-200 dark:from-orange-900 dark:to-orange-800': index === 2,
                                        'bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900 dark:to-green-800': index >= 3,
                                    }"
                                >
                                    <Trophy 
                                        class="h-7 w-7"
                                        :class="{
                                            'text-yellow-600 dark:text-yellow-400': index === 0,
                                            'text-gray-600 dark:text-gray-400': index === 1,
                                            'text-orange-600 dark:text-orange-400': index === 2,
                                            'text-green-600 dark:text-green-400': index >= 3,
                                        }"
                                    />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ estudiante?.user?.nombres || 'Sin nombre' }} {{ estudiante?.user?.primerApellido || '' }}
                                    </h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-100"
                                        >
                                            <GraduationCap class="h-3 w-3" />
                                            {{ estudiante?.curso_paralelo?.curso?.nombre || 'Sin curso' }}
                                        </span>
                                        <span
                                            v-if="estudiante?.curso_paralelo?.paralelo?.nombre"
                                            class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-100"
                                        >
                                            {{ estudiante?.curso_paralelo?.paralelo?.nombre }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-700/50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <Star 
                                            class="h-5 w-5"
                                            :class="{
                                                'text-yellow-500': index === 0,
                                                'text-gray-500': index === 1,
                                                'text-orange-500': index === 2,
                                                'text-green-500': index >= 3,
                                            }"
                                        />
                                        <span class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ formatNumber(estudiante?.user?.puntaje?.puntajeTotal || 0) }} puntos
                                        </span>
                                    </div>
                                    <div 
                                        class="rounded-full px-3 py-1 text-xs font-medium"
                                        :class="{
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100': index === 0,
                                            'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100': index === 1,
                                            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100': index === 2,
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100': index >= 3,
                                        }"
                                    >
                                        Top {{ index + 1 }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center text-gray-600 dark:text-gray-400">¡Sé el primero en unirte y aparecer en nuestro ranking!</div>
            </div>
        </section>

        <!-- Sección Contacto -->
        <section id="contacto" class="border-t border-green-100 bg-white dark:border-green-900 dark:bg-gray-900">
            <div class="container mx-auto px-4 py-16">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Contáctanos</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                        ¿Tienes preguntas sobre EcoPoints? Estamos aquí para ayudarte a crear un futuro más sostenible.
                    </p>
                </div>

                <div class="grid gap-8 lg:grid-cols-2">
                    <!-- Información de Contacto -->
                    <div class="space-y-8">
                        <div class="rounded-2xl bg-gradient-to-br from-green-50 to-green-100 p-8 dark:from-green-900/20 dark:to-green-800/20">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Información de Contacto</h3>
                            
                            <div class="space-y-6">
                                <div class="flex items-start gap-4">
                                    <div class="rounded-lg bg-green-500 p-3">
                                        <Mail class="h-6 w-6 text-white" />
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">Email</h4>
                                        <p class="text-gray-600 dark:text-gray-300">contacto@ecopoints.edu</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Respuesta en 24 horas</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4">
                                    <div class="rounded-lg bg-blue-500 p-3">
                                        <Phone class="h-6 w-6 text-white" />
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">Teléfono</h4>
                                        <p class="text-gray-600 dark:text-gray-300">+593 2 234-5678</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Lun - Vie: 8:00 AM - 6:00 PM</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4">
                                    <div class="rounded-lg bg-purple-500 p-3">
                                        <MapPin class="h-6 w-6 text-white" />
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">Ubicación</h4>
                                        <p class="text-gray-600 dark:text-gray-300">Quito, Ecuador</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Oficinas principales</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estadísticas de Soporte -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 p-6 text-center dark:from-blue-900/20 dark:to-blue-800/20">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-500">
                                    <Calendar class="h-6 w-6 text-white" />
                                </div>
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">24/7</p>
                                <p class="text-sm text-blue-600/70 dark:text-blue-400/70">Soporte Disponible</p>
                            </div>
                            
                            <div class="rounded-xl bg-gradient-to-br from-green-50 to-green-100 p-6 text-center dark:from-green-900/20 dark:to-green-800/20">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-500">
                                    <Activity class="h-6 w-6 text-white" />
                                </div>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">99%</p>
                                <p class="text-sm text-green-600/70 dark:text-green-400/70">Tiempo Activo</p>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de Contacto -->
                    <div class="rounded-2xl bg-gradient-to-br from-gray-50 to-gray-100 p-8 dark:from-gray-800/50 dark:to-gray-700/50">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Envíanos un Mensaje</h3>
                        
                        <form class="space-y-6">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Nombre
                                    </label>
                                    <input
                                        type="text"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-500 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                                        placeholder="Tu nombre"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Email
                                    </label>
                                    <input
                                        type="email"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-500 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                                        placeholder="tu@email.com"
                                    />
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Asunto
                                </label>
                                <input
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-500 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                                    placeholder="¿En qué podemos ayudarte?"
                                />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Mensaje
                                </label>
                                <textarea
                                    rows="4"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-500 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                                    placeholder="Escribe tu mensaje aquí..."
                                ></textarea>
                            </div>
                            
                            <button
                                type="submit"
                                class="w-full rounded-lg bg-gradient-to-r from-green-600 to-green-700 px-6 py-3 font-medium text-white transition-all hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-500/20"
                            >
                                Enviar Mensaje
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-green-100 bg-white dark:border-green-900 dark:bg-gray-900">
            <div class="container mx-auto px-4 py-8">
                <div class="flex flex-col items-center justify-between gap-4 text-center md:flex-row md:text-left">
                    <div class="flex items-center gap-2">
                        <Recycle class="h-6 w-6 text-green-600 dark:text-green-400" />
                        <span class="font-semibold text-green-800 dark:text-green-200">EcoPoints</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">© 2025 EcoPoints. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
