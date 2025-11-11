<template>
    <Head title="Gestión de Estudiantes" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- Header simplificado -->
            <header class="mb-6">
                <div class="rounded-lg border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                    <h1 class="flex items-center gap-3 text-2xl font-semibold text-slate-800 dark:text-slate-100">
                        <Users class="h-5 w-5 text-slate-600" />
                        Gestión de Estudiantes
                    </h1>
                    <p class="mt-1 text-slate-600 dark:text-slate-400">Administra y supervisa a los estudiantes de tus cursos</p>
                </div>
            </header>

            <!-- Filtros simplificados -->
            <Card class="mb-6">
                <CardContent class="p-4">
                    <div class="grid gap-4 md:grid-cols-5">
                        <Input v-model="searchTerm" type="text" placeholder="Buscar estudiante..." class="w-full" />
                        <Select v-model="selectedCourseParallel" @update:modelValue="onCourseParallelChange">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar curso" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los cursos</SelectItem>
                                <SelectItem v-for="courseParallel in coursesParallels" :key="courseParallel.id" :value="courseParallel.id.toString()">
                                    {{ courseParallel.nombre_completo }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
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
                        <Select v-model="performanceFilter">
                            <SelectTrigger>
                                <SelectValue placeholder="Filtrar por rendimiento" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="excellent">Excelente (80+)</SelectItem>
                                <SelectItem value="good">Bueno (60-79)</SelectItem>
                                <SelectItem value="regular">Regular (40-59)</SelectItem>
                                <SelectItem value="needs_improvement">Necesita mejora (<40)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <!-- Lista de estudiantes simplificada -->
            <Card>
                <CardHeader class="bg-slate-50 dark:bg-slate-800/50">
                    <CardTitle class="text-slate-800 dark:text-slate-200"> Estudiantes ({{ filteredStudents.length }}) </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <!-- Tabla simple -->
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Estudiante</TableHead>
                                    <TableHead>Curso</TableHead>
                                    <TableHead>Puntos Asignados</TableHead>
                                    <TableHead>Puntos Depósitos</TableHead>
                                    <TableHead>Asignaciones</TableHead>
                                    <TableHead>Depósitos</TableHead>
                                    <TableHead>Rendimiento</TableHead>
                                    <TableHead>Última Actividad</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="student in paginatedStudents" :key="student.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <TableCell>
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-sm font-medium dark:bg-slate-700"
                                            >
                                                {{ getInitials(student.nombres, student.apellidos) }}
                                            </div>
                                            <div>
                                                <p class="font-medium">{{ student.apellidos }}, {{ student.nombres }}</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{{ student.curso?.nombre }} "{{ student.paralelo?.nombre }}"</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <div class="rounded-full bg-blue-100 p-1 dark:bg-blue-900">
                                                <GraduationCap class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            <Badge variant="secondary" class="bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                                {{ student.total_puntos_asignados || 0 }} pts
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <div class="rounded-full bg-green-100 p-1 dark:bg-green-900">
                                                <Recycle class="h-3 w-3 text-green-600 dark:text-green-400" />
                                            </div>
                                            <Badge variant="secondary" class="bg-green-50 text-green-700 dark:bg-green-900/50 dark:text-green-300">
                                                {{ student.total_puntos_depositos || 0 }} pts
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">{{ student.total_asignaciones || 0 }}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">{{ student.total_depositos || 0 }}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getPerformanceBadge(student.promedio)" variant="outline">
                                            {{ getPerformanceLabel(student.promedio) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            <div
                                                v-if="student.ultima_asignacion && student.ultima_asignacion !== 'Sin asignaciones'"
                                                class="text-blue-600 dark:text-blue-400"
                                            >
                                                Asignación: {{ formatDate(student.ultima_asignacion) }}
                                            </div>
                                            <div
                                                v-if="student.ultimo_deposito && student.ultimo_deposito !== 'Sin depósitos'"
                                                class="text-green-600 dark:text-green-400"
                                            >
                                                Depósito: {{ formatDate(student.ultimo_deposito) }}
                                            </div>
                                            <div v-if="!student.ultima_asignacion && !student.ultimo_deposito" class="text-slate-500">
                                                Sin actividad
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Mensaje cuando no hay estudiantes -->
                    <div v-if="paginatedStudents.length === 0" class="py-12 text-center">
                        <Users class="mx-auto h-12 w-12 text-slate-400" />
                        <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-200">No se encontraron estudiantes</h3>
                        <p class="mt-1 text-sm text-slate-500">Intenta ajustar los filtros de búsqueda.</p>
                    </div>

                    <!-- Paginación simple -->
                    <div v-if="totalPages > 1" class="flex items-center justify-between border-t p-4">
                        <div class="text-sm text-slate-500">
                            Mostrando {{ startIndex + 1 }} - {{ Math.min(endIndex, filteredStudents.length) }} de {{ filteredStudents.length }}
                        </div>
                        <div class="flex items-center space-x-2">
                            <Button variant="outline" size="sm" @click="prevPage" :disabled="currentPage === 1">
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            <span class="text-sm text-slate-500"> Página {{ currentPage }} de {{ totalPages }} </span>
                            <Button variant="outline" size="sm" @click="nextPage" :disabled="currentPage === totalPages">
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, GraduationCap, Recycle, Trophy, Users } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce } from 'lodash';

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
    students: Array<{
        id: number;
        nombres: string;
        apellidos: string;
        curso_paralelo_id: number;
        total_puntos_asignados: number;
        total_puntos_depositos: number;
        total_puntos: number;
        total_asignaciones: number;
        total_depositos: number;
        promedio: number;
        rendimiento_percent: number;
        ultima_asignacion: string;
        ultimo_deposito: string;
        curso: {
            nombre: string;
        };
        paralelo: {
            nombre: string;
        };
    }>;
}

const props = defineProps<Props>();

// Estado reactivo
const searchTerm = ref('');
const selectedCourseParallel = ref('all');
const selectedSubject = ref('all');
const selectedPeriod = ref('all');
const performanceFilter = ref('all');
const currentPage = ref(1);
const itemsPerPage = ref(20);

// Computed properties
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

watch(
    searchTerm,
    debounce((newSearchTerm: string) => {
        performSearch();
    }, 300),
);

watch([selectedCourseParallel, selectedSubject, selectedPeriod], () => {
    performSearch();
});

function performSearch() {
    router.get(
        route('docente.gestion-estudiantes'),
        {
            search: searchTerm.value,
            curso_paralelo_id: selectedCourseParallel.value,
            materia_id: selectedSubject.value,
            periodo_id: selectedPeriod.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

const filteredStudents = computed(() => {
    let filtered = props.students;

    if (performanceFilter.value !== 'all') {
        filtered = filtered.filter((student) => {
            const promedio = student.promedio;
            switch (performanceFilter.value) {
                case 'excellent':
                    return promedio >= 80;
                case 'good':
                    return promedio >= 60 && promedio < 80;
                case 'regular':
                    return promedio >= 40 && promedio < 60;
                case 'needs_improvement':
                    return promedio < 40;
                default:
                    return true;
            }
        });
    }

    return filtered.sort((a, b) => b.total_puntos - a.total_puntos);
});

// Pagination
const totalPages = computed(() => Math.ceil(filteredStudents.value.length / itemsPerPage.value));
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value);
const endIndex = computed(() => startIndex.value + itemsPerPage.value);

const paginatedStudents = computed(() => {
    return filteredStudents.value.slice(startIndex.value, endIndex.value);
});

// Methods
const getInitials = (nombres: string, apellidos: string) => {
    const firstInitial = nombres?.charAt(0).toUpperCase() || '';
    const lastInitial = apellidos?.charAt(0).toUpperCase() || '';
    return `${firstInitial}${lastInitial}`;
};

const getPerformanceBadge = (promedio: number) => {
    if (promedio >= 80) return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    if (promedio >= 50) return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    if (promedio >= 30) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
};

const getPerformanceLabel = (promedio: number) => {
    if (promedio >= 80) return 'Excelente';
    if (promedio >= 50) return 'Normal';
    if (promedio >= 30) return 'Regular';
    return 'Necesita mejora';
};

const formatDate = (dateString: string) => {
    if (!dateString) return 'Sin actividad';
    try {
        return new Date(dateString).toLocaleDateString('es-ES', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    } catch (error) {
        return 'Fecha inválida';
    }
};

const goToPage = (page: number) => {
    currentPage.value = page;
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};
</script>
