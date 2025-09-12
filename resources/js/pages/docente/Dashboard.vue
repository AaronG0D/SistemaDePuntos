<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, ChevronRight, GraduationCap, LineChart, Star, Users } from 'lucide-vue-next';
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

import { onMounted } from 'vue';

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
</script>

<template>
    <Head title="Dashboard Docente" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <header class="mb-6">
                <h1 class="text-3xl font-bold">Dashboard Docente</h1>
                <p class="text-muted-foreground">Gestiona tus cursos y estudiantes</p>
            </header>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="info in cursosYMaterias" :key="info.idCursoParalelo">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <GraduationCap class="h-5 w-5" />
                            <Link :href="route('docente.curso.detalle', info.idCursoParalelo)" class="hover:underline">
                                {{ info.curso.nombre }} "{{ info.curso.paralelo }}"
                            </Link>
                        </CardTitle>
                        <CardDescription>
                            {{ info.materias.length || 0 }} materias · {{ info.estudiantes.length || 0 }} estudiantes
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="info.materias?.length" class="mb-3 space-y-1">
                            <div v-for="m in info.materias" :key="m.idMateria" class="flex items-center justify-between text-sm">
                                <span class="truncate pr-2">{{ m.nombre }}</span>
                                <span class="text-muted-foreground">
                                    <span class="font-medium">{{ m.asignadosPeriodoActual || 0 }}</span>
                                    /
                                    <span>{{ m.totalEstudiantes || 0 }}</span>
                                </span>
                            </div>
                        </div>
                        <div v-if="info.bimestres?.length" class="space-y-2">
                            <details v-for="b in info.bimestres" :key="b.idPeriodo" class="rounded-md border p-2">
                                <summary class="cursor-pointer list-none text-xs font-medium text-muted-foreground">
                                    {{ b.nombre }} ({{ b.codigo }})
                                </summary>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <span
                                        v-for="bm in b.materias"
                                        :key="bm.idMateria"
                                        :class="[
                                            'inline-flex items-center rounded-full border px-2 py-0.5 text-xs',
                                            bm.asignados === bm.totalEstudiantes ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-muted text-foreground/80'
                                        ]"
                                        title="{{ bm.nombre }}: {{ bm.asignados }} / {{ bm.totalEstudiantes }}"
                                    >
                                        {{ bm.nombre }} {{ bm.asignados }}/{{ bm.totalEstudiantes }}
                                    </span>
                                </div>
                            </details>
                        </div>
                        <Link :href="route('docente.curso.detalle', info.idCursoParalelo)">
                            <Button class="w-full">
                                Ver curso
                            </Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>

        </div>
    </AppLayout>
</template>
