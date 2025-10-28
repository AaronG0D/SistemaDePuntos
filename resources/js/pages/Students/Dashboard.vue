<template>
    <StudentLayout :student="student">
        <!-- Hero Section -->
        <StudentWelcome
            :student-name="student.nombres"
            :course="student.curso?.nombre"
            :parallel="student.paralelo?.nombre"
            :total-points="totalPoints"
            :ranking="ranking"
            :current-period="currentPeriod?.nombre"
        />

        <!-- Main Content -->
        <div class="px-6 py-12 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-7xl space-y-8">
                <!-- Stats Cards -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Card
                        class="border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 dark:border-green-700 dark:from-green-900/50 dark:to-emerald-900/50"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-green-100 p-3">
                                    <Leaf class="h-6 w-6 text-green-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Puntos Este Bimestre</p>
                                    <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ currentBimesterPoints }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-blue-200 bg-gradient-to-br from-blue-50 to-cyan-50 dark:border-blue-700 dark:from-blue-900/50 dark:to-cyan-900/50"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-blue-100 p-3">
                                    <Recycle class="h-6 w-6 text-blue-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Depósitos Realizados</p>
                                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ totalDeposits }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50 dark:border-purple-700 dark:from-purple-900/50 dark:to-pink-900/50"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-purple-100 p-3">
                                    <Trophy class="h-6 w-6 text-purple-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Posición en Curso</p>
                                    <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">#{{ ranking }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-orange-200 bg-gradient-to-br from-orange-50 to-yellow-50 dark:border-orange-700 dark:from-orange-900/50 dark:to-yellow-900/50"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-orange-100 p-3">
                                    <Target class="h-6 w-6 text-orange-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-orange-600 dark:text-orange-400">Meta del Bimestre</p>
                                    <p class="text-2xl font-bold text-orange-900 dark:text-orange-100">{{ goalProgress }}%</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Main Horizontal Layout -->
                <div class="grid gap-8 xl:grid-cols-3 lg:grid-cols-2">
                    <!-- Progress & Recent Activity -->
                    <div class="space-y-6 xl:col-span-2">
                        <!-- Progress Section -->
                        <Card class="border-green-200 dark:border-green-700">
                            <CardHeader>
                                <CardTitle class="flex items-center text-green-800 dark:text-green-300">
                                    <TrendingUp class="mr-2 h-5 w-5" />
                                    Tu Progreso Ecológico
                                </CardTitle>
                                <CardDescription> Progreso hacia tu meta de {{ bimesterGoal }} puntos este bimestre </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium">{{ currentBimesterPoints }} / {{ bimesterGoal }} puntos</span>
                                        <span class="text-muted-foreground text-sm">{{ goalProgress }}%</span>
                                    </div>
                                    <div class="relative h-3 w-full overflow-hidden rounded-full bg-green-100 dark:bg-green-900">
                                        <div 
                                            class="h-full bg-gradient-to-r from-green-500 to-emerald-600 rounded-full transition-all duration-700 ease-out"
                                            :style="`width: ${goalProgress}%`"
                                        ></div>
                                        <div v-if="goalProgress >= 100" class="absolute inset-0 flex items-center justify-center">
                                            <span class="text-xs font-bold text-white">¡Meta alcanzada! 🎉</span>
                                        </div>
                                    </div>
                                    <div class="text-muted-foreground flex justify-between text-xs">
                                        <span>Inicio del bimestre</span>
                                        <span>Meta alcanzada</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Combined Activity Grid -->
                        <div class="grid gap-6 lg:grid-cols-2">
                            <!-- Recent Deposits -->
                            <Card class="border-blue-200 dark:border-blue-700">
                                <CardHeader>
                                    <CardTitle class="flex items-center text-blue-800 dark:text-blue-300">
                                        <History class="mr-2 h-5 w-5" />
                                        Depósitos Recientes
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-3">
                                        <div
                                            v-for="deposit in recentDeposits.slice(0, 4)"
                                            :key="deposit.id"
                                            class="hover:bg-muted/50 flex items-center justify-between rounded-lg border p-3 transition-colors dark:border-gray-600 dark:hover:bg-gray-700/50"
                                        >
                                            <div class="flex items-center space-x-3">
                                                <div class="rounded-full bg-green-100 p-2">
                                                    <Trash2 class="h-3 w-3 text-green-600" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium dark:text-gray-100">{{ deposit.tipo_basura?.nombre }}</p>
                                                    <p class="text-muted-foreground text-xs">
                                                        {{ formatDate(deposit.fecha_deposito) }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-bold text-green-600">+{{ deposit.puntaje_obtenido }}</p>
                                            </div>
                                        </div>

                                        <div v-if="recentDeposits.length === 0" class="text-muted-foreground py-6 text-center">
                                            <Leaf class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                            <p class="text-sm">Sin depósitos recientes</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Academic Performance -->
                            <Card class="border-yellow-200 dark:border-yellow-700">
                                <CardHeader>
                                    <CardTitle class="flex items-center text-yellow-800 dark:text-yellow-300">
                                        <GraduationCap class="mr-2 h-5 w-5" />
                                        Notas Recientes
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-3">
                                        <div
                                            v-for="grade in academicGrades.slice(0, 4)"
                                            :key="grade.id"
                                            class="rounded-lg border p-3 transition hover:bg-yellow-50 dark:hover:bg-yellow-900/20"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="text-sm font-medium text-yellow-900 dark:text-yellow-100">
                                                        {{ grade.materia }}
                                                    </h4>
                                                    <p class="text-xs text-yellow-600 dark:text-yellow-400">
                                                        {{ grade.docente }}
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <Badge class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        +{{ grade.puntos }} pts
                                                    </Badge>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="academicGrades.length === 0" class="py-6 text-center">
                                            <GraduationCap class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                            <p class="text-sm text-gray-500">Sin notas registradas</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6 xl:col-span-1">
                        <!-- Quick Stats -->
                        <Card class="border-indigo-200 dark:border-indigo-700">
                            <CardHeader>
                                <CardTitle class="flex items-center text-indigo-800 dark:text-indigo-300">
                                    <Target class="mr-2 h-5 w-5" />
                                    Resumen Rápido
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Posición</span>
                                    <span class="font-bold text-purple-600">#{{ ranking }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Depósitos</span>
                                    <span class="font-bold text-blue-600">{{ totalDeposits }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Meta</span>
                                    <span class="font-bold text-orange-600">{{ goalProgress }}%</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Notas</span>
                                    <span class="font-bold text-yellow-600">{{ academicGrades.length }}</span>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Top 3 del Curso -->
                        <Card class="border-purple-200 dark:border-purple-700">
                            <CardHeader>
                                <CardTitle class="flex items-center text-purple-800 dark:text-purple-300">
                                    <Trophy class="mr-2 h-5 w-5" />
                                    Top 3 de tu Curso
                                </CardTitle>
                                <CardDescription>
                                    Los estudiantes con más puntos en {{ student.curso?.nombre }} "{{ student.paralelo?.nombre }}"
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-3">
                                    <div
                                        v-for="(topStudent, index) in courseTop3"
                                        :key="topStudent.id"
                                        class="flex items-center justify-between rounded-lg border p-3 transition-colors"
                                        :class="
                                            topStudent.id === student.id
                                                ? 'border-purple-500 bg-purple-50 dark:border-purple-400 dark:bg-purple-900/50'
                                                : 'border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-800'
                                        "
                                    >
                                        <div class="flex items-center space-x-3">
                                            <div
                                                :class="[
                                                    'flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold text-white',
                                                    index === 0 ? 'bg-yellow-500' : index === 1 ? 'bg-gray-400' : 'bg-orange-500',
                                                ]"
                                            >
                                                {{ index + 1 }}
                                            </div>
                                            <div>
                                                <p class="font-medium dark:text-gray-100">{{ topStudent.apellidos }}, {{ topStudent.nombres }}</p>
                                                <p v-if="topStudent.id === student.id" class="text-sm text-purple-600 dark:text-purple-400">
                                                    ¡Eres tú!
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-purple-600 dark:text-purple-400">{{ topStudent.puntaje }} pts</p>
                                        </div>
                                    </div>

                                    <div v-if="courseTop3.length === 0" class="text-muted-foreground py-4 text-center">
                                        <Trophy class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                        <p class="text-sm">No hay datos de ranking disponibles</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Bottom Horizontal Grid -->
                <div class="grid gap-6 xl:grid-cols-3 lg:grid-cols-2">
                    <!-- Materias y Docentes -->
                    <Card class="border-indigo-200 dark:border-indigo-700">
                        <CardHeader>
                            <CardTitle class="flex items-center text-indigo-800 dark:text-indigo-300">
                                <BookOpen class="mr-2 h-5 w-5" />
                                Materias que Asignan Puntos
                            </CardTitle>
                            <CardDescription> Conoce las materias y docentes que pueden asignarte puntos </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="subject in subjectsInfo.slice(0, 6)"
                                    :key="subject.id"
                                    class="rounded-lg border border-indigo-100 bg-indigo-50/50 p-3 dark:border-indigo-700 dark:bg-indigo-900/30"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h4 class="text-sm font-semibold text-indigo-900 dark:text-indigo-100">{{ subject.materia }}</h4>
                                            <div class="mt-1 flex items-center gap-2">
                                                <span
                                                    :class="[
                                                        'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                                        subject.tipo === 'tipo_basura'
                                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                            : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                                    ]"
                                                >
                                                    {{ subject.tipo === 'tipo_basura' ? 'Reciclaje' : 'Académica' }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    {{ subject.docentes?.length || 0 }} docentes
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="rounded-full bg-indigo-100 px-2 py-1 dark:bg-indigo-800">
                                                <span class="text-xs font-bold text-indigo-800 dark:text-indigo-200">
                                                    {{ subject.tipo === 'tipo_basura' ? subject.puntos + ' pts' : 'Variable' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="subjectsInfo.length === 0" class="text-muted-foreground py-4 text-center">
                                    <BookOpen class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                    <p class="text-sm">No hay materias configuradas</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Academic Grades by Bimester -->
                    <Card class="border-yellow-200 dark:border-yellow-700">
                        <CardHeader>
                            <CardTitle class="flex items-center text-yellow-800 dark:text-yellow-300">
                                <GraduationCap class="mr-2 h-5 w-5" />
                                Notas por Bimestre
                            </CardTitle>
                            <CardDescription>Tus calificaciones académicas organizadas por período</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div
                                    v-for="grade in academicGrades.slice(0, 5)"
                                    :key="grade.id"
                                    class="rounded-lg border p-4 transition hover:bg-yellow-50 dark:hover:bg-yellow-900/20"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-yellow-900 dark:text-yellow-100">
                                                {{ grade.materia }}
                                            </h4>
                                            <p class="text-sm text-yellow-600 dark:text-yellow-400">
                                                {{ grade.docente }}
                                            </p>
                                            <p v-if="grade.comentario" class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                                {{ grade.comentario }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <Badge class="mb-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                +{{ grade.puntos }} pts
                                            </Badge>
                                            <p class="text-xs text-gray-500">{{ formatDate(grade.fecha) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="academicGrades.length === 0" class="py-6 text-center">
                                    <GraduationCap class="mx-auto mb-2 h-12 w-12 opacity-50" />
                                    <p class="text-sm text-gray-500">No hay notas registradas este bimestre</p>
                                </div>

                                <div v-if="academicGrades.length > 5" class="pt-2 text-center">
                                    <button class="text-sm text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-200">
                                        Ver todas las notas ({{ academicGrades.length }})
                                    </button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Motivational Section -->
                    <Card
                        class="border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 dark:border-emerald-700 dark:from-emerald-900/50 dark:to-teal-900/50"
                    >
                        <CardContent class="p-8 text-center">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                                <Heart class="h-8 w-8 text-emerald-600" />
                            </div>
                            <h3 class="mb-2 text-xl font-bold text-emerald-800 dark:text-emerald-300">¡Excelente trabajo cuidando el planeta!</h3>
                            <p class="mb-4 text-emerald-700 dark:text-emerald-300">
                                Cada punto que ganas representa tu compromiso con el medio ambiente. ¡Sigue así y serás un verdadero héroe ecológico!
                            </p>
                            <div class="flex justify-center space-x-2">
                                <span class="text-2xl">🌱</span>
                                <span class="text-2xl">♻️</span>
                                <span class="text-2xl">🌍</span>
                                <span class="text-2xl">💚</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup lang="ts">
import StudentWelcome from '@/components/student/StudentWelcome.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { BookOpen, Clock, GraduationCap, Heart, History, Leaf, Recycle, Target, Trash2, TrendingUp, Trophy, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
    deposits: Array<{
        id: number;
        fecha_deposito: string;
        cantidad: number;
        puntaje_obtenido: number;
        tipo_basura?: {
            id?: number;
            nombre: string;
        };
    }>;
    currentPeriod?: {
        id: number;
        nombre: string;
    };
    totalPoints: number;
    currentBimesterPoints: number;
    ranking: number;
    bimesterGoal: number;
    courseTop3: Array<{
        id: number;
        nombres: string;
        apellidos: string;
        puntaje: number;
        posicion: number;
    }>;
    subjectsInfo: Array<{
        id: string;
        tipo: 'tipo_basura' | 'materia';
        materia: string;
        puntos: number;
        descripcion?: string;
        docentes: Array<{
            nombres: string;
            apellidos: string;
            nombre_completo: string;
        }>;
    }>;
    academicGrades: Array<{
        id: number;
        materia: string;
        docente: string;
        puntos: number;
        comentario?: string;
        fecha: string;
        periodo: string;
        porcentaje: number;
    }>;
}

const props = defineProps<Props>();

const totalDeposits = computed(() => props.deposits.length);

const recentDeposits = computed(() =>
    props.deposits.sort((a, b) => new Date(b.fecha_deposito).getTime() - new Date(a.fecha_deposito).getTime()).slice(0, 5),
);

const goalProgress = computed(() => Math.min(Math.round((props.currentBimesterPoints / props.bimesterGoal) * 100), 100));

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
