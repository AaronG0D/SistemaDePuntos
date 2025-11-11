<template>
    <Head title="Reportes por Materia" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-8">
                <div
                    class="rounded-xl border border-slate-200 bg-gradient-to-r from-slate-50 to-gray-50 p-8 shadow-sm dark:border-slate-700 dark:from-slate-800 dark:to-gray-800"
                >
                    <h1 class="mb-2 flex items-center gap-3 text-3xl font-semibold text-slate-800 dark:text-slate-100">
                        <div class="rounded-lg bg-slate-100 p-2 dark:bg-slate-700">
                            <FileText class="h-6 w-6 text-slate-600 dark:text-slate-300" />
                        </div>
                        Reportes por Materia
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400">
                        Análisis detallado de puntos asignados por el docente {{ teacher.nombres }} {{ teacher.apellidos }}
                    </p>
                </div>
            </header>

            <!-- Filtros -->
            <Card class="mb-8 border border-slate-200 shadow-sm dark:border-slate-700">
                <CardHeader class="bg-slate-50 dark:bg-slate-800/50">
                    <CardTitle class="flex items-center text-slate-700 dark:text-slate-300">
                        <div class="mr-3 rounded-md bg-slate-100 p-2 dark:bg-slate-700">
                            <Filter class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                        </div>
                        Filtros de Reporte
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-4">
                        <div>
                            <Label>Curso-Paralelo</Label>
                            <Select v-model="selectedCourseParallel" @update:modelValue="onCourseParallelChange">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar curso-paralelo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los cursos</SelectItem>
                                    <SelectItem
                                        v-for="courseParallel in coursesParallels"
                                        :key="courseParallel.id"
                                        :value="courseParallel.id.toString()"
                                    >
                                        {{ courseParallel.nombre_completo }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Materia</Label>
                            <Select v-model="selectedSubject" :disabled="!selectedCourseParallel || selectedCourseParallel === 'all'">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar materia" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las materias</SelectItem>
                                    <SelectItem v-for="subject in availableSubjects" :key="subject.id" :value="subject.id.toString()">
                                        {{ subject.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Período</Label>
                            <Select v-model="selectedPeriod">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar período" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los períodos</SelectItem>
                                    <SelectItem v-for="period in periods" :key="period.id" :value="period.id.toString()">
                                        {{ period.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex items-end">
                            <Button @click="generateReport" class="w-full bg-slate-600 text-white hover:bg-slate-700">
                                <BarChart3 class="mr-2 h-4 w-4" />
                                Generar Reporte
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- ===== TABS PRINCIPALES ===== -->
            <Tabs default-value="stats" class="w-full">
                <TabsList class="grid w-full grid-cols-3 rounded-lg bg-slate-100 p-1 dark:bg-slate-800">
                    <TabsTrigger
                        value="stats"
                        class="data-[state=active]:bg-white data-[state=active]:text-slate-900 data-[state=active]:shadow-sm dark:data-[state=active]:bg-slate-700 dark:data-[state=active]:text-slate-100"
                    >
                        <TrendingUp class="mr-2 h-4 w-4" />
                        Estadísticas Generales
                    </TabsTrigger>
                    <TabsTrigger
                        value="report"
                        class="data-[state=active]:bg-white data-[state=active]:text-slate-900 data-[state=active]:shadow-sm dark:data-[state=active]:bg-slate-700 dark:data-[state=active]:text-slate-100"
                    >
                        <FileText class="mr-2 h-4 w-4" />
                        Reporte Detallado
                    </TabsTrigger>
                    <TabsTrigger
                        value="subjects"
                        class="data-[state=active]:bg-white data-[state=active]:text-slate-900 data-[state=active]:shadow-sm dark:data-[state=active]:bg-slate-700 dark:data-[state=active]:text-slate-100"
                    >
                        <BookOpen class="mr-2 h-4 w-4" />
                        Puntos por Materia
                    </TabsTrigger>
                </TabsList>

                <!-- ===== TAB ESTADÍSTICAS GENERALES ===== -->
                <TabsContent value="stats" class="space-y-6">
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <Card class="border border-slate-200 shadow-sm transition-shadow hover:shadow-md dark:border-slate-700">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Puntos Asignados</CardTitle>
                                <div class="rounded-md bg-slate-100 p-2 dark:bg-slate-700">
                                    <Award class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ totalPoints }}</div>
                            </CardContent>
                        </Card>
                        <Card class="border border-slate-200 shadow-sm transition-shadow hover:shadow-md dark:border-slate-700">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Asignaciones</CardTitle>
                                <div class="rounded-md bg-slate-100 p-2 dark:bg-slate-700">
                                    <Award class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ filteredAssignments.length }}</div>
                            </CardContent>
                        </Card>
                        
                        <Card class="border border-slate-200 shadow-sm transition-shadow hover:shadow-md dark:border-slate-700">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-slate-600 dark:text-slate-400">Materias Evaluadas</CardTitle>
                                <div class="rounded-md bg-slate-100 p-2 dark:bg-slate-700">
                                    <BookOpen class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ totalSubjects }}</div>
                            </CardContent>
                        </Card>
                        <Card class="border border-slate-200 shadow-sm transition-shadow hover:shadow-md dark:border-slate-700">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-slate-600 dark:text-slate-400">Promedio de Puntos</CardTitle>
                                <div class="rounded-md bg-slate-100 p-2 dark:bg-slate-700">
                                    <TrendingUp class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ averagePoints }}</div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- ===== TAB REPORTE DETALLADO ===== -->
                <TabsContent value="report" class="space-y-4">
                    <Card class="border border-slate-200 shadow-sm dark:border-slate-700">
                        <CardHeader class="flex flex-row items-center justify-between bg-slate-50 dark:bg-slate-800/50">
                            <div>
                                <CardTitle class="text-slate-800 dark:text-slate-200">Detalle de Asignaciones</CardTitle>
                                <CardDescription class="text-slate-600 dark:text-slate-400"
                                    >Lista de puntos asignados según los filtros.</CardDescription
                                >
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    @click="exportToPDF"
                                    variant="outline"
                                    class="border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                                >
                                    <Download class="mr-2 h-4 w-4" />
                                    PDF
                                </Button>
                                <Button
                                    @click="exportToExcel"
                                    variant="outline"
                                    class="border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                                >
                                    <FileSpreadsheet class="mr-2 h-4 w-4" />
                                    Excel
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Estudiante</TableHead>
                                        <TableHead>Materia</TableHead>
                                        <TableHead>Curso</TableHead>
                                        <TableHead>Período</TableHead>
                                        <TableHead class="text-right">Puntos</TableHead>
                                        <TableHead>Fecha</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="paginatedAssignments.length === 0">
                                        <TableCell colspan="6" class="text-muted-foreground py-8 text-center">
                                            No hay asignaciones para los filtros seleccionados.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-for="assignment in paginatedAssignments" :key="assignment.id">
                                        <TableCell>
                                            <div class="font-medium">{{ assignment.estudiante.apellidos }}, {{ assignment.estudiante.nombres }}</div>
                                            <div class="text-muted-foreground text-sm">{{ assignment.estudiante.codigo_estudiante }}</div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">{{ assignment.materia.nombre }}</Badge>
                                        </TableCell>
                                        <TableCell
                                            >{{ assignment.estudiante.curso?.nombre }} "{{ assignment.estudiante.paralelo?.nombre }}"</TableCell
                                        >
                                        <TableCell>{{ assignment.periodo.nombre }}</TableCell>
                                        <TableCell class="text-primary text-right font-bold">{{ assignment.puntos }}</TableCell>
                                        <TableCell>{{ formatDate(assignment.fecha_asignacion) }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <!-- Paginación -->
                            <div v-if="totalPages > 1" class="mt-6 flex items-center justify-between pt-4">
                                <div class="text-muted-foreground text-sm">
                                    Mostrando {{ startIndex + 1 }} - {{ Math.min(endIndex, filteredAssignments.length) }} de
                                    {{ filteredAssignments.length }} registros
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Button variant="outline" size="icon" @click="prevPage" :disabled="currentPage === 1">
                                        <ChevronLeft class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        v-for="page in visiblePages"
                                        :key="page"
                                        :variant="currentPage === page ? 'default' : 'outline'"
                                        size="icon"
                                        @click="goToPage(page)"
                                    >
                                        {{ page }}
                                    </Button>
                                    <Button variant="outline" size="icon" @click="nextPage" :disabled="currentPage === totalPages">
                                        <ChevronRight class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- ===== TAB PUNTOS POR MATERIA ===== -->
                <TabsContent value="subjects" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Distribución de Puntos por Materia</CardTitle>
                            <CardDescription>Total de puntos asignados a cada materia según los filtros.</CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <Card v-for="subject in subjectStats" :key="subject.id">
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-base font-medium">{{ subject.nombre }}</CardTitle>
                                    <BookOpen class="text-muted-foreground h-4 w-4" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">{{ subject.puntos }}</div>
                                    <p class="text-muted-foreground text-xs">puntos en total</p>
                                </CardContent>
                            </Card>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    Award,
    BarChart3,
    BookOpen,
    ChevronLeft,
    ChevronRight,
    Download,
    FileSpreadsheet,
    FileText,
    Filter,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import { computed, defineProps, ref } from 'vue';

interface Props {
    teacher: {
        id: number;
        nombres: string;
        apellidos: string;
    };
    coursesParallels: Array<{
        id: number;
        nombre_completo: string;
        curso_nombre: string;
        paralelo_nombre: string;
    }>;
    subjects: Array<{
        id: number;
        nombre: string;
        curso_paralelo_id?: number;
    }>;
    periods: Array<{
        id: number;
        nombre: string;
    }>;
    assignments: Array<{
        id: number;
        puntos: number;
        fecha_asignacion: string;
        comentario?: string;
        estudiante: {
            id: number;
            nombres: string;
            apellidos: string;
            codigo_estudiante?: string;
            curso: {
                nombre: string;
            };
            paralelo: {
                nombre: string;
            };
        };
        materia: {
            id: number;
            nombre: string;
        };
        periodo: {
            id: number;
            nombre: string;
        };
    }>;
    stats?: {
        total_assignments: number;
        total_points: number;
        average_points: number;
        unique_students: number;
    };
}

const props = defineProps<Props>();

// Reactive variables
const selectedCourseParallel = ref('all');
const selectedSubject = ref('all');
const selectedPeriod = ref('all');
const currentPage = ref(1);
const itemsPerPage = 10;

// Computed para filtrado en cascada
const availableSubjects = computed(() => {
    if (selectedCourseParallel.value === 'all') return props.subjects;

    const courseParallelId = parseInt(selectedCourseParallel.value);
    return props.subjects.filter((s) => s.curso_paralelo_id === courseParallelId);
});

// Functions
const onCourseParallelChange = () => {
    selectedSubject.value = 'all';
    selectedPeriod.value = 'all';
    currentPage.value = 1;
};

const generateReport = () => {
    // Filtrado reactivo - no necesita hacer nada, los computed properties se actualizan automáticamente
    currentPage.value = 1;
};

const exportToPDF = () => {
    const params = new URLSearchParams();

    if (selectedCourseParallel.value !== 'all') {
        params.append('curso_paralelo_id', selectedCourseParallel.value);
    }
    if (selectedSubject.value !== 'all') {
        params.append('materia_id', selectedSubject.value);
    }
    if (selectedPeriod.value !== 'all') {
        params.append('periodo_id', selectedPeriod.value);
    }

    window.open(`${route('docente.download-pdf')}?${params.toString()}`, '_blank');
};

const exportToExcel = () => {
    const params = new URLSearchParams();

    if (selectedCourseParallel.value !== 'all') {
        params.append('curso_paralelo_id', selectedCourseParallel.value);
    }
    if (selectedSubject.value !== 'all') {
        params.append('materia_id', selectedSubject.value);
    }
    if (selectedPeriod.value !== 'all') {
        params.append('periodo_id', selectedPeriod.value);
    }

    window.open(`${route('docente.export-excel')}?${params.toString()}`, '_blank');
};

// Computed properties
const filteredAssignments = computed(() => {
    let filtered = props.assignments || [];

    // Filtrar por curso-paralelo
    if (selectedCourseParallel.value !== 'all') {
        const courseParallelId = parseInt(selectedCourseParallel.value);
        const selectedCP = props.coursesParallels.find((cp) => cp.id === courseParallelId);
        if (selectedCP) {
            filtered = filtered.filter(
                (a) => a.estudiante.curso.nombre === selectedCP.curso_nombre && a.estudiante.paralelo.nombre === selectedCP.paralelo_nombre,
            );
        }
    }

    // Filtrar por materia
    if (selectedSubject.value !== 'all') {
        const subjectId = parseInt(selectedSubject.value);
        filtered = filtered.filter((a) => a.materia.id === subjectId);
    }

    // Filtrar por período
    if (selectedPeriod.value !== 'all') {
        const periodId = parseInt(selectedPeriod.value);
        filtered = filtered.filter((a) => a.periodo.id === periodId);
    }

    return filtered;
});

const totalPoints = computed(() => {
    return filteredAssignments.value.reduce((sum, a) => sum + a.puntos, 0);
});

const totalStudents = computed(() => {
    // Contar estudiantes únicos en las asignaciones FILTRADAS
    if (!filteredAssignments.value || filteredAssignments.value.length === 0) return 0;

    const filteredStudents = new Set(filteredAssignments.value.map((a) => a.estudiante.id));
    return filteredStudents.size;
});

const totalSubjects = computed(() => {
    return new Set(filteredAssignments.value.map((a) => a.materia.id)).size;
});

const averagePoints = computed(() => {
    return filteredAssignments.value.length > 0
        ? Math.round((filteredAssignments.value.reduce((sum, a) => sum + a.puntos, 0) / filteredAssignments.value.length) * 10) / 10
        : 0;
});

const subjectStats = computed(() => {
    const stats = new Map();
    filteredAssignments.value.forEach((assignment) => {
        const materia = assignment.materia.nombre;
        if (!stats.has(materia)) {
            stats.set(materia, { id: assignment.materia.id, nombre: materia, puntos: 0 });
        }
        stats.get(materia).puntos += assignment.puntos;
    });
    return Array.from(stats.values());
});

const maxSubjectPoints = computed(() => {
    return Math.max(...subjectStats.value.map((s) => s.puntos), 1);
});

// Pagination
const totalPages = computed(() => Math.ceil(filteredAssignments.value.length / itemsPerPage));
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage);
const endIndex = computed(() => startIndex.value + itemsPerPage);
const paginatedAssignments = computed(() => filteredAssignments.value.slice(startIndex.value, endIndex.value));

const visiblePages = computed(() => {
    const pages = [];
    const start = Math.max(1, currentPage.value - 2);
    const end = Math.min(totalPages.value, start + 4);
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    return pages;
});

const prevPage = () => {
    if (currentPage.value > 1) currentPage.value--;
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) currentPage.value++;
};

const goToPage = (page: number) => {
    currentPage.value = page;
};

const getInitials = (nombres: string, apellidos: string) => {
    const n = nombres?.charAt(0) || '';
    const a = apellidos?.charAt(0) || '';
    return (n + a).toUpperCase();
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>
