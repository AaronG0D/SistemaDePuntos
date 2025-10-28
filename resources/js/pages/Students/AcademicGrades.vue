<template>
    <StudentLayout :student="student">
        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-yellow-600 via-yellow-700 to-orange-600 px-6 py-16 text-white sm:px-8 lg:px-12">
            <div class="mx-auto max-w-7xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Notas Académicas</h1>
                        <p class="mt-4 text-xl text-yellow-100">
                            Revisa tus calificaciones y puntos asignados por bimestre
                        </p>
                        <div class="mt-6 flex items-center space-x-6">
                            <div class="flex items-center">
                                <GraduationCap class="mr-2 h-6 w-6" />
                                <span class="text-lg font-medium">{{ gradesByPeriod.length }} bimestres</span>
                            </div>
                            <div class="flex items-center">
                                <Trophy class="mr-2 h-6 w-6" />
                                <span class="text-lg font-medium">{{ totalPoints }} puntos totales</span>
                            </div>
                        </div>
                    </div>
                    <div class="hidden lg:block">
                        <div class="rounded-full bg-white/10 p-6">
                            <BookOpen class="h-16 w-16 text-white" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="px-6 py-12 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-7xl space-y-8">
                <!-- Summary Cards -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Card class="border-yellow-200 bg-gradient-to-br from-yellow-50 to-orange-50 dark:border-yellow-700 dark:from-yellow-900/50 dark:to-orange-900/50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-yellow-100 p-3">
                                    <BookOpen class="h-6 w-6 text-yellow-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Total Bimestres</p>
                                    <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ gradesByPeriod.length }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 dark:border-green-700 dark:from-green-900/50 dark:to-emerald-900/50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-green-100 p-3">
                                    <Target class="h-6 w-6 text-green-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Puntos Totales</p>
                                    <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ totalPoints }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-blue-200 bg-gradient-to-br from-blue-50 to-cyan-50 dark:border-blue-700 dark:from-blue-900/50 dark:to-cyan-900/50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-blue-100 p-3">
                                    <TrendingUp class="h-6 w-6 text-blue-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Promedio por Bimestre</p>
                                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ averagePointsPerPeriod }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50 dark:border-purple-700 dark:from-purple-900/50 dark:to-pink-900/50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-purple-100 p-3">
                                    <Star class="h-6 w-6 text-purple-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Mejor Bimestre</p>
                                    <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ bestPeriodPoints }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Grades by Period -->
                <div class="space-y-8">
                    <div
                        v-for="periodData in gradesByPeriod"
                        :key="periodData.periodo.id"
                        class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                    >
                        <!-- Period Header -->
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ periodData.periodo.nombre }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Bimestre {{ periodData.periodo.bimestre }} • {{ periodData.notas.length }} notas registradas
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="rounded-lg bg-yellow-100 px-4 py-2 dark:bg-yellow-900">
                                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Total del Período</p>
                                    <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ periodData.total_puntos }} pts</p>
                                </div>
                            </div>
                        </div>

                        <!-- Grades Grid -->
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="grade in periodData.notas"
                                :key="grade.id"
                                class="rounded-lg border border-yellow-200 bg-yellow-50/50 p-4 transition-colors hover:bg-yellow-100/50 dark:border-yellow-700 dark:bg-yellow-900/20 dark:hover:bg-yellow-900/30"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-yellow-900 dark:text-yellow-100">{{ grade.materia }}</h3>
                                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">{{ grade.docente }}</p>
                                        <p v-if="grade.comentario" class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                            "{{ grade.comentario }}"
                                        </p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <Clock class="h-3 w-3 text-gray-500" />
                                            <span class="text-xs text-gray-500">{{ formatDate(grade.fecha) }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <Badge class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            +{{ grade.puntos }} pts
                                        </Badge>
                                        <p v-if="grade.porcentaje !== 100" class="mt-1 text-xs text-gray-500">
                                            {{ grade.porcentaje }}%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="periodData.notas.length === 0" class="py-12 text-center">
                            <GraduationCap class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Sin notas en este período</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                No hay calificaciones registradas para {{ periodData.periodo.nombre }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Empty State for All Periods -->
                <div v-if="gradesByPeriod.length === 0" class="py-16 text-center">
                    <BookOpen class="mx-auto mb-6 h-16 w-16 text-gray-400" />
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Sin notas académicas</h2>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
                        Aún no tienes calificaciones registradas en el sistema.
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        Las notas aparecerán aquí cuando tus docentes las asignen.
                    </p>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { BookOpen, Clock, GraduationCap, Star, Target, TrendingUp, Trophy } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    student: {
        id: number;
        nombres: string;
        apellidos: string;
        codigo_estudiante?: string;
        curso?: {
            id?: number;
            nombre: string;
        };
        paralelo?: {
            id?: number;
            nombre: string;
        };
    };
    currentPeriod?: {
        id: number;
        nombre: string;
    };
    gradesByPeriod: Array<{
        periodo: {
            id: number;
            nombre: string;
            bimestre: number;
        };
        notas: Array<{
            id: number;
            materia: string;
            docente: string;
            puntos: number;
            comentario?: string;
            fecha: string;
            periodo: string;
            porcentaje: number;
        }>;
        total_puntos: number;
        promedio_puntos: number;
    }>;
    totalPoints: number;
}

const props = defineProps<Props>();

const averagePointsPerPeriod = computed(() => {
    if (props.gradesByPeriod.length === 0) return 0;
    return Math.round(props.totalPoints / props.gradesByPeriod.length);
});

const bestPeriodPoints = computed(() => {
    if (props.gradesByPeriod.length === 0) return 0;
    return Math.max(...props.gradesByPeriod.map(p => p.total_puntos));
});

const formatDate = (dateString: string) => {
    try {
        return new Date(dateString).toLocaleDateString('es-ES', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    } catch (error) {
        console.error('Error formatting date:', dateString, error);
        return 'Fecha inválida';
    }
};
</script>
