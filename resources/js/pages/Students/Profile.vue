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
            <div class="mx-auto max-w-7xl space-y-8">
                <!-- Profile Card -->
                <Card class="border-green-200 dark:border-green-700">
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
                                    <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ student.nombres }} {{ student.apellidos }}</h2>
                                    <p class="text-lg font-medium text-green-600 dark:text-green-400">Estudiante Eco-Responsable</p>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex items-center space-x-3">
                                        <div class="rounded-full bg-blue-100 dark:bg-blue-900 p-2">
                                            <GraduationCap class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Curso</p>
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ student.curso?.nombre }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-3">
                                        <div class="rounded-full bg-purple-100 dark:bg-purple-900 p-2">
                                            <Users class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Paralelo</p>
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ student.paralelo?.nombre }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-3">
                                        <div class="rounded-full bg-green-100 dark:bg-green-900 p-2">
                                            <Calendar class="h-5 w-5 text-green-600 dark:text-green-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Período Académico</p>
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ currentPeriod?.nombre }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-3">
                                        <div class="rounded-full bg-orange-100 dark:bg-orange-900 p-2">
                                            <IdCard class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Código Estudiante</p>
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ student.codigo_estudiante || 'No asignado' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Stats Overview -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Card class="border-green-200 dark:border-green-700 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/50 dark:to-emerald-900/50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-green-100 dark:bg-green-800 p-3">
                                    <Trophy class="h-6 w-6 text-green-600 dark:text-green-400" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Puntos Totales</p>
                                    <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ totalPoints }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-blue-200 dark:border-blue-700 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/50 dark:to-cyan-900/50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-blue-100 dark:bg-blue-800 p-3">
                                    <Recycle class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Depósitos</p>
                                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ totalDeposits }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-purple-200 dark:border-purple-700 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/50 dark:to-pink-900/50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-purple-100 dark:bg-purple-800 p-3">
                                    <Target class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Ranking</p>
                                    <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">#{{ ranking }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-yellow-200 dark:border-yellow-700 bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/50 dark:to-orange-900/50">
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-yellow-100 dark:bg-yellow-800 p-3">
                                    <Award class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Logros</p>
                                    <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ earnedAchievements }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Horizontal Information Grid -->
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Academic Information -->
                    <Card class="border-blue-200 dark:border-blue-700">
                        <CardHeader>
                            <CardTitle class="flex items-center text-blue-800 dark:text-blue-300">
                                <BookOpen class="mr-2 h-5 w-5" />
                                Información Académica
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
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
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Código:</span>
                                    <span class="font-medium dark:text-gray-200">{{ student.codigo_estudiante || 'No asignado' }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Performance Metrics -->
                    <Card class="border-green-200 dark:border-green-700">
                        <CardHeader>
                            <CardTitle class="flex items-center text-green-800 dark:text-green-300">
                                <TrendingUp class="mr-2 h-5 w-5" />
                                Rendimiento Ecológico
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Promedio por depósito:</span>
                                    <span class="font-medium dark:text-gray-200">{{ averagePointsPerDeposit }} pts</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Mejor bimestre:</span>
                                    <span class="font-medium dark:text-gray-200">{{ bestBimester }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Depósitos totales:</span>
                                    <span class="font-medium dark:text-gray-200">{{ deposits.length }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Academic Grades -->
                    <Card class="border-yellow-200 dark:border-yellow-700">
                        <CardHeader>
                            <CardTitle class="flex items-center text-yellow-800 dark:text-yellow-300">
                                <GraduationCap class="mr-2 h-5 w-5" />
                                Notas Recientes
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div v-if="recentGrades.length === 0" class="text-center py-4">
                                    <GraduationCap class="mx-auto mb-2 h-8 w-8 text-gray-400 dark:text-gray-500" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin notas registradas</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">Aún no tienes calificaciones</p>
                                </div>
                                <div v-else class="space-y-2">
                                    <div v-for="grade in recentGrades" :key="grade.id" class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ grade.materia }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ currentPeriod?.nombre }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">{{ grade.total }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">pts</p>
                                        </div>
                                    </div>
                                </div>
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    class="w-full border-yellow-200 dark:border-yellow-600 text-yellow-700 dark:text-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-900"
                                    @click="router.visit(route('students.academic-grades'))"
                                >
                                    Ver todas las notas
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Alerta QR Desactivado -->
                <div v-if="!student.qr_codigo || student.qr_codigo === ''" class="rounded-lg border-2 border-red-300 bg-red-50 dark:border-red-600 dark:bg-red-900/20 p-6">
                    <div class="flex items-start gap-4">
                        <div class="rounded-full bg-red-100 dark:bg-red-900 p-3">
                            <AlertCircle class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-red-900 dark:text-red-100 mb-2">
                                ⚠️ Código QR Desactivado
                            </h3>
                            <p class="text-red-800 dark:text-red-200 mb-3">
                                Tu código QR está actualmente <strong>desactivado</strong>. No podrás acumular puntos ni registrar depósitos hasta que sea reactivado.
                            </p>
                            <div class="rounded-md bg-red-100 dark:bg-red-900/40 p-4 mb-3">
                                <p class="text-sm text-red-900 dark:text-red-100 font-medium mb-2">
                                    📞 Para reactivar tu código QR:
                                </p>
                                <ul class="text-sm text-red-800 dark:text-red-200 space-y-1 ml-4">
                                    <li>• Comunícate con la <strong>Dirección del colegio</strong></li>
                                    <li>• Solicita información sobre la reactivación</li>
                                    <li>• Proporciona tu código de estudiante: <strong>{{ student.id }}</strong></li>
                                </ul>
                            </div>
                           
                            <p class="text-xs text-red-700 dark:text-red-300">
                                Una vez reactivado, podrás volver a acumular puntos y participar en el programa de reciclaje.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- QR Code -->
                <Card v-else class="border-green-200 dark:border-green-700">
                    <CardHeader>
                        <CardTitle class="flex items-center text-green-800 dark:text-green-300">
                            <QrCode class="mr-2 h-5 w-5" />
                            Mi Código QR
                        </CardTitle>
                        <CardDescription class="dark:text-gray-400"> Usa este código para registrar tus depósitos de residuos </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col items-center space-y-4 sm:flex-row sm:space-y-0 sm:space-x-8">
                            <!-- QR Code con contorno bonito -->
                            <div class="rounded-lg border-2 border-green-200 dark:border-green-600 bg-white dark:bg-gray-800 p-4 shadow-lg">
                                <div v-if="qrLoading" class="h-48 w-48 flex items-center justify-center">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
                                </div>
                                <div v-else-if="qrError" class="h-48 w-48 flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-700 rounded">
                                    <QrCode class="h-16 w-16 text-gray-400 mb-2" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">QR no disponible</p>
                                </div>
                                <img 
                                    v-else-if="qrUrl"
                                    :src="qrUrl" 
                                    :alt="'QR de ' + student.nombres + ' ' + student.apellidos"
                                    class="h-48 w-48 rounded"
                                />
                            </div>
                            
                            <!-- Información y botones -->
                            <div class="text-center sm:text-left">
                                <h4 class="mb-2 font-semibold text-gray-900 dark:text-gray-100">Código: {{ student.codigo_estudiante || student.id }}</h4>
                                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                    Muestra este código QR al docente o encargado cuando realices un depósito de residuos.
                                </p>
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        class="border-green-200 dark:border-green-600 text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900"
                                        @click="downloadQr"
                                        :disabled="!qrUrl"
                                    >
                                        <Download class="mr-2 h-4 w-4" />
                                        Descargar QR
                                    </Button>
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        class="border-blue-200 dark:border-blue-600 text-blue-700 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900"
                                        @click="printQr"
                                        :disabled="!qrUrl"
                                    >
                                        <QrCode class="mr-2 h-4 w-4" />
                                        Imprimir QR
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Environmental Impact -->
                <Card class="border-emerald-200 dark:border-emerald-700 bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/50 dark:to-teal-900/50">
                    <CardHeader>
                        <CardTitle class="flex items-center text-emerald-800 dark:text-emerald-300">
                            <Leaf class="mr-2 h-5 w-5" />
                            Tu Impacto Ambiental
                        </CardTitle>
                        <CardDescription class="dark:text-gray-400"> El impacto positivo que has generado en el medio ambiente </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-6 md:grid-cols-3">
                            <div class="text-center">
                                <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-800">
                                    <TreePine class="h-6 w-6 text-green-600 dark:text-green-400" />
                                </div>
                                <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ treesEquivalent }}</div>
                                <div class="text-sm text-green-600 dark:text-green-400">Árboles salvados</div>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-800">
                                    <Droplets class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ waterSaved }}L</div>
                                <div class="text-sm text-blue-600 dark:text-blue-400">Agua ahorrada</div>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-800">
                                    <Zap class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ energySaved }}kWh</div>
                                <div class="text-sm text-purple-600 dark:text-purple-400">Energía ahorrada</div>
                            </div>
                        </div>

                        <!-- Descripción de cálculos -->
                        <div class="mt-6 rounded-lg bg-gray-50 p-4 dark:bg-gray-800/50">
                            <h4 class="mb-3 font-semibold text-gray-800 dark:text-gray-200">¿Cómo calculamos tu impacto?</h4>
                            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex items-center gap-2">
                                    <TreePine class="h-4 w-4 text-green-600" />
                                    <p><strong>Árboles salvados:</strong> 1 árbol por cada 100 puntos de reciclaje (basado en estudios de conservación forestal)</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Droplets class="h-4 w-4 text-blue-600" />
                                    <p><strong>Agua ahorrada:</strong> 3 litros por punto (promedio del ahorro de agua en procesos de reciclaje vs. producción nueva)</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Zap class="h-4 w-4 text-yellow-600" />
                                    <p><strong>Energía ahorrada:</strong> 0.4 kWh por punto (reducción de energía al reciclar materiales vs. crear nuevos)</p>
                                </div>
                            </div>
                            <div class="mt-3 text-xs text-gray-500 dark:text-gray-500">
                                *Estimaciones basadas en estudios ambientales de la EPA y organizaciones de reciclaje internacionales
                            </div>
                        </div>

                        <div class="mt-6 text-center">
                            <p class="font-medium text-emerald-700 dark:text-emerald-300">
                                ¡Felicitaciones! Tu compromiso con el reciclaje está haciendo la diferencia.
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
    AlertCircle,
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
    TrendingUp,
    Trophy,
    Users,
    Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    student: {
        id: number;
        nombres: string;
        apellidos: string;
        codigo_estudiante?: string;
        qr_codigo?: string | null;
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
    academicGrades?: Array<{
        id: number;
        materia: string;
        total: number;
        periodo: string;
        ultima_fecha: string;
    }>;
}

const props = defineProps<Props>();

// Estados para el QR
const qrUrl = ref('');
const qrLoading = ref(true);
const qrError = ref(false);

const totalDeposits = computed(() => props.deposits.length);

const recentGrades = computed(() => {
    if (!props.academicGrades || props.academicGrades.length === 0) return [];
    // Mostrar las 3 notas más recientes
    return props.academicGrades.slice(0, 3);
});

const earnedAchievements = computed(() => {
    let count = 0;
    if (props.deposits.length > 0) count++; // Eco-Iniciado: Primer depósito
    if (props.totalPoints >= 100) count++; // Reciclador Activo: 100 puntos
    if (props.deposits.length >= 10) count++; // Guardián Verde: 10 depósitos
    if (props.totalPoints >= 300) count++; // Campeón Ecológico: 300 puntos
    if (!props.deposits || props.deposits.length === 0) return 0;
                                                                  //Deposito en todos los bimestres
    const bimesterPoints = [1, 2, 3].map((b) => ({
        bimester: b,
        points: props.deposits.filter((d) => d.bimestre === b).reduce((sum, d) => sum + d.puntaje_obtenido, 0),
    }));
    const allBimestersHaveDeposits = bimesterPoints.every((b) => b.points > 0);
    if (allBimestersHaveDeposits) count++; // Eco-Responsable: Primer depósito en todos los bimestres
    
    
    return count;
});

const averagePointsPerDeposit = computed(() => {
    if (props.deposits.length === 0) return 0;
    return Math.round(props.totalPoints / props.deposits.length);
});

const bestBimester = computed(() => {
    const bimesterPoints = [1, 2, 3].map((b) => ({
        bimester: b,
        points: props.deposits.filter((d) => d.bimestre === b).reduce((sum, d) => sum + d.puntaje_obtenido, 0),
    }));

    const best = bimesterPoints.reduce((max, current) => (current.points > max.points ? current : max));

    const bimesterNames = ['Primer', 'Segundo', 'Tercer'];
    return best.points > 0 ? `${bimesterNames[best.bimester - 1]} Trimestre` : 'Ninguno';
});

// Cálculos de impacto ambiental basados en puntos de reciclaje (igual que Dashboard)
const treesEquivalent = computed(() => {
    const totalPoints = props.totalPoints;
    if (totalPoints === 0) return 0;
    
    // 1 árbol por cada 100 puntos de reciclaje
    const treesSaved = totalPoints * 0.01;
    return Math.max(Math.round(treesSaved), 0);
});

const waterSaved = computed(() => {
    const totalPoints = props.totalPoints;
    if (totalPoints === 0) return 0;
    
    // 3 litros por punto
    return Math.max(Math.round(totalPoints * 3), 0);
});

const energySaved = computed(() => {
    const totalPoints = props.totalPoints;
    if (totalPoints === 0) return 0;
    
    // 0.4 kWh por punto
    return Math.max(Math.round(totalPoints * 0.4), 0);
});

const getInitials = (nombres: string, apellidos: string) => {
    const firstInitial = nombres.charAt(0).toUpperCase();
    const lastInitial = apellidos.charAt(0).toUpperCase();
    return `${firstInitial}${lastInitial}`;
};

// Cargar QR al montar el componente
const loadQr = async () => {
    qrLoading.value = true;
    qrError.value = false;
    
    try {
        const response = await fetch(route('qr.generate.user', props.student.id));
        const data = await response.json();
        
        if (data.success && data.qr_url) {
            qrUrl.value = data.qr_url;
        } else {
            qrError.value = true;
        }
    } catch (error) {
        console.error('Error cargando QR:', error);
        qrError.value = true;
    } finally {
        qrLoading.value = false;
    }
};

// Descargar QR
const downloadQr = () => {
    if (!qrUrl.value) return;
    const link = document.createElement('a');
    link.href = qrUrl.value;
    link.download = `qr_${props.student.nombres}_${props.student.apellidos}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Imprimir QR
const printQr = () => {
    if (!qrUrl.value) return;
    
    const printWindow = window.open('', '_blank');
    if (printWindow) {
        const htmlContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>QR - ${props.student.nombres} ${props.student.apellidos}</title>
                <style>
                    body {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        min-height: 100vh;
                        margin: 0;
                        font-family: Arial, sans-serif;
                    }
                    img {
                        max-width: 400px;
                        margin: 20px;
                    }
                    .info {
                        text-align: center;
                        margin: 20px;
                    }
                    h2 {
                        margin: 10px 0;
                    }
                    @media print {
                        body {
                            padding: 20px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="info">
                    <h2>${props.student.nombres} ${props.student.apellidos}</h2>
                    <p>Código: ${props.student.codigo_estudiante || props.student.id}</p>
                    <p>${props.student.curso?.nombre || ''} "${props.student.paralelo?.nombre || ''}"</p>
                </div>
                <img src="${qrUrl.value}" alt="QR Code" />
            </body>
            </html>
        `;
        printWindow.document.write(htmlContent);
        printWindow.document.close();
        printWindow.onload = () => {
            setTimeout(() => {
                printWindow.print();
            }, 500);
        };
    }
};

// Cargar QR al montar
loadQr();
</script>