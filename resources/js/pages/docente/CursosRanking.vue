<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Award, ChevronLeft, FileSpreadsheet, Medal, Trophy, Users } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Estudiante {
    id: number;
    nombres: string;
    apellidos: string;
    email: string;
    puntaje: number;
    posicion?: number;
}

interface Materia {
    idMateria: number;
    nombre: string;
}

interface CursoInfo {
    idCursoParalelo: string;
    curso: {
        nombre: string;
        paralelo: string;
    };
    materias: Materia[];
}

interface Props {
    curso?: CursoInfo;
    estudiantes?: {
        data: Estudiante[];
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
    periodos?: Array<{
        idPeriodo: number;
        nombre: string;
        codigo: string;
    }>;
    periodoSeleccionado?: number | null;
    periodoActivoId?: number | null;
    ranking?: Array<{
        idCursoParalelo: string;
        nombreCurso: string;
        puntajeTotal: number;
    }>;
    periodoActivo?: {
        idPeriodo: number;
        nombre: string;
        codigo: string;
    };
    asignacionesPorMateria?: Array<{
        idMateria: number;
        nombreMateria: string;
        fecha: string;
        puntos: number;
        comentario?: string;
        docente: {
            nombres: string;
            apellidos: string;
        };
    }>;
}

const props = defineProps<Props>();

const searchQuery = ref('');
const periodoId = ref<string>(
    props.periodoSeleccionado ? String(props.periodoSeleccionado) : props.periodoActivoId ? String(props.periodoActivoId) : '',
);
const materiaSeleccionada = ref<string>('');
const exportandoExcel = ref(false);

const currentPeriodo = computed(() => props.periodos?.find((p) => String(p.idPeriodo) === periodoId.value));

const estudiantesConPosicion = computed(() => {
    if (!props.estudiantes?.data) return [];
    const estudiantes = props.estudiantes;
    return estudiantes.data.map((estudiante, index) => ({
        ...estudiante,
        posicion: (estudiantes.current_page - 1) * estudiantes.per_page + index + 1,
    }));
});

const estadisticas = computed(() => {
    const estudiantes = props.estudiantes?.data || [];
    const totalEstudiantes = estudiantes.length;
    const estudiantesConPuntos = estudiantes.filter((e) => e.puntaje > 0).length;
    const puntajeTotal = estudiantes.reduce((sum, e) => sum + (e.puntaje || 0), 0);
    const puntajePromedio = totalEstudiantes > 0 ? puntajeTotal / totalEstudiantes : 0;
    const puntajeMaximo = Math.max(...estudiantes.map((e) => e.puntaje || 0), 0);

    return {
        totalEstudiantes,
        estudiantesConPuntos,
        puntajeTotal,
        puntajePromedio: Math.round(puntajePromedio * 100) / 100,
        puntajeMaximo,
    };
});

function goBack() {
    router.visit(route('docente.dashboard'));
}

function applyFilters() {
    if (!props.curso?.idCursoParalelo) return;
    const params: Record<string, string> = {};
    if (periodoId.value !== '') params['periodo_id'] = periodoId.value;
    if (searchQuery.value) params['search'] = searchQuery.value;

    router.visit(route('docente.curso.ranking', props.curso.idCursoParalelo), {
        method: 'get',
        data: params,
        only: ['estudiantes', 'periodoSeleccionado'],
        preserveScroll: true,
        preserveState: true,
    });
}

function buscar(page?: number) {
    if (!props.curso?.idCursoParalelo) return;
    const params: Record<string, string> = {};
    if (periodoId.value !== '') params['periodo_id'] = periodoId.value;
    if (searchQuery.value) params['search'] = searchQuery.value;
    if (page) params['page'] = String(page);

    router.visit(route('docente.curso.ranking', props.curso.idCursoParalelo), {
        method: 'get',
        data: params,
        only: ['estudiantes'],
        preserveScroll: true,
        preserveState: true,
    });
}

async function exportarRanking() {
    exportandoExcel.value = true;
    try {
        const params = new URLSearchParams();
        if (periodoId.value) params.append('periodo_id', periodoId.value);
        if (materiaSeleccionada.value) params.append('materia_id', materiaSeleccionada.value);

        if (!props.curso?.idCursoParalelo) return;
        const url = route('docente.curso.exportar-ranking', props.curso.idCursoParalelo) + (params.toString() ? `?${params.toString()}` : '');

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

function getMedalIcon(posicion: number) {
    switch (posicion) {
        case 1:
            return Trophy;
        case 2:
            return Medal;
        case 3:
            return Award;
        default:
            return null;
    }
}

function getMedalColor(posicion: number) {
    switch (posicion) {
        case 1:
            return 'text-yellow-500';
        case 2:
            return 'text-gray-400';
        case 3:
            return 'text-orange-500';
        default:
            return '';
    }
}

onMounted(() => {
    if (periodoId.value) {
        const activeButton = document.getElementById(`periodo-btn-${periodoId.value}`);
        if (activeButton) {
            activeButton.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }
});
</script>

<template>
    <AppLayout>
        <Head :title="props.curso ? `Ranking - ${props.curso.curso?.nombre || 'Curso'} ${props.curso.curso?.paralelo || ''}` : 'Ranking de Cursos'" />

        <div class="mx-auto w-full max-w-6xl p-4 sm:p-6">
            <header class="mb-6 flex items-center justify-between">
                <div class="min-w-0">
                    <h1 class="truncate text-2xl font-bold sm:text-3xl">🏆 {{ props.curso ? 'Ranking de Estudiantes' : 'Ranking de Cursos' }}</h1>
                    <p class="text-muted-foreground text-sm" v-if="props.curso">
                        {{ props.curso.curso?.nombre || 'Curso' }} "{{ props.curso.curso?.paralelo || '' }}" ·
                        <span v-if="currentPeriodo">{{ currentPeriodo.nombre }} ({{ currentPeriodo.codigo }})</span>
                        <span v-else>Todos los períodos</span>
                    </p>
                    <p class="text-muted-foreground text-sm" v-else>
                        Comparación de puntajes totales por curso
                        <span v-if="props.periodoActivo"> · {{ props.periodoActivo.nombre }}</span>
                    </p>
                </div>
                <Button variant="outline" class="shrink-0" @click="goBack">
                    <ChevronLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </header>

            <!-- Filtros - Solo para ranking de estudiantes -->
            <div v-if="props.curso" class="mb-6 grid gap-4">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <Input v-model="searchQuery" placeholder="Buscar estudiantes por nombre o apellido..." @keydown.enter.prevent="buscar()" />
                    </div>
                    <Button variant="outline" @click="buscar()">Buscar</Button>
                </div>

                <div class="flex items-center gap-2 overflow-x-auto py-2">
                    <Label class="text-sm whitespace-nowrap">Período:</Label>
                    <Button
                        v-for="p in props.periodos || []"
                        :key="p.idPeriodo"
                        :id="`periodo-btn-${p.idPeriodo}`"
                        size="sm"
                        variant="outline"
                        :class="{
                            'bg-primary dark:bg-primary text-white dark:text-white': periodoId === String(p.idPeriodo),
                            'border-gray-300 bg-white text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100':
                                periodoId !== String(p.idPeriodo),
                        }"
                        @click="
                            periodoId = String(p.idPeriodo);
                            applyFilters();
                        "
                    >
                        {{ p.nombre }} ({{ p.codigo }})
                    </Button>
                </div>
            </div>

            <!-- Ranking de Cursos -->
            <div v-if="props.ranking && !props.curso" class="mb-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Trophy class="h-5 w-5 text-yellow-500" />
                            Ranking de Cursos
                        </CardTitle>
                        <CardDescription> Clasificación de cursos por puntaje total acumulado </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="(curso, index) in props.ranking"
                                :key="curso.idCursoParalelo"
                                :class="[
                                    'flex items-center justify-between rounded-lg border p-4 transition-all duration-200',
                                    index < 3
                                        ? 'border-yellow-200 bg-gradient-to-r from-yellow-50 to-orange-50 dark:border-yellow-800 dark:from-yellow-950/20 dark:to-orange-950/20'
                                        : 'dark:hover:bg-gray-750 border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800',
                                ]"
                            >
                                <div class="flex items-center gap-4">
                                    <!-- Posición -->
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-lg font-bold text-white shadow-md"
                                    >
                                        <component
                                            v-if="getMedalIcon(index + 1)"
                                            :is="getMedalIcon(index + 1)"
                                            :class="['h-6 w-6', getMedalColor(index + 1)]"
                                        />
                                        <span v-else>{{ index + 1 }}</span>
                                    </div>

                                    <!-- Nombre del curso -->
                                    <div>
                                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                            {{ curso.nombreCurso }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Puntaje -->
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                        {{ curso.puntajeTotal }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">puntos totales</div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Ranking de Estudiantes -->
            <Tabs v-if="props.curso" default-value="ranking">
                <TabsList class="grid w-full grid-cols-2 bg-gray-100 dark:bg-gray-800">
                    <TabsTrigger
                        value="ranking"
                        class="dark:data-[state=active]:bg-primary text-gray-700 data-[state=active]:bg-white data-[state=active]:text-gray-900 dark:text-gray-300 dark:data-[state=active]:text-gray-100"
                    >
                        🏆 Ranking
                    </TabsTrigger>
                    <TabsTrigger
                        value="estadisticas"
                        class="dark:data-[state=active]:bg-primary text-gray-700 data-[state=active]:bg-white data-[state=active]:text-gray-900 dark:text-gray-300 dark:data-[state=active]:text-gray-100"
                    >
                        📊 Estadísticas
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="ranking">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="flex items-center gap-2">
                                        <Trophy class="h-5 w-5 text-yellow-500" />
                                        Ranking de Estudiantes
                                    </CardTitle>
                                    <CardDescription> Clasificación por puntaje total en el período seleccionado </CardDescription>
                                </div>
                                <div class="flex gap-2">
                                    <select
                                        v-model="materiaSeleccionada"
                                        class="dark:focus:ring-primary-600 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-offset-gray-900"
                                    >
                                        <option value="">Todas las materias</option>
                                        <option v-for="m in props.curso?.materias || []" :key="m.idMateria" :value="String(m.idMateria)">
                                            {{ m.nombre }}
                                        </option>
                                    </select>
                                    <Button
                                        variant="outline"
                                        @click="exportarRanking"
                                        :disabled="exportandoExcel"
                                        class="border-green-200 bg-green-50 text-green-700 hover:border-green-300 hover:bg-green-100 dark:border-green-800 dark:bg-green-950/20 dark:text-green-400"
                                    >
                                        <FileSpreadsheet class="mr-2 h-4 w-4" />
                                        {{ exportandoExcel ? 'Exportando...' : 'Exportar Excel' }}
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <!-- Modificar la sección de estudiantes para hacerla más horizontal -->
                            <div class="grid grid-cols-12 gap-6">
                                <!-- Lista de estudiantes (7 columnas) -->
                                <div class="col-span-7">
                                    <ScrollArea class="h-[600px]">
                                        <div class="space-y-2">
                                            <div
                                                v-for="estudiante in estudiantesConPosicion"
                                                :key="estudiante.id"
                                                class="flex items-center gap-4 rounded-lg border p-4 transition-all"
                                                :class="[
                                                    estudiante.posicion <= 3
                                                        ? 'border-yellow-200 bg-gradient-to-r from-yellow-50 to-orange-50'
                                                        : 'border-gray-200 bg-white hover:bg-gray-50',
                                                ]"
                                            >
                                                <!-- Posición y medalla -->
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white"
                                                    >
                                                        <component
                                                            v-if="getMedalIcon(estudiante.posicion)"
                                                            :is="getMedalIcon(estudiante.posicion)"
                                                            :class="['h-6 w-6', getMedalColor(estudiante.posicion)]"
                                                        />
                                                        <span v-else>{{ estudiante.posicion }}</span>
                                                    </div>
                                                </div>

                                                <!-- Info del estudiante -->
                                                <div class="min-w-0 flex-1">
                                                    <div class="text-lg font-semibold">{{ estudiante.apellidos }}, {{ estudiante.nombres }}</div>
                                                    <div class="text-sm text-gray-500">{{ estudiante.email }}</div>
                                                </div>

                                                <!-- Puntaje -->
                                                <div class="flex-shrink-0 text-right">
                                                    <div class="text-2xl font-bold text-blue-600">{{ estudiante.puntaje }}</div>
                                                    <div class="text-xs text-gray-500">puntos</div>
                                                </div>
                                            </div>
                                        </div>
                                    </ScrollArea>
                                </div>

                                <!-- Panel lateral derecho (5 columnas) -->
                                <div class="col-span-5 space-y-6">
                                    <!-- Estadísticas Rápidas -->
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Estadísticas del Curso</CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="rounded-lg bg-blue-50 p-4">
                                                    <div class="text-2xl font-bold text-blue-700">{{ estadisticas.totalEstudiantes }}</div>
                                                    <div class="text-sm text-blue-600">Total Estudiantes</div>
                                                </div>
                                                <div class="rounded-lg bg-green-50 p-4">
                                                    <div class="text-2xl font-bold text-green-700">{{ estadisticas.puntajePromedio }}</div>
                                                    <div class="text-sm text-green-600">Promedio</div>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    <!-- Nueva sección: Historial de Asignaciones -->
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Últimas Asignaciones</CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <ScrollArea class="h-[400px]">
                                                <div class="space-y-3">
                                                    <div
                                                        v-for="asignacion in asignacionesPorMateria"
                                                        :key="asignacion.idMateria"
                                                        class="rounded-lg border bg-gray-50 p-3"
                                                    >
                                                        <div class="flex items-start justify-between">
                                                            <div>
                                                                <h4 class="font-medium text-gray-900">{{ asignacion.nombreMateria }}</h4>
                                                                <p class="text-sm text-gray-500">
                                                                    {{ new Date(asignacion.fecha).toLocaleDateString() }}
                                                                    por {{ asignacion.docente.nombres }} {{ asignacion.docente.apellidos }}
                                                                </p>
                                                                <p v-if="asignacion.comentario" class="mt-1 text-sm text-gray-600">
                                                                    "{{ asignacion.comentario }}"
                                                                </p>
                                                            </div>
                                                            <div class="text-right">
                                                                <span class="text-lg font-bold text-green-600">+{{ asignacion.puntos }}</span>
                                                                <div class="text-xs text-gray-500">puntos</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </ScrollArea>
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="estadisticas">
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Estadísticas generales -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Users class="h-5 w-5 text-blue-500" />
                                    Estadísticas Generales
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div
                                        class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-center dark:border-blue-800 dark:bg-blue-950/20"
                                    >
                                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                            {{ estadisticas.totalEstudiantes }}
                                        </div>
                                        <div class="text-sm font-medium text-blue-700 dark:text-blue-300">Total Estudiantes</div>
                                    </div>
                                    <div
                                        class="rounded-lg border border-green-200 bg-green-50 p-4 text-center dark:border-green-800 dark:bg-green-950/20"
                                    >
                                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                            {{ estadisticas.estudiantesConPuntos }}
                                        </div>
                                        <div class="text-sm font-medium text-green-700 dark:text-green-300">Con Puntos</div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Puntaje Total:</span>
                                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ estadisticas.puntajeTotal }}</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Promedio:</span>
                                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ estadisticas.puntajePromedio }}</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Puntaje Máximo:</span>
                                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ estadisticas.puntajeMaximo }}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Top 10 -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Trophy class="h-5 w-5 text-yellow-500" />
                                    Top 10 Estudiantes
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ScrollArea class="h-80">
                                    <div class="space-y-2">
                                        <div
                                            v-for="(estudiante, index) in (props.estudiantes?.data || []).slice(0, 10)"
                                            :key="estudiante.id"
                                            class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800"
                                        >
                                            <div class="flex items-center gap-3">
                                                <div
                                                    :class="[
                                                        'flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold text-white',
                                                        index === 0
                                                            ? 'bg-yellow-500'
                                                            : index === 1
                                                              ? 'bg-gray-400'
                                                              : index === 2
                                                                ? 'bg-orange-500'
                                                                : 'bg-blue-500',
                                                    ]"
                                                >
                                                    {{ index + 1 }}
                                                </div>
                                                <span class="truncate font-medium text-gray-900 dark:text-gray-100">
                                                    {{ estudiante.apellidos }}
                                                </span>
                                            </div>
                                            <span class="font-bold text-blue-600 dark:text-blue-400">
                                                {{ estudiante.puntaje }}
                                            </span>
                                        </div>
                                    </div>
                                </ScrollArea>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
