<template>
    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- Header simplificado -->
            <header class="mb-6">
                <div class="rounded-lg border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                    <h1 class="flex items-center gap-3 text-2xl font-semibold text-slate-800 dark:text-slate-100">
                        <BarChart3 class="h-5 w-5 text-slate-600" />
                        Estadísticas Avanzadas
                    </h1>
                    <p class="mt-1 text-slate-600 dark:text-slate-400">
                        Análisis de rendimiento académico - {{ teacher.nombres }} {{ teacher.apellidos }}
                    </p>
                </div>
            </header>

            <!-- Filtros simplificados -->
            <Card class="mb-6">
                <CardContent class="p-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <Label class="text-sm font-medium text-slate-700 dark:text-slate-300">Período</Label>
                            <Select v-model="selectedPeriod" @update:model-value="updateStats">
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
                        <div>
                            <Label class="text-sm font-medium text-slate-700 dark:text-slate-300">Materia</Label>
                            <Select v-model="selectedSubject" @update:model-value="updateStats">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar materia" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las materias</SelectItem>
                                    <SelectItem v-for="subject in subjects" :key="subject.id" :value="subject.id.toString()">
                                        {{ subject.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label class="text-sm font-medium text-slate-700 dark:text-slate-300">Curso</Label>
                            <Select v-model="selectedCourse" @update:model-value="updateStats">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar curso" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los cursos</SelectItem>
                                    <SelectItem v-for="course in courses" :key="course.id" :value="course.id.toString()">
                                        {{ course.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Métricas principales -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                <Card class="border border-slate-200 dark:border-slate-700">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-800">
                                <Award class="h-5 w-5 text-blue-600 dark:text-blue-300" />
                            </div>
                            <div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Puntos Totales</p>
                                <p class="text-xl font-semibold text-slate-900 dark:text-slate-100">
                                    {{ qualityMetrics.total * qualityMetrics.promedio_general }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border border-slate-200 dark:border-slate-700">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-green-100 p-2 dark:bg-green-800">
                                <Users class="h-5 w-5 text-green-600 dark:text-green-300" />
                            </div>
                            <div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Estudiantes</p>
                                <p class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ assignments.length }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border border-slate-200 dark:border-slate-700">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-800">
                                <TrendingUp class="h-5 w-5 text-purple-600 dark:text-purple-300" />
                            </div>
                            <div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Promedio</p>
                                <p class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ qualityMetrics.promedio_general }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border border-slate-200 dark:border-slate-700">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-800">
                                <Target class="h-5 w-5 text-amber-600 dark:text-amber-300" />
                            </div>
                            <div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Asignaciones</p>
                                <p class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ qualityMetrics.total }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabla de estadísticas -->
            <Card class="border border-slate-200 dark:border-slate-700">
                <CardHeader class="bg-slate-50 dark:bg-slate-800/50">
                    <CardTitle class="text-slate-800 dark:text-slate-200">Estadísticas por Materia</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Materia</TableHead>
                                    <TableHead>Estudiantes</TableHead>
                                    <TableHead>Puntos Asignados</TableHead>
                                    <TableHead>Promedio</TableHead>
                                    <TableHead>Última Actividad</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="subject in statsBySubject"
                                    :key="subject.materia"
                                    class="hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                >
                                    <TableCell>
                                        <div class="font-medium text-slate-900 dark:text-slate-100">{{ subject.materia }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-slate-900 dark:text-slate-100">{{ subject.estudiantes_unicos }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-slate-900 dark:text-slate-100">{{ subject.total_puntos }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getPerformanceBadge(subject.promedio)" variant="outline"> {{ subject.promedio }}% </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-sm text-slate-500">{{ subject.total_asignaciones }} asignaciones</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Award, BarChart3, Target, TrendingUp, Users } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

interface Props {
    teacher: {
        nombres: string;
        apellidos: string;
    };
    periods: Array<{
        id: number;
        nombre: string;
    }>;
    subjects: Array<{
        id: number;
        nombre: string;
    }>;
    courses: Array<{
        id: number;
        nombre: string;
    }>;
    coursesParallels: Array<{
        id: number;
        nombre_completo: string;
    }>;
    assignments: Array<any>;
    qualityMetrics: {
        total: number;
        promedio_general: number;
        excelencia: number;
        bueno: number;
        regular: number;
        bajo: number;
    };
    statsBySubject: Array<{
        materia: string;
        total_asignaciones: number;
        promedio: number;
        total_puntos: number;
        estudiantes_unicos: number;
    }>;
}

const props = defineProps<Props>();

// Estado reactivo
const selectedPeriod = ref('all');
const selectedSubject = ref('all');
const selectedCourse = ref('all');

// Métodos
const updateStats = () => {
    // Lógica para actualizar estadísticas según filtros
    console.log('Actualizando estadísticas...');
};

const getPerformanceBadge = (promedio: number) => {
    if (promedio >= 80) return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    if (promedio >= 60) return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    if (promedio >= 40) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
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

// Lifecycle
onMounted(() => {
    // Inicializar estadísticas
});
</script>
