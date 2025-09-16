<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, ChartBar, GraduationCap, Medal, Recycle, Star, Trash2, Trophy } from 'lucide-vue-next';
import { ref } from 'vue';
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
}>();

// Estado para los filtros
const cursoSeleccionado = ref('all');
const paraleloSeleccionado = ref('all');

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
</script>

<template>
    <Head title="EcoPoints - Sistema de Gestión de Residuos">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="min-h-screen bg-gradient-to-b from-green-50 to-white dark:from-green-950 dark:to-gray-900">
        <!-- Navegación -->
        <header class="border-b border-green-100 dark:border-green-900">
            <nav class="container mx-auto flex items-center justify-between p-4">
                <div class="flex items-center gap-2">
                    <Recycle class="h-8 w-8 text-green-600 dark:text-green-400" />
                    <span class="text-xl font-bold text-green-800 dark:text-green-200">EcoPoints</span>
                </div>
                <div class="flex items-center gap-4">
                    <Link
                        v-if="page.props.auth.user"
                        :href="route('admin.dashboard')"
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
        <section class="container mx-auto px-4 py-16 lg:py-24">
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
                        <Link
                            :href="route('login')"
                            class="flex items-center gap-2 rounded-lg border border-green-200 px-6 py-3 font-medium text-green-700 transition hover:bg-green-50 dark:border-green-800 dark:text-green-300 dark:hover:bg-green-900"
                        >
                            Saber Más
                        </Link>
                    </div>
                </div>
                <div class="relative">
                    <div class="aspect-square rounded-3xl bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900 dark:to-green-800">
                        <Recycle class="absolute inset-0 m-auto h-32 w-32 text-green-600 dark:text-green-400" />
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

        <!-- Ranking de Estudiantes -->
        <section class="border-t border-green-100 bg-white dark:border-green-900 dark:bg-gray-900">
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
                        class="relative rounded-xl bg-white p-6 shadow-lg transition-transform hover:scale-105 dark:bg-gray-800"
                    >
                        <!-- Medalla para los tres primeros lugares -->
                        <div v-if="index < 3" class="absolute -top-3 -right-3">
                            <div
                                class="rounded-full p-2"
                                :class="{
                                    'bg-yellow-100 dark:bg-yellow-900': index === 0,
                                    'bg-gray-100 dark:bg-gray-700': index === 1,
                                    'bg-orange-100 dark:bg-orange-900': index === 2,
                                }"
                            >
                                <Medal
                                    class="h-6 w-6"
                                    :class="{
                                        'text-yellow-600 dark:text-yellow-400': index === 0,
                                        'text-gray-600 dark:text-gray-400': index === 1,
                                        'text-orange-600 dark:text-orange-400': index === 2,
                                    }"
                                />
                            </div>
                        </div>

                        <div class="mb-4 flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                <Trophy class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    {{ estudiante?.user?.nombres || 'Sin nombre' }} {{ estudiante?.user?.primerApellido || '' }}
                                </h3>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-100"
                                    >
                                        <GraduationCap class="h-3 w-3" />
                                        {{ estudiante?.curso_paralelo?.curso?.nombre || 'Sin curso' }}
                                    </span>
                                    <span
                                        v-if="estudiante?.curso_paralelo?.paralelo?.nombre"
                                        class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-100"
                                    >
                                        {{ estudiante?.curso_paralelo?.paralelo?.nombre }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Star class="h-5 w-5 text-green-600 dark:text-green-400" />
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ estudiante?.user?.puntaje?.puntajeTotal || 0 }} puntos
                                </span>
                            </div>
                            <span class="text-sm text-gray-600 dark:text-gray-400"> Posición #{{ index + 1 }} </span>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center text-gray-600 dark:text-gray-400">¡Sé el primero en unirte y aparecer en nuestro ranking!</div>
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
