<template>
    <StudentLayout :student="student">
        <Head title="Ranking" />
        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 px-6 py-16 text-white sm:px-8 lg:px-12">
            <div class="mx-auto max-w-7xl">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-4">
                            <div class="rounded-full bg-white/20 p-4 backdrop-blur-sm">
                                <Trophy class="h-12 w-12 text-white" />
                            </div>
                            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Ranking del Curso</h1>
                        </div>
                        <p class="mt-4 text-xl text-purple-100">
                            {{ student.curso?.nombre }} "{{ student.paralelo?.nombre }}"
                        </p>
                        <p class="mt-2 text-lg text-purple-200">
                            {{ selectedPeriodId ? (currentPeriod?.nombre || 'Período') : 'Todos los bimestres' }}
                        </p>
                        <div class="mt-6 flex items-center space-x-6">
                            <div class="flex items-center">
                                <Users class="mr-2 h-6 w-6" />
                                <span class="text-lg font-medium">{{ totalStudents }} estudiantes</span>
                            </div>
                            <div class="flex items-center">
                                <Target class="mr-2 h-6 w-6" />
                                <span class="text-lg font-medium">Tu posición: #{{ myPosition }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="hidden lg:block">
                        <div class="rounded-full bg-white/10 p-6">
                            <Trophy class="h-16 w-16 text-white" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="px-6 py-12 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-7xl space-y-8">
                <!-- Period Filter -->
                <div v-if="periods && periods.length" class="flex items-center gap-2 overflow-x-auto py-2">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Período:</span>
                    <!-- Botón Todos -->
                    <Button
                        size="sm"
                        variant="outline"
                        :class="[
                            selectedPeriodId === null 
                                ? 'bg-purple-600 text-white border-purple-600 hover:bg-purple-700' 
                                : 'border-gray-300 bg-white text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100'
                        ]"
                        @click="changePeriod(null)"
                    >
                        Todos
                    </Button>
                    <Button
                        v-for="p in periods"
                        :key="p.idPeriodo"
                        size="sm"
                        variant="outline"
                        :class="[
                            selectedPeriodId === p.idPeriodo 
                                ? 'bg-purple-600 text-white border-purple-600 hover:bg-purple-700' 
                                : 'border-gray-300 bg-white text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100'
                        ]"
                        @click="changePeriod(p.idPeriodo)"
                    >
                        {{ p.nombre }}
                    </Button>
                </div>
                <!-- Stats Cards -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Card class="border-yellow-200 bg-gradient-to-br from-yellow-50 to-orange-50 dark:border-yellow-700 dark:from-yellow-900/50 dark:to-orange-900/50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-yellow-100 p-3 dark:bg-yellow-900">
                                    <Crown class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Líder</p>
                                    <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ topStudent?.puntaje || 0 }} pts</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50 dark:border-blue-700 dark:from-blue-900/50 dark:to-indigo-900/50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                                    <TrendingUp class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Tu Posición</p>
                                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">#{{ myPosition }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 dark:border-green-700 dark:from-green-900/50 dark:to-emerald-900/50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-green-100 p-3 dark:bg-green-900">
                                    <BarChart3 class="h-6 w-6 text-green-600 dark:text-green-400" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Promedio</p>
                                    <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ averagePoints }} pts</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50 dark:border-purple-700 dark:from-purple-900/50 dark:to-pink-900/50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900">
                                    <Zap class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Diferencia</p>
                                    <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ pointsToLeader }} pts</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Podium Section -->
                <Card class="border-yellow-200 dark:border-yellow-700">
                    <CardHeader>
                        <CardTitle class="flex items-center text-yellow-800 dark:text-yellow-300">
                            <Medal class="mr-2 h-5 w-5" />
                            Podium de Honor
                        </CardTitle>
                        <CardDescription class="dark:text-gray-400">Los 3 mejores estudiantes del curso</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-6 md:grid-cols-3">
                            <!-- Second Place -->
                            <div v-if="podium[1]" class="order-2 md:order-1">
                                <div class="text-center">
                                    <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-gray-300 to-gray-500 text-2xl font-bold text-white shadow-lg">
                                        {{ getInitials(podium[1].nombres, podium[1].apellidos) }}
                                    </div>
                                    <div class="mb-2 flex justify-center">
                                        <Badge class="bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                            <Medal class="mr-1 h-3 w-3" />
                                            2do Lugar
                                        </Badge>
                                    </div>
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ podium[1].nombres }} {{ podium[1].apellidos }}</h3>
                                    <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ podium[1].puntaje }} pts</p>
                                </div>
                            </div>

                            <!-- First Place -->
                            <div v-if="podium[0]" class="order-1 md:order-2">
                                <div class="text-center">
                                    <div class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 text-2xl font-bold text-white shadow-lg">
                                        {{ getInitials(podium[0].nombres, podium[0].apellidos) }}
                                    </div>
                                    <div class="mb-2 flex justify-center">
                                        <Badge class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            <Crown class="mr-1 h-3 w-3" />
                                            1er Lugar
                                        </Badge>
                                    </div>
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ podium[0].nombres }} {{ podium[0].apellidos }}</h3>
                                    <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ podium[0].puntaje }} pts</p>
                                </div>
                            </div>

                            <!-- Third Place -->
                            <div v-if="podium[2]" class="order-3">
                                <div class="text-center">
                                    <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-orange-400 to-orange-600 text-2xl font-bold text-white shadow-lg">
                                        {{ getInitials(podium[2].nombres, podium[2].apellidos) }}
                                    </div>
                                    <div class="mb-2 flex justify-center">
                                        <Badge class="bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                            <Award class="mr-1 h-3 w-3" />
                                            3er Lugar
                                        </Badge>
                                    </div>
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ podium[2].nombres }} {{ podium[2].apellidos }}</h3>
                                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ podium[2].puntaje }} pts</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Full Ranking -->
                <Card class="border-purple-200 dark:border-purple-700">
                    <CardHeader>
                        <CardTitle class="flex items-center text-purple-800 dark:text-purple-300">
                            <Trophy class="mr-2 h-5 w-5" />
                            Ranking Completo
                        </CardTitle>
                        <CardDescription class="dark:text-gray-400">Posiciones de todos los estudiantes del curso</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="studentRank in ranking"
                                :key="studentRank.id"
                                :class="[
                                    'flex items-center justify-between rounded-lg border p-4 transition-all hover:shadow-md',
                                    studentRank.id === student.id 
                                        ? 'border-purple-300 bg-purple-50 dark:border-purple-600 dark:bg-purple-900/30' 
                                        : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700',
                                ]"
                            >
                                <div class="flex items-center space-x-4">
                                    <!-- Position Badge -->
                                    <div
                                        :class="[
                                            'flex h-12 w-12 items-center justify-center rounded-full font-bold text-white shadow-sm',
                                            studentRank.posicion === 1
                                                ? 'bg-gradient-to-br from-yellow-400 to-yellow-600'
                                                : studentRank.posicion === 2
                                                  ? 'bg-gradient-to-br from-gray-300 to-gray-500'
                                                  : studentRank.posicion === 3
                                                    ? 'bg-gradient-to-br from-orange-400 to-orange-600'
                                                    : 'bg-gradient-to-br from-blue-400 to-blue-600',
                                        ]"
                                    >
                                        <span v-if="studentRank.posicion <= 3">
                                            <Crown v-if="studentRank.posicion === 1" class="h-5 w-5" />
                                            <Medal v-else-if="studentRank.posicion === 2" class="h-5 w-5" />
                                            <Award v-else class="h-5 w-5" />
                                        </span>
                                        <span v-else class="text-sm">{{ studentRank.posicion }}</span>
                                    </div>

                                    <!-- Student Info -->
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                                {{ studentRank.apellidos }}, {{ studentRank.nombres }}
                                            </h3>
                                            <Badge v-if="studentRank.id === student.id" class="bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                Tú
                                            </Badge>
                                        </div>
                                        <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                            <span>Posición {{ studentRank.posicion }} de {{ totalStudents }}</span>
                                            <span v-if="studentRank.posicion > 1">
                                                {{ getPointsDifference(studentRank) }} pts del líder
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Points Display -->
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ studentRank.puntaje }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">puntos</div>
                                        <div v-if="studentRank.posicion <= 3" class="mt-1">
                                            <Badge 
                                                :class="[
                                                    studentRank.posicion === 1 
                                                        ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                                        : studentRank.posicion === 2
                                                          ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
                                                          : 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
                                                ]"
                                            >
                                                <Crown v-if="studentRank.posicion === 1" class="mr-1 h-3 w-3" />
                                                <Medal v-else-if="studentRank.posicion === 2" class="mr-1 h-3 w-3" />
                                                <Award v-else class="mr-1 h-3 w-3" />
                                                {{ studentRank.posicion === 1 ? 'Oro' : studentRank.posicion === 2 ? 'Plata' : 'Bronce' }}
                                            </Badge>
                                        </div>
                                    </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="ranking.length === 0" class="py-12 text-center">
                            <Trophy class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-600" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Sin datos de ranking</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                No hay información de ranking disponible para este período.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { defineProps } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { 
    Award, 
    BarChart3, 
    Crown, 
    Medal, 
    Target, 
    TrendingUp, 
    Trophy, 
    Users, 
    Zap 
} from 'lucide-vue-next';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

interface Props {
    student: {
        id: number;
        nombres: string;
        apellidos: string;
        curso?: {
            nombre: string;
        };
        paralelo?: {
            nombre: string;
        };
    };
    ranking: Array<{
        id: number;
        nombres: string;
        apellidos: string;
        puntaje: number;
        posicion: number;
    }>;
    currentPeriod?: {
        nombre: string;
    };
    periods?: Array<{ idPeriodo: number; nombre: string }>;
    selectedPeriodId?: number | null;
    myPosition: number;
    totalStudents: number;
}

const props = defineProps<Props>();

const changePeriod = (idPeriodo?: number | null) => {
    const params = idPeriodo ? { periodo_id: idPeriodo } : {};
    router.get('/estudiante/ranking', params, { preserveScroll: true });
};
const periods = props.periods || [];
const selectedPeriodId = props.selectedPeriodId ?? null;

// Computed properties
const topStudent = computed(() => {
    return props.ranking.find(s => s.posicion === 1);
});

const averagePoints = computed(() => {
    if (props.ranking.length === 0) return 0;
    const total = props.ranking.reduce((sum, s) => sum + s.puntaje, 0);
    return Math.round(total / props.ranking.length);
});

const pointsToLeader = computed(() => {
    const leader = topStudent.value;
    const currentStudent = props.ranking.find(s => s.id === props.student.id);
    if (!leader || !currentStudent) return 0;
    return leader.puntaje - currentStudent.puntaje;
});

const podium = computed(() => {
    return props.ranking.filter(s => s.posicion <= 3).sort((a, b) => a.posicion - b.posicion);
});

// Helper functions
const getInitials = (nombres: string, apellidos: string) => {
    const firstInitial = nombres.charAt(0).toUpperCase();
    const lastInitial = apellidos.charAt(0).toUpperCase();
    return `${firstInitial}${lastInitial}`;
};

const getPointsDifference = (student: { puntaje: number }) => {
    const leader = topStudent.value;
    if (!leader) return 0;
    return leader.puntaje - student.puntaje;
};
</script>
