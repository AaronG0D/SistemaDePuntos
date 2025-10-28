<template>
    <Head title="Dashboard Docente" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- Header simplificado -->
            <header class="mb-6">
                <div class="rounded-lg border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                    <h1 class="flex items-center gap-3 text-2xl font-semibold text-slate-800 dark:text-slate-100">
                        <BookOpen class="h-5 w-5 text-slate-600" />
                        Dashboard Docente
                    </h1>
                    <p class="mt-1 text-slate-600 dark:text-slate-400">Gestiona tus cursos y estudiantes de manera eficiente</p>
                </div>
            </header>

            <!-- Estadísticas simplificadas -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                <Card class="border border-slate-200 dark:border-slate-700">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-800">
                                <Users2 class="h-5 w-5 text-blue-600 dark:text-blue-300" />
                            </div>
                            <div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Estudiantes</p>
                                <p class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ totalEstudiantes }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border border-slate-200 dark:border-slate-700">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-green-100 p-2 dark:bg-green-800">
                                <BookText class="h-5 w-5 text-green-600 dark:text-green-300" />
                            </div>
                            <div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Materias</p>
                                <p class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ totalMaterias }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border border-slate-200 dark:border-slate-700">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-800">
                                <Target class="h-5 w-5 text-purple-600 dark:text-purple-300" />
                            </div>
                            <div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Cursos</p>
                                <p class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ cursosYMaterias.length }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border border-slate-200 dark:border-slate-700">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-800">
                                <ScrollText class="h-5 w-5 text-amber-600 dark:text-amber-300" />
                            </div>
                            <div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Reportes</p>
                                <p class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ reportesPendientes }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filtros simplificados -->
            <Card class="mb-6">
                <CardContent class="p-4">
                    <div class="flex flex-col gap-4 md:flex-row">
                        <div class="flex-1">
                            <Input v-model="searchFilter" placeholder="Buscar por curso o paralelo..." class="w-full" />
                        </div>
                        <div class="flex gap-2">
                            <Select v-model="selectedTipo">
                                <SelectTrigger class="w-48">
                                    <SelectValue placeholder="Filtrar por nivel" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los niveles</SelectItem>
                                    <SelectItem value="bachillerato">Bachillerato</SelectItem>
                                    <SelectItem value="basica">Educación Básica</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button variant="outline" @click="limpiarFiltros" size="sm">
                                <XCircle class="mr-2 h-4 w-4" />
                                Limpiar
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Grid de cursos simplificado -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="info in cursosFiltrados"
                    :key="info.idCursoParalelo"
                    :href="route('docente.curso.detalle', info.idCursoParalelo)"
                    class="group block transition-all duration-200 hover:scale-105"
                >
                    <Card
                        class="h-full cursor-pointer border border-slate-200 shadow-sm transition-all duration-200 hover:border-slate-300 hover:shadow-md dark:border-slate-700 dark:hover:border-slate-600"
                    >
                        <CardHeader class="bg-slate-50 dark:bg-slate-800/50">
                            <CardTitle class="flex items-center gap-3 text-lg">
                                <div class="rounded-lg bg-slate-100 p-2 dark:bg-slate-700">
                                    <GraduationCap class="h-5 w-5 text-slate-600 dark:text-slate-400" />
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900 dark:text-slate-100">
                                        {{ info.curso.nombre }}
                                    </div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400">Paralelo "{{ info.curso.paralelo }}"</div>
                                </div>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-4">
                            <!-- Estadísticas del curso -->
                            <div class="mb-4 grid grid-cols-2 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ info.estudiantes.length || 0 }}</div>
                                    <div class="text-xs text-slate-600 dark:text-slate-400">Estudiantes</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ info.materias.length || 0 }}</div>
                                    <div class="text-xs text-slate-600 dark:text-slate-400">Materias</div>
                                </div>
                            </div>

                            <!-- Top estudiantes -->
                            <div v-if="info.estudiantes?.length" class="space-y-2">
                                <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300">Top estudiantes:</h4>
                                <div class="space-y-1">
                                    <div
                                        v-for="(estudiante, index) in info.estudiantes.slice(0, 3)"
                                        :key="estudiante.id"
                                        class="flex items-center justify-between text-sm"
                                    >
                                        <span class="text-slate-600 dark:text-slate-400"
                                            >{{ index + 1 }}. {{ estudiante.nombres }} {{ estudiante.apellidos }}</span
                                        >
                                        <span class="font-medium text-slate-900 dark:text-slate-100">{{ estudiante.puntaje }} pts</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Materias -->
                            <div v-if="info.materias?.length" class="mt-4">
                                <h4 class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-300">Materias:</h4>
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="materia in info.materias.slice(0, 3)"
                                        :key="materia.idMateria"
                                        class="rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-600 dark:bg-slate-700 dark:text-slate-400"
                                    >
                                        {{ materia.nombre }}
                                    </span>
                                    <span
                                        v-if="info.materias.length > 3"
                                        class="rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-600 dark:bg-slate-700 dark:text-slate-400"
                                    >
                                        +{{ info.materias.length - 3 }} más
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <!-- Mensaje cuando no hay cursos -->
            <div v-if="cursosFiltrados.length === 0" class="py-12 text-center">
                <GraduationCap class="mx-auto h-12 w-12 text-slate-400" />
                <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-200">No se encontraron cursos</h3>
                <p class="mt-1 text-sm text-slate-500">Intenta ajustar los filtros de búsqueda.</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, BookText, GraduationCap, ScrollText, Target, Users2, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Estudiante {
    id: number;
    nombres: string;
    apellidos: string;
    puntaje: number;
}

interface MateriaInfo {
    idMateria: number;
    nombre: string;
    asignadosPeriodoActual?: number;
    totalEstudiantes?: number;
}

interface CursoInfo {
    idCursoParalelo: string;
    curso: {
        nombre: string;
        paralelo: string;
    };
    materias: MateriaInfo[];
    estudiantes: Estudiante[];
}

const props = defineProps<{
    cursosYMaterias: CursoInfo[];
}>();

// Estado reactivo
const searchFilter = ref('');
const selectedTipo = ref('all');

// Computed properties
const totalEstudiantes = computed(() => {
    return props.cursosYMaterias.reduce((total, curso) => {
        return total + curso.estudiantes.length;
    }, 0);
});

const totalMaterias = computed(() => {
    const materiasUnicas = new Set();
    props.cursosYMaterias.forEach((curso) => {
        curso.materias.forEach((materia) => {
            materiasUnicas.add(materia.idMateria);
        });
    });
    return materiasUnicas.size;
});

const cursosFiltrados = computed(() => {
    return props.cursosYMaterias
        .filter((curso) => {
            if (searchFilter.value) {
                const searchTerm = searchFilter.value.toLowerCase();
                const cursoText = `${curso.curso.nombre} ${curso.curso.paralelo}`.toLowerCase();
                if (!cursoText.includes(searchTerm)) return false;
            }

            if (selectedTipo.value !== 'all') {
                const cursoNombre = curso.curso.nombre.toLowerCase();
                if (selectedTipo.value === 'bachillerato') {
                    if (!['5to', '6to'].some((nivel) => cursoNombre.includes(nivel))) {
                        return false;
                    }
                } else if (selectedTipo.value === 'basica') {
                    if (!['1ro', '2do', '3ro', '4to'].some((nivel) => cursoNombre.includes(nivel))) {
                        return false;
                    }
                }
            }

            return true;
        })
        .sort((a, b) => {
            const getCursoNumero = (nombre: string): number => {
                const match = nombre.toLowerCase().match(/(\d+)to/);
                return match ? parseInt(match[1]) : 0;
            };
            const numA = getCursoNumero(a.curso.nombre);
            const numB = getCursoNumero(b.curso.nombre);
            return numB - numA;
        });
});

// Métodos
function limpiarFiltros() {
    searchFilter.value = '';
    selectedTipo.value = 'all';
}

// Valor de ejemplo para reportes pendientes
const reportesPendientes = ref(5);
</script>
