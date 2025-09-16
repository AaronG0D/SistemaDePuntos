<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, BookText, Calendar, GraduationCap, School, ScrollText, Target, Trophy, Users2 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

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

interface CursoCardInfo {
    idCursoParalelo: string | number;
    curso: { nombre: string; paralelo: string };
    materias: MateriaInfo[];
    estudiantes: Estudiante[];
}

interface Materia {
    asignadosPeriodoActual: number;
    totalEstudiantes: number;
    idMateria: number;
    nombre: string;
}

interface BimestreMateriaChip {
    idMateria: number;
    nombre: string;
    asignados: number;
    totalEstudiantes: number;
}

interface BimestreInfo {
    idPeriodo: number;
    nombre: string;
    codigo: string;
    materias: BimestreMateriaChip[];
}

interface CursoInfo {
    idCursoParalelo: string;
    curso: {
        nombre: string;
        paralelo: string;
    };
    materias: MateriaInfo[];
    estudiantes: Estudiante[];
    bimestres?: BimestreInfo[];
}

interface EstudiantesResponse {
    data: Estudiante[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    cursosYMaterias: CursoInfo[];
}>();

const currentCursoInfo = computed(() => {
    if (!cursoActivo.value) return null;
    return props.cursosYMaterias.find((c) => String(c.idCursoParalelo) === String(cursoActivo.value)) || null;
});

onMounted(() => {
    if (!cursoActivo.value && props.cursosYMaterias && props.cursosYMaterias.length > 0) {
        cursoActivo.value = String(props.cursosYMaterias[0].idCursoParalelo);
        // precargar estudiantes del primer curso
        console.log('Props cursosYMaterias:', props.cursosYMaterias);
        cargarEstudiantes(cursoActivo.value);
    }
});

const cursoActivo = ref<string | null>(null);
const materiaSeleccionada = ref<number | null>(null);
const estadisticas = ref<any>(null);
const searchQuery = ref('');
const estudiantes = ref<EstudiantesResponse>({
    data: [],
    total: 0,
    per_page: 10,
    current_page: 1,
    last_page: 1,
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function debounceSearch() {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        if (cursoActivo.value) {
            cargarEstudiantes(cursoActivo.value);
        }
    }, 300);
}
const showDetalles = ref(false);
const estudianteSeleccionado = ref<Estudiante | null>(null);
const materiasSeleccionadas = ref<number[]>([]);
const selectedMateriasMasivo = ref<number[]>([]);

async function cargarEstudiantes(idCursoParalelo: string, page: number = 1) {
    try {
        console.log('Cargando estudiantes para curso:', idCursoParalelo, 'search=', searchQuery.value, 'page=', page);
        // Usar la ruta web autenticada para mantener la sesión y middleware web
        const response = await fetch(`/docente/estudiantes/${idCursoParalelo}?page=${page}&search=${encodeURIComponent(searchQuery.value)}`, {
            credentials: 'same-origin',
        });
        const data = await response.json();
        console.log('Respuesta estudiantes:', data);
        estudiantes.value = data;
        cursoActivo.value = idCursoParalelo;
    } catch (error) {
        console.error('Error cargando estudiantes:', error);
    }
}

function cambiarPagina(page: number) {
    if (cursoActivo.value) {
        cargarEstudiantes(cursoActivo.value, page);
    }
}

function mostrarDetalles(estudiante: Estudiante) {
    estudianteSeleccionado.value = estudiante;
    materiasSeleccionadas.value = [];
    showDetalles.value = true;
}

function toggleMateria(materiaId: number) {
    const index = materiasSeleccionadas.value.indexOf(materiaId);
    if (index === -1) {
        materiasSeleccionadas.value.push(materiaId);
    } else {
        materiasSeleccionadas.value.splice(index, 1);
    }
}

function toggleMateriaMasivo(materiaId: number) {
    const index = selectedMateriasMasivo.value.indexOf(materiaId);
    if (index === -1) {
        selectedMateriasMasivo.value.push(materiaId);
    } else {
        selectedMateriasMasivo.value.splice(index, 1);
    }
}

async function generarReporte() {
    if (!estudianteSeleccionado.value || !cursoActivo.value) return;

    try {
        const response = await fetch('/api/docente/reporte-puntos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                estudiante_id: estudianteSeleccionado.value.id,
                curso_id: cursoActivo.value,
                materias: materiasSeleccionadas.value,
            }),
        });

        const result = await response.json();
        console.log('Resultado reporte:', result);
        if (response.ok) {
            // Mostrar confirmación simple al usuario
            alert(result.message || 'Reporte solicitado correctamente');
            showDetalles.value = false;
        } else {
            alert(result.error || 'Error generando el reporte');
            console.error('Error generando el reporte:', result);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function generarReporteMasivo(idCursoParalelo: string) {
    try {
        if (!selectedMateriasMasivo.value.length) {
            alert('Selecciona al menos una materia para generar el reporte masivo.');
            return;
        }

        const response = await fetch('/api/docente/reporte-masivo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                curso_id: idCursoParalelo,
                materias: selectedMateriasMasivo.value,
            }),
        });

        const result = await response.json();
        console.log('Resultado reporte masivo:', result);
        if (response.ok) {
            alert(result.message || `Reporte masivo solicitado para ${result.total} estudiantes.`);
            // limpiar selección opcional
            selectedMateriasMasivo.value = [];
        } else {
            alert(result.error || 'Error generando el reporte masivo');
            console.error('Error reporte masivo:', result);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function cargarEstadisticasCurso(idCursoParalelo: string) {
    try {
        const response = await fetch(`/docente/reportes/curso/${idCursoParalelo}`, { credentials: 'same-origin' });
        estadisticas.value = await response.json();
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

async function cargarEstadisticasMateria(idCursoParalelo: string, idMateria: number) {
    try {
        const response = await fetch(`/docente/reportes/materia/${idCursoParalelo}/${idMateria}`, { credentials: 'same-origin' });
        estadisticas.value = await response.json();
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

// Nuevos estados para filtros
const searchFilter = ref('');
const selectedTipo = ref('all');
const selectedParalelos = ref<string[]>([]);

// Modificar los tipos de curso para reflejar mejor la estructura
const tiposCurso = [
    { id: 'all', nombre: 'Todos los niveles', icon: GraduationCap },
    { id: 'bachillerato', nombre: 'Bachillerato', icon: School, cursos: ['5to', '6to'] },
    { id: 'basica', nombre: 'Educación Básica', icon: BookOpen, cursos: ['1ro', '2do', '3ro', '4to'] },
];

// Computed para paralelos disponibles
const paralelosDisponibles = computed(() => {
    const paralelos = new Set<string>();
    props.cursosYMaterias.forEach((curso) => {
        paralelos.add(curso.curso.paralelo);
    });
    return Array.from(paralelos).sort();
});

// Modificar el computed para filtrar y ordenar cursos
const cursosFiltrados = computed(() => {
    // Función helper para extraer el número del curso
    const getCursoNumero = (nombre: string): number => {
        const match = nombre.toLowerCase().match(/(\d+)to/);
        return match ? parseInt(match[1]) : 0;
    };

    return props.cursosYMaterias
        .filter((curso) => {
            // Mantener la lógica de filtrado existente
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

            if (selectedParalelos.value.length > 0) {
                if (!selectedParalelos.value.includes(curso.curso.paralelo)) {
                    return false;
                }
            }

            return true;
        })
        .sort((a, b) => {
            // Ordenar de mayor a menor (6to a 1ro)
            const numA = getCursoNumero(a.curso.nombre);
            const numB = getCursoNumero(b.curso.nombre);
            return numB - numA; // Orden descendente
        });
});

// Métodos para filtros
function filtrarPorTipo(tipo: string) {
    selectedTipo.value = tipo;
}

function toggleParalelo(paralelo: string) {
    const index = selectedParalelos.value.indexOf(paralelo);
    if (index === -1) {
        selectedParalelos.value.push(paralelo);
    } else {
        selectedParalelos.value.splice(index, 1);
    }
}

function limpiarFiltros() {
    searchFilter.value = '';
    selectedTipo.value = 'all';
    selectedParalelos.value = [];
}

// Añadir computed properties para estadísticas
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

// Valor de ejemplo para reportes pendientes
const reportesPendientes = ref(5);
</script>

<template>
    <Head title="Dashboard Docente" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- Período Activo Badge -->
            <div class="fixed top-4 height-4 z-50">
                <div class="rounded-lg border bg-white/95 p-3 shadow-lg backdrop-blur dark:border-gray-700 dark:bg-gray-800/95">
                    <div class="flex items-center gap-2">
                        <Calendar class="text-primary h-5 w-5" />
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Período Actual</p>
                            <p class="text-primary text-sm font-bold">2025-2026</p>
                            <p class="text-primary/80 text-xs font-medium">Tercer Bimestre</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header y filtros -->
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Dashboard Docente</h1>
                    <p class="text-muted-foreground">Gestiona tus cursos y estudiantes</p>
                </div>
            </div>

           

            <!-- Nuevo componente de filtros mejorado -->
            <div class="mb-6 rounded-lg border bg-white/95 p-4 shadow-sm backdrop-blur dark:border-gray-700 dark:bg-gray-800/95">
                <div class="space-y-4">
                    <!-- Barra de búsqueda -->
                    <div class="flex items-center gap-4">
                        <div class="relative flex-1">
                            <Input v-model="searchFilter" placeholder="Buscar por curso o paralelo..." class="bg-white pl-10 dark:bg-gray-900">
                                <template #prefix>
                                    <Search class="absolute left-3 h-4 w-4 text-gray-500 dark:text-gray-400" />
                                </template>
                            </Input>
                        </div>
                        <Button variant="outline" @click="limpiarFiltros" class="shrink-0 dark:border-gray-600 dark:hover:bg-gray-700">
                            <XCircle class="mr-2 h-4 w-4" />
                            Limpiar filtros
                        </Button>
                    </div>

                    <!-- Filtros de nivel educativo -->
                    <div class="flex flex-wrap gap-2">
                        <Label class="mr-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <GraduationCap class="mr-1 inline h-4 w-4" />
                            Nivel:
                        </Label>
                        <Button
                            v-for="tipo in tiposCurso"
                            :key="tipo.id"
                            variant="outline"
                            size="sm"
                            :class="[
                                'rounded-full transition-colors',
                                selectedTipo === tipo.id
                                    ? 'bg-primary dark:bg-primary border-primary text-white dark:text-white'
                                    : 'hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700',
                            ]"
                            @click="filtrarPorTipo(tipo.id)"
                        >
                            <component :is="tipo.icon" class="mr-2 h-4 w-4" />
                            {{ tipo.nombre }}
                        </Button>
                    </div>

                    <!-- Chips de paralelos -->
                    <div class="flex flex-wrap gap-2">
                        <Label class="mr-2 text-sm font-medium text-gray-700 dark:text-gray-300"> Paralelos: </Label>
                        <button
                            v-for="paralelo in paralelosDisponibles"
                            :key="paralelo"
                            @click="toggleParalelo(paralelo)"
                            class="rounded-full px-3 py-1 text-sm transition-colors"
                            :class="[
                                selectedParalelos.includes(paralelo)
                                    ? 'bg-blue-600 text-white dark:bg-blue-500'
                                    : 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/50 dark:text-blue-300 dark:hover:bg-blue-800/50',
                            ]"
                        >
                            Paralelo "{{ paralelo }}"
                        </button>
                    </div>

                    <!-- Indicadores de filtros activos -->
                    <div
                        v-if="selectedTipo !== 'all' || selectedParalelos.length > 0 || searchFilter"
                        class="flex flex-wrap items-center gap-2 text-sm"
                    >
                        <span class="font-medium text-gray-600 dark:text-gray-400">Filtros activos:</span>
                        <div v-if="selectedTipo !== 'all'" class="bg-primary/10 text-primary dark:bg-primary/20 rounded-md px-2 py-0.5">
                            {{ tiposCurso.find((t) => t.id === selectedTipo)?.nombre }}
                        </div>
                        <div
                            v-for="paralelo in selectedParalelos"
                            :key="paralelo"
                            class="rounded-md bg-blue-100 px-2 py-0.5 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300"
                        >
                            Paralelo "{{ paralelo }}"
                        </div>
                        <div v-if="searchFilter" class="rounded-md bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            "{{ searchFilter }}"
                        </div>
                    </div>
                </div>
            </div>
             <!-- Estadísticas con iconos mejorados -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card class="border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
                    <CardContent class="flex items-center gap-4 p-4">
                        <div class="rounded-full bg-purple-500 p-3">
                            <Users2 class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-purple-600 dark:text-purple-300">Estudiantes</p>
                            <p class="text-2xl font-bold text-purple-700 dark:text-purple-200">{{ totalEstudiantes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                    <CardContent class="flex items-center gap-4 p-4">
                        <div class="rounded-full bg-blue-500 p-3">
                            <BookText class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-600 dark:text-blue-300">Materias</p>
                            <p class="text-2xl font-bold text-blue-700 dark:text-blue-200">{{ totalMaterias }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-emerald-200 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20">
                    <CardContent class="flex items-center gap-4 p-4">
                        <div class="rounded-full bg-emerald-500 p-3">
                            <Target class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-emerald-600 dark:text-emerald-300">Cursos Activos</p>
                            <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-200">{{ cursosYMaterias.length }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-amber-200 bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20">
                    <CardContent class="flex items-center gap-4 p-4">
                        <div class="rounded-full bg-amber-500 p-3">
                            <ScrollText class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-amber-600 dark:text-amber-300">Reportes Pendientes</p>
                            <p class="text-2xl font-bold text-amber-700 dark:text-amber-200">{{ reportesPendientes }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Grid de cursos con filtrado -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="info in cursosFiltrados"
                    :key="info.idCursoParalelo"
                    :href="route('docente.curso.detalle', info.idCursoParalelo)"
                    class="group block transition-all duration-200 hover:scale-105"
                >
                    <Card class="hover:border-primary h-full cursor-pointer border-2 transition-all duration-200 hover:shadow-lg">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-3 text-lg">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-md"
                                >
                                    <GraduationCap class="h-5 w-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate font-bold text-gray-900 dark:text-gray-100">
                                        {{ info.curso.nombre }}
                                    </div>
                                    <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Paralelo "{{ info.curso.paralelo }}"</div>
                                </div>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Estadísticas principales -->
                            <div class="grid grid-cols-2 gap-3">
                                <div
                                    class="rounded-lg border border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-3 text-center dark:border-green-800 dark:from-green-950/30 dark:to-emerald-950/30"
                                >
                                    <div class="text-2xl font-bold text-green-700 dark:text-green-400">{{ info.estudiantes.length || 0 }}</div>
                                    <div class="text-xs font-medium text-green-600 dark:text-green-500">Estudiantes</div>
                                </div>
                                <div
                                    class="rounded-lg border border-purple-200 bg-gradient-to-br from-purple-50 to-violet-50 p-3 text-center dark:border-purple-800 dark:from-purple-950/30 dark:to-violet-950/30"
                                >
                                    <div class="text-2xl font-bold text-purple-700 dark:text-purple-400">{{ info.materias.length || 0 }}</div>
                                    <div class="text-xs font-medium text-purple-600 dark:text-purple-500">Materias</div>
                                </div>
                            </div>

                            <!-- Top 3 estudiantes con más puntos -->
                            <div v-if="info.estudiantes?.length" class="space-y-2">
                                <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <Trophy class="h-4 w-4 text-yellow-500" />
                                    <span>Top Estudiantes</span>
                                </div>
                                <div class="space-y-1">
                                    <div
                                        v-for="(estudiante, index) in info.estudiantes.slice(0, 3)"
                                        :key="estudiante.id"
                                        class="flex items-center justify-between rounded-md bg-gray-50 p-2 dark:bg-gray-800"
                                    >
                                        <div class="flex min-w-0 flex-1 items-center gap-2">
                                            <div
                                                :class="[
                                                    'flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold text-white',
                                                    index === 0 ? 'bg-yellow-500' : index === 1 ? 'bg-gray-400' : 'bg-orange-500',
                                                ]"
                                            >
                                                {{ index + 1 }}
                                            </div>
                                            <span class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ estudiante.apellidos }}
                                            </span>
                                        </div>
                                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                            {{ estudiante.puntaje }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Progreso de materias con icono -->
                            <div v-if="info.materias?.length" class="space-y-2">
                                <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <BookOpen class="h-4 w-4 text-blue-500" />
                                    <span>Progreso por Materia</span>
                                </div>
                                <div class="space-y-2">
                                    <div v-for="m in info.materias.slice(0, 3)" :key="m.idMateria" class="space-y-1">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="truncate font-medium text-gray-700 dark:text-gray-300">{{ m.nombre }}</span>
                                            <span class="text-gray-500 dark:text-gray-400">
                                                {{ m.asignadosPeriodoActual || 0 }}/{{ m.totalEstudiantes || 0 }}
                                            </span>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                            <div
                                                class="h-full rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 transition-all duration-300"
                                                :style="{ width: `${((m.asignadosPeriodoActual || 0) / (m.totalEstudiantes || 1)) * 100}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Indicador de acción -->
                            <div
                                class="flex items-center justify-center pt-2 text-sm text-gray-500 transition-colors group-hover:text-blue-600 dark:text-gray-400 dark:group-hover:text-blue-400"
                            >
                                <span class="font-medium">Hacer clic para gestionar</span>
                                <ChevronRight class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
