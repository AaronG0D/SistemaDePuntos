<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { BarChart3, BookOpen, Calendar, ChevronLeft, Clock, FileSpreadsheet, FileText, Users } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface Periodo {
    idPeriodo: number;
    nombre: string;
    codigo: string;
    fecha_inicio?: string;
    fecha_fin?: string;
}

interface CursoInfo {
    idCursoParalelo: string;
    curso: {
        idCursoParalelo: any;
        nombre: string;
        paralelo: string;
    };
    materias: { idMateria: number; nombre: string }[];
}

interface Estudiante {
    id: number;
    nombres: string;
    apellidos: string;
    puntaje: number;
}

interface Props {
    curso: CursoInfo;
    periodos: Periodo[];
    periodoSeleccionado: number | null;
    estudiantes: {
        data: Estudiante[];
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
    atribuidosPorMateria: Record<number, number[]>;
    periodoActivoId?: number | null;
}

const props = defineProps<Props>();

const materiaId = ref<string>('');
const materiasSeleccionadasPorPeriodo = ref<Record<string, Set<number>>>({});
const comentario = ref<string>('');
const selectedIds = ref<Set<number>>(new Set());
const selectAllOnPage = ref<boolean>(false);
const selectAllFiltered = ref<boolean>(false);
const periodoId = ref<string>(
    props.periodoSeleccionado ? String(props.periodoSeleccionado) : props.periodoActivoId ? String(props.periodoActivoId) : '',
);
const searchQuery = ref('');
const currentPeriodo = computed(() => props.periodos.find((p) => String(p.idPeriodo) === periodoId.value));
const cursoData = computed(() => props.curso);
const materiasSeleccionadasActuales = computed(() => {
    const periodo = periodoId.value || 'default';
    return materiasSeleccionadasPorPeriodo.value[periodo] || new Set<number>();
});
const materiasDisponibles = computed(() => {
    return cursoData.value.materias.filter((m) => materiasSeleccionadasActuales.value.has(m.idMateria));
});
const pageTitle = computed(() => `Curso ${cursoData.value.curso.nombre} "${cursoData.value.curso.paralelo}"`);
const isPeriodoActivo = computed(() => {
    return props.periodoActivoId ? String(props.periodoActivoId) === periodoId.value : true;
});

onMounted(() => {
    if (periodoId.value) {
        const activeButton = document.getElementById(`periodo-btn-${periodoId.value}`);
        if (activeButton) {
            activeButton.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }
});

// Función para toggle de materias por período
function toggleMateria(idMateria: number) {
    const periodo = periodoId.value || 'default';

    // Inicializar el Set para este período si no existe
    if (!materiasSeleccionadasPorPeriodo.value[periodo]) {
        materiasSeleccionadasPorPeriodo.value[periodo] = new Set<number>();
    }

    const materiasDelPeriodo = materiasSeleccionadasPorPeriodo.value[periodo];

    if (materiasDelPeriodo.has(idMateria)) {
        materiasDelPeriodo.delete(idMateria);
        // Si la materia actual se desactiva, limpiar selección
        if (materiaId.value === String(idMateria)) {
            materiaId.value = '';
        }
    } else {
        materiasDelPeriodo.add(idMateria);
    }
}

// Estadísticas de reporte para materia específica
const estadisticasReporte = ref({
    total_estudiantes: 0,
    estudiantes_con_puntos: 0,
    puntos_asignados_total: 0,
    promedio_asignados: 0,
});

// Función para obtener estadísticas de reporte por materia
async function obtenerEstadisticasReporte() {
    if (!materiaId.value) {
        estadisticasReporte.value = {
            total_estudiantes: props.estudiantes.data.length,
            estudiantes_con_puntos: 0,
            puntos_asignados_total: 0,
            promedio_asignados: 0,
        };
        return;
    }

    try {
        const params = new URLSearchParams();
        if (periodoId.value) {
            params.append('periodo_id', periodoId.value);
        }

        const response = await fetch(`/docente/curso/${cursoData.value.curso.idCursoParalelo}/materia/${materiaId.value}/reporte?${params}`, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
        });

        if (response.ok) {
            const data = await response.json();
            estadisticasReporte.value = data;
        }
    } catch (error) {
        console.error('Error obteniendo estadísticas de reporte:', error);
    }
}

// Estadísticas dinámicas basadas en el período seleccionado (para vista general)
const estadisticasPeriodo = computed(() => {
    const estudiantesActuales = props.estudiantes.data;
    const totalEstudiantes = estudiantesActuales.length;
    const estudiantesConPuntos = estudiantesActuales.filter((e) => e.puntaje > 0).length;
    const puntajeTotal = estudiantesActuales.reduce((sum, e) => sum + (e.puntaje || 0), 0);
    const puntajePromedio = totalEstudiantes > 0 ? puntajeTotal / totalEstudiantes : 0;

    return {
        totalEstudiantes,
        estudiantesConPuntos,
        puntajeTotal,
        puntajePromedio: Math.round(puntajePromedio * 100) / 100,
    };
});

// Watchers para actualizar estadísticas cuando cambie materia o período
watch(
    [materiaId, periodoId],
    () => {
        obtenerEstadisticasReporte();
    },
    { immediate: true },
);

// CSRF token para peticiones fetch (Laravel)
const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

// Estudiantes ya atribuidos para la materia seleccionada en el período actual
const atribuidosSet = computed(() => {
    const mid = Number(materiaId.value || 0);
    const ids = props.atribuidosPorMateria && props.atribuidosPorMateria[mid] ? props.atribuidosPorMateria[mid] : [];
    return new Set<number>(ids as number[]);
});

const totalSeleccionados = computed(() => {
    if (selectAllFiltered.value) {
        return 'todos los estudiantes';
    }
    return selectedIds.value.size;
});

function toggleAllOnPage() {
    if (selectAllOnPage.value) {
        props.estudiantes.data.forEach((e) => {
            if (e.puntaje === 0) return; // no seleccionar con 0 puntos
            if (!(materiaId.value && atribuidosSet.value.has(e.id))) selectedIds.value.add(e.id);
        });
    } else {
        props.estudiantes.data.forEach((e) => selectedIds.value.delete(e.id));
    }
}

function handleSelectAllFiltered() {
    if (selectAllFiltered.value) {
        // Cuando se activa selección global, limpiar todo
        selectedIds.value.clear();
        selectAllOnPage.value = false;

        // Marcar visualmente los checkboxes de la página actual para feedback
        props.estudiantes.data.forEach((e) => {
            if (e.puntaje === 0) return; // no seleccionar con 0 puntos
            if (!(materiaId.value && atribuidosSet.value.has(e.id))) {
                selectedIds.value.add(e.id);
            }
        });
    } else {
        // Limpiar selección cuando se desactiva
        selectedIds.value.clear();
        selectAllOnPage.value = false;
    }
}

function toggleOne(id: number, checked: boolean) {
    if (checked) selectedIds.value.add(id);
    else selectedIds.value.delete(id);
}

const confirmOpen = ref(false);
const successOpen = ref(false);
const errorOpen = ref(false);
const errorText = ref('');
const successInfo = ref<{
    materia: string;
    curso: string;
    periodo: string;
    cantidad: string;
    insertados?: number;
    omitidos?: number;
    estudiantes_afectados?: number;
}>({ materia: '', curso: '', periodo: '', cantidad: '' });
const confirmInfo = ref<{ materia: string; curso: string; periodo: string; cantidad: string }>({ materia: '', curso: '', periodo: '', cantidad: '' });
const isExporting = ref(false);
const exportandoExcel = ref(false);

// Estados para importación de estudiantes
const descargandoPlantilla = ref(false);
const archivoSeleccionado = ref<File | null>(null);
const importandoEstudiantes = ref(false);
const resultadosImportacion = ref<{
    exitosos: number;
    actualizados: number;
    errores: Array<{ fila: number; mensaje: string }>;
    mensajes: string[];
} | null>(null);

function abrirConfirmacion() {
    if (!materiaId.value || (selectedIds.value.size === 0 && !selectAllFiltered.value)) return;
    if (!isPeriodoActivo.value) return;
    const materiaNombre = cursoData.value.materias.find((m) => String(m.idMateria) === materiaId.value)?.nombre || '';
    const cursoNombre = `${cursoData.value.curso.nombre} "${cursoData.value.curso.paralelo}"`;
    const periodoLabel = currentPeriodo.value ? `${currentPeriodo.value.nombre} (${currentPeriodo.value.codigo})` : 'Período activo';
    confirmInfo.value = { materia: materiaNombre, curso: cursoNombre, periodo: periodoLabel, cantidad: String(totalSeleccionados.value) };
    confirmOpen.value = true;
}

async function asignar() {
    if (!materiaId.value || (selectedIds.value.size === 0 && !selectAllFiltered.value) || !isPeriodoActivo.value) return;
    const payload: Record<string, any> = {
        estudiantes: selectAllFiltered.value ? [] : Array.from(selectedIds.value),
        idMateria: Number(materiaId.value),
        comentario: comentario.value || undefined,
        select_all: selectAllFiltered.value || undefined,
        search: searchQuery.value || undefined,
    };
    if (periodoId.value !== '') payload.idPeriodo = Number(periodoId.value);

    try {
        const res = await fetch(route('docente.curso.asignar', props.curso.idCursoParalelo), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Inertia': 'true',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error((data && (data.error || data.message)) || 'Error al atribuir');
        }

        // Limpiar selección
        selectedIds.value.clear();
        selectAllOnPage.value = false;
        comentario.value = '';

        // Refrescar datos (estudiantes y atribuidosPorMateria) para que badges y checks se actualicen
        const q = new URLSearchParams();
        if (periodoId.value !== '') q.set('periodo_id', String(periodoId.value));
        if (searchQuery.value) q.set('search', searchQuery.value);
        await router.visit(route('docente.curso.detalle', props.curso.idCursoParalelo), {
            method: 'get',
            data: Object.fromEntries(q.entries()),
            only: ['estudiantes', 'atribuidosPorMateria', 'periodoSeleccionado'],
            preserveScroll: true,
            preserveState: true,
        });

        // Abrir diálogo de éxito con datos relevantes (usamos confirmInfo ya preparado)
        successInfo.value = {
            ...confirmInfo.value,
            insertados: data?.insertados,
            omitidos: data?.omitidos,
            estudiantes_afectados: data?.estudiantes_afectados,
        } as any;
        successOpen.value = true;
    } catch (err) {
        errorText.value = (err as Error).message || 'Error al atribuir';
        errorOpen.value = true;
    }
}

function goBack() {
    window.close();
    // Fallback: si no se puede cerrar, volver al dashboard
    router.visit(route('docente.dashboard'));
}

function applyPeriodo() {
    const params: Record<string, string> = {};
    if (periodoId.value !== '') params['periodo_id'] = periodoId.value;
    router.visit(route('docente.curso.detalle', props.curso.idCursoParalelo), {
        method: 'get',
        data: params,
        only: ['estudiantes', 'periodoSeleccionado', 'atribuidosPorMateria'],
        preserveScroll: true,
        preserveState: true,
    });
}

async function buscar(page?: number) {
    const q = new URLSearchParams();
    if (periodoId.value !== '') q.set('periodo_id', String(periodoId.value));
    if (searchQuery.value) q.set('search', searchQuery.value);
    if (page) q.set('page', String(page));
    router.visit(route('docente.curso.detalle', props.curso.idCursoParalelo), {
        method: 'get',
        data: Object.fromEntries(q.entries()),
        only: ['estudiantes'],
        preserveScroll: true,
        preserveState: true,
    });
}

async function exportarExcel() {
    if (!materiaId.value) {
        errorText.value = 'Debes seleccionar una materia para exportar';
        errorOpen.value = true;
        return;
    }

    exportandoExcel.value = true;
    try {
        const url =
            route('docente.curso.exportar-materia-excel', {
                idCursoParalelo: props.curso.idCursoParalelo,
                idMateria: materiaId.value,
            }) + (props.periodoSeleccionado ? `?idPeriodo=${props.periodoSeleccionado}` : '');

        // Crear un enlace temporal para descargar
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error('Error al exportar:', error);
    } finally {
        exportandoExcel.value = false;
    }
}
</script>
<template>
    <AppLayout>
        <!-- Período Activo Badge -->
        <div class="fixed top-4 right-4 z-50">
            <div class="dark:border-primary/20 rounded-lg border bg-white/95 p-3 shadow-lg backdrop-blur dark:bg-gray-800/95">
                <div class="flex items-center gap-2">
                    <Clock class="text-primary h-5 w-5 animate-pulse" />
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Período Actual</p>
                        <p class="text-primary text-sm font-bold">{{ currentPeriodo?.nombre || 'No seleccionado' }}</p>
                        <p class="text-primary/80 text-xs font-medium">{{ currentPeriodo?.codigo }}</p>
                    </div>
                </div>
            </div>
        </div>

        <Head :title="pageTitle" />
        <!-- Cambiar el div contenedor para usar un ancho similar al dashboard -->
        <div class="container mx-auto py-6">
            <!-- Header del curso -->
            <header class="mb-4 flex items-center justify-between">
                <div class="min-w-0">
                    <h1 class="truncate text-xl font-semibold sm:text-2xl">Curso {{ cursoData.curso.nombre }} "{{ cursoData.curso.paralelo }}"</h1>
                    <div class="text-muted-foreground flex items-center gap-2 text-sm">
                        <span>{{ cursoData.materias.length }} materias</span>
                        <span>·</span>
                        <div class="flex items-center gap-1">
                            <Calendar class="h-4 w-4" />
                            <span v-if="currentPeriodo">
                                {{ currentPeriodo.nombre }}
                                <span class="font-medium text-blue-600 dark:text-blue-400"> ({{ currentPeriodo.codigo }}) </span>
                            </span>
                            <span v-else>Todos los períodos</span>
                        </div>
                    </div>
                </div>
                <Button variant="outline" class="shrink-0" @click="goBack">
                    <ChevronLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </header>

            <!-- Sección de períodos -->
            <div class="flex items-center gap-2 overflow-x-auto py-2">
                <Label class="flex items-center gap-2 text-sm whitespace-nowrap">
                    <Calendar class="h-4 w-4" />
                    Bimestre
                </Label>
                <Button
                    :id="`periodo-btn-${p.idPeriodo}`"
                    size="lg"
                    variant="default"
                    :class="{
                        'bg-primary dark:bg-primary text-white dark:text-white': periodoId === String(p.idPeriodo),
                        'border-gray-300 bg-white text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100':
                            periodoId !== String(p.idPeriodo),
                    }"
                    v-for="p in periodos"
                    :key="p.idPeriodo"
                    @click="
                        periodoId = String(p.idPeriodo);
                        applyPeriodo();
                    "
                >
                    <div class="flex flex-col items-start">
                        <span class="text-sm font-medium">{{ p.nombre }}</span>
                        <span class="text-xs opacity-90">{{ p.codigo }}</span>
                    </div>
                </Button>
            </div>

            <div class="mb-4 grid gap-3">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <Input v-model="searchQuery" placeholder="Buscar estudiantes..." @keydown.enter.prevent="buscar()" />
                    </div>
                    <Button variant="outline" @click="buscar()">Buscar</Button>
                </div>
                <div v-if="!isPeriodoActivo" class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    Solo se pueden atribuir puntos en el período activo. Cambia al período activo para habilitar la acción.
                </div>
            </div>

            <Tabs default-value="estudiantes">
                <TabsList class="grid w-full grid-cols-3 bg-gray-100 dark:bg-gray-800">
                    <TabsTrigger
                        value="estudiantes"
                        class="dark:data-[state=active]:bg-primary text-gray-700 data-[state=active]:bg-white data-[state=active]:text-gray-900 dark:text-gray-300 dark:data-[state=active]:text-gray-100"
                    >
                        <Users class="mr-2 h-4 w-4" />
                        Estudiantes
                    </TabsTrigger>
                    <TabsTrigger
                        value="materias"
                        class="dark:data-[state=active]:bg-primary text-gray-700 data-[state=active]:bg-white data-[state=active]:text-gray-900 dark:text-gray-300 dark:data-[state=active]:text-gray-100"
                    >
                        <BookOpen class="mr-2 h-4 w-4" />
                        Materias
                    </TabsTrigger>
                    <TabsTrigger
                        value="reportes"
                        class="dark:data-[state=active]:bg-primary text-gray-700 data-[state=active]:bg-white data-[state=active]:text-gray-900 dark:text-gray-300 dark:data-[state=active]:text-gray-100"
                    >
                        <BarChart3 class="mr-2 h-4 w-4" />
                        Reportes
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="estudiantes">
                    <Card>
                        <CardHeader>
                            <CardTitle>Listado de estudiantes</CardTitle>
                            <CardDescription>
                                Filtrado por período académico. Al atribuir, se usarán los puntos totales del estudiante en el período seleccionado.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="mb-4 space-y-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Label class="text-sm">Materia</Label>
                                    <select
                                        v-model="materiaId"
                                        class="dark:focus:ring-primary-600 rounded-md border border-gray-300 bg-white px-2 py-1 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-offset-gray-900"
                                    >
                                        <option value="" disabled>Selecciona una materia</option>
                                        <option v-for="m in materiasDisponibles" :key="m.idMateria" :value="String(m.idMateria)">
                                            {{ m.nombre }}
                                        </option>
                                    </select>
                                    <Label class="ml-4 text-sm">Comentario</Label>
                                    <Input v-model="comentario" placeholder="Opcional" class="max-w-sm" />
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="selectAllFiltered" @change="handleSelectAllFiltered" />
                                        <Label class="text-white-700 text-sm font-medium">
                                            🌐 Seleccionar TODOS los estudiantes (todas las páginas)
                                        </Label>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="lg"
                                            :disabled="!materiaId || isExporting"
                                            @click="exportarExcel"
                                            class="border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100"
                                        >
                                            <FileSpreadsheet class="mr-2 h-4 w-4" />
                                            {{ isExporting ? 'Generando Excel...' : 'Exportar Excel' }}
                                        </Button>

                                        <Button
                                            :disabled="!materiaId || !isPeriodoActivo || (selectedIds.size === 0 && !selectAllFiltered)"
                                            @click="abrirConfirmacion"
                                        >
                                            Atribuir puntos del período
                                            <span
                                                v-if="selectedIds.size > 0 || selectAllFiltered"
                                                class="ml-2 rounded-full bg-blue-100 px-2 py-1 text-xs"
                                            >
                                                {{ totalSeleccionados }}
                                            </span>
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <!-- Modificar el ScrollArea y la tabla para mejor visualización -->
                            <ScrollArea class="h-[480px] rounded-md border">
                                <table class="w-full">
                                    <thead>
                                        <tr class="sticky top-0 z-10 border-b bg-gray-50 dark:bg-gray-800">
                                            <th class="w-10 px-3 py-3">
                                                <div class="flex items-center justify-center">
                                                    <input
                                                        type="checkbox"
                                                        v-model="selectAllOnPage"
                                                        @change="toggleAllOnPage"
                                                        :disabled="selectAllFiltered"
                                                        class="h-4 w-4 rounded border-gray-300"
                                                    />
                                                </div>
                                            </th>
                                            <th class="w-12 px-3 py-3 text-center text-sm font-medium text-gray-500">#</th>
                                            <th class="px-3 py-3 text-left text-sm font-medium text-gray-500">Estudiante</th>
                                            <th class="w-24 px-3 py-3 text-center text-sm font-medium text-gray-500">Estado</th>
                                            <th class="w-24 px-3 py-3 text-center text-sm font-medium text-gray-500">Puntos</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr v-for="(e, idx) in estudiantes.data" :key="e.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                            <td class="px-3 py-2">
                                                <div class="flex items-center justify-center">
                                                    <input
                                                        type="checkbox"
                                                        :disabled="e.puntaje === 0 || (!!materiaId && atribuidosSet.has(e.id)) || selectAllFiltered"
                                                        :checked="
                                                            selectedIds.has(e.id) ||
                                                            (selectAllFiltered && e.puntaje > 0 && !(materiaId && atribuidosSet.has(e.id)))
                                                        "
                                                        @change="toggleOne(e.id, ($event.target as HTMLInputElement).checked)"
                                                        class="h-4 w-4 rounded border-gray-300"
                                                    />
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-center text-sm text-gray-500">
                                                {{ (estudiantes.current_page - 1) * estudiantes.per_page + idx + 1 }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="flex flex-col justify-center">
                                                    <p class="truncate font-medium text-gray-900">{{ e.apellidos }}</p>
                                                    <p class="truncate text-sm text-gray-500">{{ e.nombres }}</p>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <span
                                                    v-if="materiaId && atribuidosSet.has(e.id)"
                                                    class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700"
                                                >
                                                    Atribuido
                                                </span>
                                                <span
                                                    v-else-if="e.puntaje === 0"
                                                    class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600"
                                                >
                                                    Sin puntos
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <div class="flex flex-col items-center justify-center">
                                                    <span class="text-base font-semibold text-gray-900">{{ e.puntaje }}</span>
                                                    <span class="text-xs text-gray-500">puntos</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </ScrollArea>

                            <div v-if="estudiantes.total > estudiantes.per_page" class="mt-3 flex justify-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="estudiantes.current_page === 1"
                                    @click="buscar(estudiantes.current_page - 1)"
                                    >Anterior</Button
                                >
                                <Button
                                    v-for="page in estudiantes.last_page"
                                    :key="page"
                                    variant="outline"
                                    size="sm"
                                    :class="{ 'bg-primary text-white': page === estudiantes.current_page }"
                                    @click="buscar(page)"
                                    >{{ page }}</Button
                                >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="estudiantes.current_page === estudiantes.last_page"
                                    @click="buscar(estudiantes.current_page + 1)"
                                    >Siguiente</Button
                                >
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="materias">
                    <Card>
                        <CardHeader>
                            <CardTitle>Materias del curso</CardTitle>
                            <CardDescription>Selecciona las materias en las que puedes asignar puntos</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                                <div class="flex items-center gap-2 text-sm font-medium text-blue-800 dark:text-blue-200">
                                    <Calendar class="h-4 w-4" />
                                    <span
                                        >Configurando materias para: {{ currentPeriodo?.nombre || 'Período actual' }} ({{
                                            currentPeriodo?.codigo || 'N/A'
                                        }})</span
                                    >
                                </div>
                                <p class="mt-1 text-xs text-blue-600 dark:text-blue-300">
                                    Cada período puede tener diferentes materias activas. Cambia de período para configurar otras materias.
                                </p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div
                                    v-for="m in cursoData.materias"
                                    :key="m.idMateria"
                                    class="rounded-md border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <div class="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            :id="`materia-${m.idMateria}`"
                                            :checked="materiasSeleccionadasActuales.has(m.idMateria)"
                                            @change="toggleMateria(m.idMateria)"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <label
                                            :for="`materia-${m.idMateria}`"
                                            class="flex-1 cursor-pointer font-medium text-gray-900 dark:text-gray-100"
                                        >
                                            {{ m.nombre }}
                                        </label>
                                        <span
                                            v-if="materiasSeleccionadasActuales.has(m.idMateria)"
                                            class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-800 dark:bg-green-900 dark:text-green-200"
                                        >
                                            Activa
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="reportes">
                    <Card>
                        <CardHeader>
                            <CardTitle>Reportes y Exportaciones</CardTitle>
                            <CardDescription>Genera reportes detallados en Excel para análisis y seguimiento</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-6">
                                <!-- Reporte por Materia -->
                                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="space-y-2">
                                            <h3 class="flex items-center gap-2 font-semibold text-blue-900">
                                                <BarChart3 class="h-5 w-5" />
                                                Reporte de Estudiantes por Materia
                                            </h3>
                                            <p class="text-sm text-blue-700">
                                                Exporta un reporte detallado con todos los estudiantes que tienen puntos atribuidos en una materia
                                                específica.
                                            </p>
                                            <div class="flex items-center gap-2 text-xs text-blue-600">
                                                <span>✓ Información completa del estudiante</span>
                                                <span>✓ Puntos atribuidos y registros</span>
                                                <span>✓ Estadísticas y calificaciones</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <select
                                                v-model="materiaId"
                                                class="rounded-md border border-blue-300 bg-white px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none dark:border-blue-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-900"
                                            >
                                                <option value="" disabled>Selecciona materia</option>
                                                <option v-for="m in materiasDisponibles" :key="m.idMateria" :value="String(m.idMateria)">
                                                    {{ m.nombre }}
                                                </option>
                                            </select>
                                            <button
                                                @click="exportarExcel"
                                                :disabled="exportandoExcel"
                                                class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                <svg
                                                    v-if="!exportandoExcel"
                                                    class="mr-2 h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                    ></path>
                                                </svg>
                                                <svg v-else class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path
                                                        class="opacity-75"
                                                        fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                    ></path>
                                                </svg>
                                                {{ exportandoExcel ? 'Generando...' : 'Generar Excel' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información del Período -->
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <div class="space-y-2">
                                        <h3 class="font-semibold text-amber-900">📅 Información del Período</h3>
                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span class="font-medium text-amber-800">Período Seleccionado:</span>
                                                <p class="text-amber-700">
                                                    {{
                                                        currentPeriodo ? `${currentPeriodo.nombre} (${currentPeriodo.codigo})` : 'Todos los períodos'
                                                    }}
                                                </p>
                                            </div>
                                            <div>
                                                <span class="font-medium text-amber-800">Total de Estudiantes:</span>
                                                <p class="text-amber-700">{{ estadisticasReporte.total_estudiantes }} estudiantes</p>
                                            </div>
                                            <div>
                                                <span class="font-medium text-amber-800">Estudiantes con Puntos Asignados:</span>
                                                <p class="text-amber-700">{{ estadisticasReporte.estudiantes_con_puntos }} estudiantes</p>
                                            </div>
                                            <div>
                                                <span class="font-medium text-amber-800">Puntos Asignados Total:</span>
                                                <p class="text-amber-700">{{ estadisticasReporte.puntos_asignados_total }} puntos</p>
                                            </div>
                                            <div>
                                                <span class="font-medium text-amber-800">Promedio de Puntos Asignados:</span>
                                                <p class="text-amber-700">{{ estadisticasReporte.promedio_asignados }} puntos</p>
                                            </div>
                                            <div>
                                                <span class="font-medium text-amber-800">Materias Disponibles:</span>
                                                <p class="text-amber-700">{{ cursoData.materias.length }} materias</p>
                                            </div>
                                            <div>
                                                <span class="font-medium text-amber-800">Fecha de Consulta:</span>
                                                <p class="text-amber-700">{{ new Date().toLocaleDateString('es-ES') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Instrucciones -->
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <h3 class="mb-3 flex items-center gap-2 font-semibold text-gray-900">
                                        <FileText class="text-primary h-5 w-5" />
                                        Cómo usar los reportes
                                    </h3>
                                    <div class="space-y-2 text-sm text-gray-700">
                                        <div class="flex items-start gap-2">
                                            <span class="font-medium text-emerald-600">1.</span>
                                            <span>Selecciona el período académico deseado usando los botones de período arriba.</span>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="font-medium text-emerald-600">2.</span>
                                            <span>Elige la materia para la cual quieres generar el reporte.</span>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="font-medium text-emerald-600">3.</span>
                                            <span>Haz clic en "Generar Excel" para descargar el reporte completo.</span>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="font-medium text-emerald-600">4.</span>
                                            <span>El archivo Excel incluirá formato profesional, estadísticas y gráficos.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>

        <!-- Diálogos -->
        <!-- Confirmación -->
        <Dialog v-model:open="confirmOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Confirmar atribución</DialogTitle>
                    <DialogDescription> Revisa los datos antes de continuar. </DialogDescription>
                </DialogHeader>
                <div class="space-y-1 text-sm">
                    <div><span class="text-muted-foreground">Curso:</span> {{ confirmInfo.curso }}</div>
                    <div><span class="text-muted-foreground">Materia:</span> {{ confirmInfo.materia }}</div>
                    <div><span class="text-muted-foreground">Período:</span> {{ confirmInfo.periodo }}</div>
                    <div><span class="text-muted-foreground">Estudiantes:</span> {{ confirmInfo.cantidad }}</div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="confirmOpen = false">Cancelar</Button>
                    <Button
                        @click="
                            confirmOpen = false;
                            asignar();
                        "
                        >Confirmar</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Éxito -->
        <Dialog v-model:open="successOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Atribución realizada</DialogTitle>
                </DialogHeader>
                <div class="space-y-1 text-sm">
                    <div><span class="text-muted-foreground">Curso:</span> {{ successInfo.curso }}</div>
                    <div><span class="text-muted-foreground">Materia:</span> {{ successInfo.materia }}</div>
                    <div><span class="text-muted-foreground">Período:</span> {{ successInfo.periodo }}</div>
                    <div><span class="text-muted-foreground">Estudiantes seleccionados:</span> {{ successInfo.cantidad }}</div>
                    <div v-if="successInfo.estudiantes_afectados !== undefined">
                        <span class="text-muted-foreground">Estudiantes afectados:</span> {{ successInfo.estudiantes_afectados }}
                    </div>
                    <div v-if="successInfo.insertados !== undefined">
                        <span class="text-muted-foreground">Registros insertados:</span> {{ successInfo.insertados }}
                    </div>
                    <div v-if="successInfo.omitidos !== undefined">
                        <span class="text-muted-foreground">Registros omitidos (ya existentes):</span> {{ successInfo.omitidos }}
                    </div>
                </div>
                <DialogFooter>
                    <Button @click="successOpen = false">Cerrar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
