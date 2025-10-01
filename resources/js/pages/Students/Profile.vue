<template>
    <StudentLayout :student="student">
        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 px-6 py-12 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-7xl text-center">
                <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-white/20 text-3xl font-bold text-white shadow-lg backdrop-blur-sm">
                    {{ getInitials(student.nombres, student.apellidos) }}
                </div>
                <h1 class="text-4xl font-bold text-white sm:text-5xl">{{ student.nombres }} {{ student.apellidos }}</h1>
                <p class="mt-4 text-xl text-emerald-100">Estudiante Eco-Responsable</p>
                <div class="mt-6 flex justify-center space-x-8">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">{{ totalPoints }}</div>
                        <div class="text-sm text-emerald-100">Puntos Totales</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">#{{ ranking }}</div>
                        <div class="text-sm text-emerald-100">Posición</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="px-6 py-8 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-4xl space-y-8">
                <!-- Profile Card -->
                <Card class="border-green-200">
                    <CardContent class="p-8">
                        <div class="flex flex-col items-center text-center lg:flex-row lg:text-left">
                            <!-- Avatar -->
                            <div class="mb-6 lg:mr-8 lg:mb-0">
                                <div
                                    class="mx-auto flex h-32 w-32 items-center justify-center rounded-full bg-gradient-to-br from-green-400 to-emerald-600 text-4xl font-bold text-white shadow-lg"
                                >
                                    {{ getInitials(student.nombres, student.apellidos) }}
                                </div>
                            </div>

                            <!-- Student Info -->
                            <div class="flex-1 space-y-4">
                                <div>
                                    <h2 class="text-3xl font-bold text-gray-900">{{ student.nombres }} {{ student.apellidos }}</h2>
                                    <p class="text-lg font-medium text-green-600">Estudiante Eco-Responsable</p>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex items-center space-x-3">
                                        <div class="rounded-full bg-blue-100 p-2">
                                            <GraduationCap class="h-5 w-5 text-blue-600" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Curso</p>
                                            <p class="font-semibold text-gray-900">{{ student.curso?.nombre }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-3">
                                        <div class="rounded-full bg-purple-100 p-2">
                                            <Users class="h-5 w-5 text-purple-600" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Paralelo</p>
                                            <p class="font-semibold text-gray-900">{{ student.paralelo?.nombre }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-3">
                                        <div class="rounded-full bg-green-100 p-2">
                                            <Calendar class="h-5 w-5 text-green-600" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Período Académico</p>
                                            <p class="font-semibold text-gray-900">{{ currentPeriod?.nombre }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-3">
                                        <div class="rounded-full bg-orange-100 p-2">
                                            <IdCard class="h-5 w-5 text-orange-600" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Código Estudiante</p>
                                            <p class="font-semibold text-gray-900">{{ student.codigo_estudiante || 'No asignado' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Stats Overview -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Card class="border-green-200 bg-gradient-to-br from-green-50 to-emerald-50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-green-100 p-3">
                                    <Trophy class="h-6 w-6 text-green-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-600">Puntos Totales</p>
                                    <p class="text-2xl font-bold text-green-900">{{ totalPoints }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-blue-200 bg-gradient-to-br from-blue-50 to-cyan-50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-blue-100 p-3">
                                    <Recycle class="h-6 w-6 text-blue-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-600">Depósitos</p>
                                    <p class="text-2xl font-bold text-blue-900">{{ totalDeposits }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-purple-100 p-3">
                                    <Target class="h-6 w-6 text-purple-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-purple-600">Ranking</p>
                                    <p class="text-2xl font-bold text-purple-900">#{{ ranking }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-yellow-200 bg-gradient-to-br from-yellow-50 to-orange-50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-yellow-100 p-3">
                                    <Award class="h-6 w-6 text-yellow-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-yellow-600">Logros</p>
                                    <p class="text-2xl font-bold text-yellow-900">{{ earnedAchievements }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Academic Information -->
                <Card class="border-blue-200 dark:border-blue-700">
                    <CardHeader>
                        <CardTitle class="flex items-center text-blue-800 dark:text-blue-300">
                            <BookOpen class="mr-2 h-5 w-5" />
                            Información Académica
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-4">
                                <div>
                                    <h4 class="mb-2 font-semibold text-gray-900 dark:text-gray-100">Detalles del Curso</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Curso:</span>
                                            <span class="font-medium dark:text-gray-200">{{ student.curso?.nombre }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Paralelo:</span>
                                            <span class="font-medium dark:text-gray-200">{{ student.paralelo?.nombre }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Período:</span>
                                            <span class="font-medium dark:text-gray-200">{{ currentPeriod?.nombre }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <h4 class="mb-2 font-semibold text-gray-900 dark:text-gray-100">Rendimiento Ecológico</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Promedio por depósito:</span>
                                            <span class="font-medium dark:text-gray-200">{{ averagePointsPerDeposit }} pts</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Mejor bimestre:</span>
                                            <span class="font-medium dark:text-gray-200">{{ bestBimester }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Peso total reciclado:</span>
                                            <span class="font-medium dark:text-gray-200">{{ totalWeight }}kg</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- QR Code -->
                <Card class="border-green-200 dark:border-green-700">
                    <CardHeader>
                        <CardTitle class="flex items-center text-green-800 dark:text-green-300">
                            <QrCode class="mr-2 h-5 w-5" />
                            Mi Código QR
                        </CardTitle>
                        <CardDescription> Usa este código para registrar tus depósitos de residuos </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col items-center space-y-4 sm:flex-row sm:space-y-0 sm:space-x-8">
                            <div class="rounded-lg border-2 border-green-200 dark:border-green-600 bg-white dark:bg-gray-800 p-4 shadow-lg">
                                <div class="flex h-32 w-32 items-center justify-center rounded bg-gray-100 dark:bg-gray-700">
                                    <!-- Aquí iría el QR code real -->
                                    <QrCode class="h-16 w-16 text-gray-400" />
                                </div>
                            </div>
                            <div class="text-center sm:text-left">
                                <h4 class="mb-2 font-semibold text-gray-900 dark:text-gray-100">Código: {{ student.codigo_estudiante || student.id }}</h4>
                                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                    Muestra este código QR al docente o encargado cuando realices un depósito de residuos.
                                </p>
                                <Button variant="outline" size="sm" class="border-green-200 dark:border-green-600 text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900">
                                    <Download class="mr-2 h-4 w-4" />
                                    Descargar QR
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Environmental Impact -->
                <Card class="border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50">
                    <CardHeader>
                        <CardTitle class="flex items-center text-emerald-800">
                            <Leaf class="mr-2 h-5 w-5" />
                            Tu Impacto Ambiental
                        </CardTitle>
                        <CardDescription> El impacto positivo que has generado en el medio ambiente </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-6 md:grid-cols-3">
                            <div class="text-center">
                                <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                                    <TreePine class="h-6 w-6 text-green-600" />
                                </div>
                                <div class="text-2xl font-bold text-green-900">{{ treesEquivalent }}</div>
                                <div class="text-sm text-green-600">Árboles salvados</div>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                    <Droplets class="h-6 w-6 text-blue-600" />
                                </div>
                                <div class="text-2xl font-bold text-blue-900">{{ waterSaved }}L</div>
                                <div class="text-sm text-blue-600">Agua ahorrada</div>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-purple-100">
                                    <Zap class="h-6 w-6 text-purple-600" />
                                </div>
                                <div class="text-2xl font-bold text-purple-900">{{ energySaved }}kWh</div>
                                <div class="text-sm text-purple-600">Energía ahorrada</div>
                            </div>
                        </div>

                        <div class="mt-6 text-center">
                            <p class="font-medium text-emerald-700">
                                ¡Felicitaciones! Tu compromiso con el reciclaje está haciendo la diferencia. 🌍💚
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
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Award,
    BookOpen,
    Calendar,
    Download,
    Droplets,
    GraduationCap,
    IdCard,
    Leaf,
    QrCode,
    Recycle,
    Target,
    TreePine,
    Trophy,
    Users,
    Zap,
} from 'lucide-vue-next';
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
    deposits: Array<{
        id: number;
        fecha_deposito: string;
        cantidad: number;
        puntaje_obtenido: number;
        bimestre: number;
    }>;
    currentPeriod?: {
        id: number;
        nombre: string;
    };
    totalPoints: number;
    ranking: number;
}

const props = defineProps<Props>();

const totalDeposits = computed(() => props.deposits.length);

const earnedAchievements = computed(() => {
    let count = 0;
    if (props.deposits.length > 0) count++; // Primer depósito
    if (props.totalPoints >= 100) count++; // 100 puntos
    if (props.deposits.length >= 10) count++; // 10 depósitos
    if (props.totalPoints >= 500) count++; // 500 puntos
    return count;
});

const averagePointsPerDeposit = computed(() => {
    if (props.deposits.length === 0) return 0;
    return Math.round(props.totalPoints / props.deposits.length);
});

const bestBimester = computed(() => {
    const bimesterPoints = [1, 2, 3, 4].map((b) => ({
        bimester: b,
        points: props.deposits.filter((d) => d.bimestre === b).reduce((sum, d) => sum + d.puntaje_obtenido, 0),
    }));

    const best = bimesterPoints.reduce((max, current) => (current.points > max.points ? current : max));

    const bimesterNames = ['Primer', 'Segundo', 'Tercer', 'Cuarto'];
    return best.points > 0 ? `${bimesterNames[best.bimester - 1]} Bimestre` : 'Ninguno';
});

const totalWeight = computed(() => {
    return props.deposits.reduce((sum, d) => sum + d.cantidad, 0).toFixed(1);
});

// Cálculos de impacto ambiental (estimaciones)
const treesEquivalent = computed(() => {
    // Aproximadamente 1 árbol por cada 17kg de papel reciclado
    return Math.round(parseFloat(totalWeight.value) / 17);
});

const waterSaved = computed(() => {
    // Aproximadamente 50L de agua ahorrada por kg de material reciclado
    return Math.round(parseFloat(totalWeight.value) * 50);
});

const energySaved = computed(() => {
    // Aproximadamente 3kWh de energía ahorrada por kg de material reciclado
    return Math.round(parseFloat(totalWeight.value) * 3);
});

const getInitials = (nombres: string, apellidos: string) => {
    const firstInitial = nombres.charAt(0).toUpperCase();
    const lastInitial = apellidos.charAt(0).toUpperCase();
    return `${firstInitial}${lastInitial}`;
};
</script>