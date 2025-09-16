<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Award, ChevronLeft, Download, FileSpreadsheet, Medal, Trophy, Users } from 'lucide-vue-next';
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
    curso: CursoInfo;
    estudiantes: {
        data: Estudiante[];
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
    periodos: Array<{
        idPeriodo: number;
        nombre: string;
        codigo: string;
    }>;
    periodoSeleccionado?: number | null;
    periodoActivoId?: number | null;
}

const props = defineProps<Props>();

const searchQuery = ref('');
const periodoId = ref<string>(
    props.periodoSeleccionado ? String(props.periodoSeleccionado) : 
    props.periodoActivoId ? String(props.periodoActivoId) : ''
);
const materiaSeleccionada = ref<string>('');
const exportandoExcel = ref(false);

const currentPeriodo = computed(() => 
    props.periodos.find((p) => String(p.idPeriodo) === periodoId.value)
);

const estudiantesConPosicion = computed(() => {
    return props.estudiantes.data.map((estudiante, index) => ({
        ...estudiante,
        posicion: (props.estudiantes.current_page - 1) * props.estudiantes.per_page + index + 1
    }));
});

const estadisticas = computed(() => {
    const estudiantes = props.estudiantes.data;
    const totalEstudiantes = estudiantes.length;
    const estudiantesConPuntos = estudiantes.filter(e => e.puntaje > 0).length;
    const puntajeTotal = estudiantes.reduce((sum, e) => sum + (e.puntaje || 0), 0);
    const puntajePromedio = totalEstudiantes > 0 ? (puntajeTotal / totalEstudiantes) : 0;
    const puntajeMaximo = Math.max(...estudiantes.map(e => e.puntaje || 0), 0);
    
    return {
        totalEstudiantes,
        estudiantesConPuntos,
        puntajeTotal,
        puntajePromedio: Math.round(puntajePromedio * 100) / 100,
        puntajeMaximo
    };
});

function goBack() {
    router.visit(route('docente.dashboard'));
}

function applyFilters() {
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
        
        const url = route('docente.curso.exportar-ranking', props.curso.idCursoParalelo) + 
                   (params.toString() ? `?${params.toString()}` : '');

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
        case 1: return Trophy;
        case 2: return Medal;
        case 3: return Award;
        default: return null;
    }
}

function getMedalColor(posicion: number) {
    switch (posicion) {
        case 1: return 'text-yellow-500';
        case 2: return 'text-gray-400';
        case 3: return 'text-orange-500';
        default: return '';
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
        <Head :title="`Ranking - ${props.curso.curso.nombre} ${props.curso.curso.paralelo}`" />
        
        <div class="mx-auto w-full max-w-6xl p-4 sm:p-6">
            <header class="mb-6 flex items-center justify-between">
                <div class="min-w-0">
                    <h1 class="truncate text-2xl font-bold sm:text-3xl">
                        🏆 Ranking de Estudiantes
                    </h1>
                    <p class="text-muted-foreground text-sm">
                        {{ props.curso.curso.nombre }} "{{ props.curso.curso.paralelo }}" · 
                        <span v-if="currentPeriodo">{{ currentPeriodo.nombre }} ({{ currentPeriodo.codigo }})</span>
                        <span v-else>Todos los períodos</span>
                    </p>
                </div>
                <Button variant="outline" class="shrink-0" @click="goBack">
                    <ChevronLeft class="mr-2 h-4 w-4" /> 
                    Volver
                </Button>
            </header>

            <!-- Filtros -->
            <div class="mb-6 grid gap-4">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <Input 
                            v-model="searchQuery" 
                            placeholder="Buscar estudiantes por nombre o apellido..." 
                            @keydown.enter.prevent="buscar()" 
                        />
                    </div>
                    <Button variant="outline" @click="buscar()">Buscar</Button>
                </div>
                
                <div class="flex items-center gap-2 overflow-x-auto py-2">
                    <Label class="text-sm whitespace-nowrap">Período:</Label>
                    <Button
                        v-for="p in props.periodos"
                        :key="p.idPeriodo"
                        :id="`periodo-btn-${p.idPeriodo}`"
                        size="sm"
                        variant="outline"
                        :class="{ 
                            'bg-primary text-white dark:bg-primary dark:text-white': periodoId === String(p.idPeriodo),
                            'bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100 border-gray-300 dark:border-gray-600': periodoId !== String(p.idPeriodo)
                        }"
                        @click="periodoId = String(p.idPeriodo); applyFilters();"
                    >
                        {{ p.nombre }} ({{ p.codigo }})
                    </Button>
                </div>
            </div>

            <Tabs default-value="ranking">
                <TabsList class="grid w-full grid-cols-2 bg-gray-100 dark:bg-gray-800">
                    <TabsTrigger value="ranking" class="data-[state=active]:bg-white data-[state=active]:text-gray-900 dark:data-[state=active]:bg-primary dark:data-[state=active]:text-gray-100 text-gray-700 dark:text-gray-300">
                        🏆 Ranking
                    </TabsTrigger>
                    <TabsTrigger value="estadisticas" class="data-[state=active]:bg-white data-[state=active]:text-gray-900 dark:data-[state=active]:bg-primary dark:data-[state=active]:text-gray-100 text-gray-700 dark:text-gray-300">
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
                                    <CardDescription>
                                        Clasificación por puntaje total en el período seleccionado
                                    </CardDescription>
                                </div>
                                <div class="flex gap-2">
                                    <select 
                                        v-model="materiaSeleccionada"
                                        class="rounded-md border px-3 py-2 text-sm bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                                    >
                                        <option value="">Todas las materias</option>
                                        <option v-for="m in props.curso.materias" :key="m.idMateria" :value="String(m.idMateria)">
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
                            <ScrollArea class="h-[600px]">
                                <div class="space-y-2">
                                    <div 
                                        v-for="estudiante in estudiantesConPosicion" 
                                        :key="estudiante.id"
                                        :class="[
                                            'flex items-center justify-between p-4 rounded-lg border transition-all duration-200',
                                            estudiante.posicion <= 3 
                                                ? 'bg-gradient-to-r from-yellow-50 to-orange-50 border-yellow-200 dark:from-yellow-950/20 dark:to-orange-950/20 dark:border-yellow-800' 
                                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750'
                                        ]"
                                    >
                                        <div class="flex items-center gap-4 min-w-0 flex-1">
                                            <!-- Posición y medalla -->
                                            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white font-bold text-lg shadow-md">
                                                <component 
                                                    v-if="getMedalIcon(estudiante.posicion)" 
                                                    :is="getMedalIcon(estudiante.posicion)" 
                                                    :class="['h-6 w-6', getMedalColor(estudiante.posicion)]"
                                                />
                                                <span v-else>{{ estudiante.posicion }}</span>
                                            </div>
                                            
                                            <!-- Información del estudiante -->
                                            <div class="min-w-0 flex-1">
                                                <div class="font-semibold text-lg text-gray-900 dark:text-gray-100 truncate">
                                                    {{ estudiante.apellidos }}, {{ estudiante.nombres }}
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                                    {{ estudiante.email }}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Puntaje -->
                                        <div class="text-right">
                                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                                {{ estudiante.puntaje }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                puntos
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </ScrollArea>

                            <!-- Paginación -->
                            <div v-if="props.estudiantes.total > props.estudiantes.per_page" class="mt-6 flex justify-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="props.estudiantes.current_page === 1"
                                    @click="buscar(props.estudiantes.current_page - 1)"
                                >
                                    Anterior
                                </Button>
                                <Button
                                    v-for="page in props.estudiantes.last_page"
                                    :key="page"
                                    variant="outline"
                                    size="sm"
                                    :class="{ 'bg-primary text-white': page === props.estudiantes.current_page }"
                                    @click="buscar(page)"
                                >
                                    {{ page }}
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="props.estudiantes.current_page === props.estudiantes.last_page"
                                    @click="buscar(props.estudiantes.current_page + 1)"
                                >
                                    Siguiente
                                </Button>
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
                                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-950/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                            {{ estadisticas.totalEstudiantes }}
                                        </div>
                                        <div class="text-sm text-blue-700 dark:text-blue-300 font-medium">
                                            Total Estudiantes
                                        </div>
                                    </div>
                                    <div class="text-center p-4 bg-green-50 dark:bg-green-950/20 rounded-lg border border-green-200 dark:border-green-800">
                                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                            {{ estadisticas.estudiantesConPuntos }}
                                        </div>
                                        <div class="text-sm text-green-700 dark:text-green-300 font-medium">
                                            Con Puntos
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Puntaje Total:</span>
                                        <span class="font-bold text-lg text-gray-900 dark:text-gray-100">{{ estadisticas.puntajeTotal }}</span>
                                    </div>
                                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Promedio:</span>
                                        <span class="font-bold text-lg text-gray-900 dark:text-gray-100">{{ estadisticas.puntajePromedio }}</span>
                                    </div>
                                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Puntaje Máximo:</span>
                                        <span class="font-bold text-lg text-gray-900 dark:text-gray-100">{{ estadisticas.puntajeMaximo }}</span>
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
                                            v-for="(estudiante, index) in props.estudiantes.data.slice(0, 10)" 
                                            :key="estudiante.id"
                                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                                        >
                                            <div class="flex items-center gap-3">
                                                <div 
                                                    :class="[
                                                        'flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold text-white',
                                                        index === 0 ? 'bg-yellow-500' : 
                                                        index === 1 ? 'bg-gray-400' : 
                                                        index === 2 ? 'bg-orange-500' : 'bg-blue-500'
                                                    ]"
                                                >
                                                    {{ index + 1 }}
                                                </div>
                                                <span class="font-medium text-gray-900 dark:text-gray-100 truncate">
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
