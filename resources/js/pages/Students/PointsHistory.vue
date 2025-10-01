<template>
    <StudentLayout :student="student">
        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-800 dark:via-indigo-800 dark:to-purple-800 px-6 py-12 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-7xl text-center">
                <h1 class="text-4xl font-bold text-white sm:text-5xl">Historial de Puntos</h1>
                <p class="mt-4 text-xl text-blue-100">{{ student.nombres }} {{ student.apellidos }}</p>
                <div class="mt-6">
                    <div class="text-5xl font-bold text-white">{{ totalPoints }}</div>
                    <div class="text-lg text-blue-100">Puntos Totales Acumulados</div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="px-6 py-8 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-7xl space-y-8">
                <!-- Estado vacío general -->
                <div v-if="(!deposits || deposits.length === 0) && (!periods || periods.length === 0)" class="text-center py-16">
                    <div class="mx-auto max-w-md">
                        <Leaf class="mx-auto h-16 w-16 text-gray-400 mb-4" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No hay datos disponibles</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            Aún no tienes depósitos registrados o no hay períodos académicos configurados.
                        </p>
                        <Button @click="router.visit(route('students.dashboard'))" class="bg-green-600 hover:bg-green-700">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver al Dashboard
                        </Button>
                    </div>
                </div>

                <!-- Contenido normal -->
                <div v-else>

                <!-- Points Summary by Bimester -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <Card
                        v-for="bimester in bimesters"
                        :key="bimester.number"
                        class="cursor-pointer border-green-200 dark:border-green-700 transition-shadow hover:shadow-lg"
                        :class="selectedBimester === bimester.number ? 'ring-2 ring-green-500' : ''"
                        @click="selectedBimester = bimester.number"
                    >
                        <CardContent class="p-6">
                            <div class="text-center">
                                <div class="mb-2">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                                        <span class="text-lg font-bold text-green-600">{{ bimester.number }}</span>
                                    </div>
                                </div>
                                <h3 class="font-semibold text-green-800 dark:text-green-300">{{ bimester.name }}</h3>
                                <div class="mt-2">
                                    <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                                        {{ getBimesterPoints(bimester.number) }}
                                    </div>
                                    <div class="text-sm text-green-600 dark:text-green-400">puntos</div>
                                </div>
                                <div class="mt-2">
                                    <div class="text-muted-foreground text-sm">{{ getBimesterDeposits(bimester.number) }} depósitos</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Detailed View -->
                <Card class="border-green-200 dark:border-green-700" v-if="selectedBimester">
                    <CardHeader>
                        <CardTitle class="flex items-center text-green-800 dark:text-green-300">
                            <BarChart3 class="mr-2 h-5 w-5" />
                            Detalle del {{ getBimesterName(selectedBimester) }}
                        </CardTitle>
                        <CardDescription> Depósitos realizados en este bimestre </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <!-- Summary Stats -->
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="rounded-lg bg-green-50 dark:bg-green-900/50 p-4 text-center">
                                    <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                                        {{ getBimesterPoints(selectedBimester) }}
                                    </div>
                                    <div class="text-sm text-green-600 dark:text-green-400">Puntos Totales</div>
                                </div>
                                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/50 p-4 text-center">
                                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                        {{ getBimesterDeposits(selectedBimester) }}
                                    </div>
                                    <div class="text-sm text-blue-600 dark:text-blue-400">Depósitos</div>
                                </div>
                                <div class="rounded-lg bg-purple-50 dark:bg-purple-900/50 p-4 text-center">
                                    <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ getBimesterWeight(selectedBimester) }}kg</div>
                                    <div class="text-sm text-purple-600 dark:text-purple-400">Peso Total</div>
                                </div>
                            </div>

                            <!-- Deposits List -->
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">Depósitos Realizados</h4>
                                <div class="space-y-2">
                                    <div
                                        v-for="deposit in getFilteredDeposits(selectedBimester)"
                                        :key="deposit.id"
                                        class="flex items-center justify-between rounded-lg border border-green-100 dark:border-green-700 bg-green-50/50 dark:bg-green-900/30 p-4 transition-all cursor-pointer hover:bg-green-100 dark:hover:bg-green-800/50 hover:shadow-md hover:scale-[1.02]"
                                        @click="showDepositDetails(deposit)"
                                    >
                                        <div class="flex items-center space-x-4">
                                            <div class="rounded-full bg-green-100 p-2">
                                                <Trash2 class="h-4 w-4 text-green-600" />
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ deposit.tipo_basura?.nombre }}
                                                </p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ formatDate(deposit.fecha_deposito) }}
                                                </p>
                                                <p v-if="deposit.basurero?.nombre" class="text-xs text-blue-600 dark:text-blue-400">
                                                    📍 {{ deposit.basurero.nombre }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-green-600">+{{ deposit.puntaje_obtenido }} pts</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ deposit.cantidad }}kg</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500">Ver detalles →</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="getFilteredDeposits(selectedBimester).length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    <Leaf class="mx-auto mb-4 h-12 w-12 opacity-50" />
                                    <p class="dark:text-gray-400">No hay depósitos registrados en este bimestre</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Progress Chart -->
                <Card class="border-green-200 dark:border-green-700">
                    <CardHeader>
                        <CardTitle class="flex items-center text-green-800 dark:text-green-300">
                            <TrendingUp class="mr-2 h-5 w-5" />
                            Progreso por Bimestre
                        </CardTitle>
                        <CardDescription> Tu evolución a lo largo del año académico </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="bimester in bimesters" :key="bimester.number" class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium">{{ bimester.name }}</span>
                                    <span class="text-muted-foreground"> {{ getBimesterPoints(bimester.number) }} puntos </span>
                                </div>
                                <Progress :value="getProgressPercentage(bimester.number)" class="h-2" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Achievements -->
                <Card class="border-yellow-200 dark:border-yellow-700 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/50 dark:to-orange-900/50">
                    <CardHeader>
                        <CardTitle class="flex items-center text-yellow-800 dark:text-yellow-300">
                            <Award class="mr-2 h-5 w-5" />
                            Logros Ecológicos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="achievement in achievements"
                                :key="achievement.id"
                                class="flex items-center space-x-3 rounded-lg border border-yellow-200 dark:border-yellow-600 bg-white dark:bg-gray-800 p-4"
                                :class="achievement.earned ? 'opacity-100' : 'opacity-50'"
                            >
                                <div class="text-2xl">{{ achievement.icon }}</div>
                                <div>
                                    <p class="font-medium" :class="achievement.earned ? 'text-yellow-800 dark:text-yellow-300' : 'text-gray-500 dark:text-gray-400'">
                                        {{ achievement.name }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ achievement.description }}</p>
                                </div>
                                <div v-if="achievement.earned" class="ml-auto">
                                    <CheckCircle class="h-5 w-5 text-green-500" />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                </div> <!-- Cierre del div v-else -->
            </div>
        </div>

        <!-- Modal Flotante SIN Fondo Oscuro -->
        <div v-if="selectedDeposit" class="fixed top-20 right-8 z-50 w-96 max-h-[80vh] overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-green-200 dark:border-green-700 transform transition-all duration-300">
                <div class="p-6">
                    <!-- Header del Modal -->
                    <div class="flex items-center justify-between mb-6 border-b border-green-200 pb-4">
                        <h3 class="text-xl font-bold text-green-800 dark:text-green-300">Detalles del Depósito</h3>
                        <button @click="selectedDeposit = null" class="text-green-400 dark:text-green-500 hover:text-green-600 dark:hover:text-green-400 transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Información Principal -->
                        <Card class="border-green-200 dark:border-green-700">
                            <CardHeader class="pb-3">
                                <CardTitle class="text-green-800 dark:text-green-300 text-sm">📅 Información del Depósito</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Fecha:</span>
                                    <span class="font-medium dark:text-gray-200">{{ formatDate(selectedDeposit.fecha_deposito) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Hora:</span>
                                    <span class="font-medium dark:text-gray-200">{{ formatTime(selectedDeposit.fecha_deposito) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Cantidad:</span>
                                    <span class="font-medium dark:text-gray-200">{{ selectedDeposit.cantidad }} kg</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Bimestre:</span>
                                    <span class="font-medium dark:text-gray-200">{{ selectedDeposit.bimestre }}°</span>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Tipo de Residuo -->
                        <Card class="border-green-200 dark:border-green-700">
                            <CardHeader class="pb-3">
                                <CardTitle class="text-green-800 dark:text-green-300 text-sm">🗑️ Tipo de Residuo</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Tipo:</span>
                                    <span class="font-medium dark:text-gray-200">{{ selectedDeposit.tipo_basura?.nombre }}</span>
                                </div>
                                <div v-if="selectedDeposit.tipo_basura?.descripcion">
                                    <span class="text-gray-600 dark:text-gray-400">Descripción:</span>
                                    <p class="font-medium dark:text-gray-200 text-xs mt-1">{{ selectedDeposit.tipo_basura?.descripcion }}</p>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Puntos Base:</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">{{ selectedDeposit.tipo_basura?.puntos_base }}</span>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Información del Basurero -->
                        <Card class="border-green-200 dark:border-green-700">
                            <CardHeader class="pb-3">
                                <CardTitle class="text-green-800 dark:text-green-300 text-sm">📍 Basurero</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Nombre:</span>
                                    <span class="font-medium dark:text-gray-200">{{ selectedDeposit.basurero?.nombre }}</span>
                                </div>
                                <div v-if="selectedDeposit.basurero?.ubicacion">
                                    <span class="text-gray-600 dark:text-gray-400">Ubicación:</span>
                                    <p class="font-medium dark:text-gray-200 text-xs mt-1">{{ selectedDeposit.basurero?.ubicacion }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Resumen de Puntos -->
                        <Card class="border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/30">
                            <CardHeader class="pb-3">
                                <CardTitle class="text-green-800 dark:text-green-300 text-sm">🏆 Resumen</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ selectedDeposit.puntaje_obtenido }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Puntos Obtenidos</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-2">{{ selectedDeposit.periodo?.nombre || 'Sin período' }}</div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>

    </StudentLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { router } from '@inertiajs/vue3';
import { ArrowLeft, Award, Calendar, CheckCircle, MapPin, Trash2, X } from 'lucide-vue-next';
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
        bimestre: number;
        periodo_id?: number;
        tipo_basura?: {
            id?: number;
            nombre: string;
            descripcion?: string;
            puntos_base?: number;
        };
        basurero?: {
            id?: number;
            nombre: string;
            ubicacion?: string;
            descripcion?: string;
        };
        periodo?: {
            nombre: string;
        };
    }>;
    periods: Array<{
        id: number;
        nombre: string;
    }>;
    totalPoints: number;
    pointsByPeriod?: Record<number, {
        periodo_id: number;
        periodo_nombre: string;
        puntos: number;
        fecha_asignacion?: string;
        comentario?: string;
    }>;
}

const props = defineProps<Props>();

const selectedBimester = ref(1);
const selectedDeposit = ref<any>(null);

const bimesters = [
    { number: 1, name: 'Primer Bimestre' },
    { number: 2, name: 'Segundo Bimestre' },
    { number: 3, name: 'Tercer Bimestre' },
    { number: 4, name: 'Cuarto Bimestre' },
];

const achievements = ref([
    {
        id: 1,
        name: 'Primer Reciclador',
        description: 'Realizaste tu primer depósito',
        icon: '🌱',
        earned: computed(() => props.deposits && props.deposits.length > 0),
    },
    {
        id: 2,
        name: 'Eco Guerrero',
        description: 'Alcanzaste 100 puntos',
        icon: '⚡',
        earned: computed(() => props.totalPoints >= 100),
    },
    {
        id: 3,
        name: 'Guardián Verde',
        description: 'Realizaste 10 depósitos',
        icon: '🛡️',
        earned: computed(() => props.deposits && props.deposits.length >= 10),
    },
    {
        id: 4,
        name: 'Campeón Ecológico',
        description: 'Alcanzaste 500 puntos',
        icon: '🏆',
        earned: computed(() => props.totalPoints >= 500),
    },
    {
        id: 5,
        name: 'Héroe del Planeta',
        description: 'Completaste todos los bimestres',
        icon: '🌍',
        earned: computed(() => bimesters.every((b) => getBimesterPoints(b.number) > 0)),
    },
    {
        id: 6,
        name: 'Reciclador Constante',
        description: 'Depósitos en todos los bimestres',
        icon: '♻️',
        earned: computed(() => bimesters.every((b) => getBimesterDeposits(b.number) > 0)),
    },
]);

const getBimesterPoints = (bimester: number) => {
    if (!props.pointsByPeriod) return 0;
    
    // Buscar el período que corresponde al bimestre
    const bimesterNames = ['primer', 'segundo', 'tercer', 'cuarto'];
    const bimesterName = bimesterNames[bimester - 1];
    
    // Buscar en todos los períodos el que corresponde al bimestre
    for (const periodData of Object.values(props.pointsByPeriod)) {
        const periodName = periodData.periodo_nombre.toLowerCase();
        if (periodName.includes(bimesterName)) {
            return periodData.puntos;
        }
    }
    
    return 0;
};

// Función auxiliar para mostrar detalles del depósito
const showDepositDetails = (deposit: any) => {
    selectedDeposit.value = deposit;
};

const getBimesterDeposits = (bimester: number) => {
    if (!props.deposits || props.deposits.length === 0) return 0;
    return props.deposits.filter((d) => d.bimestre === bimester).length;
};

const getBimesterWeight = (bimester: number) => {
    if (!props.deposits || props.deposits.length === 0) return '0.0';
    return props.deposits
        .filter((d) => d.bimestre === bimester)
        .reduce((sum, d) => sum + d.cantidad, 0)
        .toFixed(1);
};

const getBimesterName = (bimester: number) => {
    return bimesters.find((b) => b.number === bimester)?.name || '';
};

const getFilteredDeposits = (bimester: number) => {
    if (!props.deposits || props.deposits.length === 0) return [];
    return props.deposits
        .filter((d) => d.bimestre === bimester)
        .sort((a, b) => new Date(b.fecha_deposito).getTime() - new Date(a.fecha_deposito).getTime());
};

const getProgressPercentage = (bimester: number) => {
    if (!props.deposits || props.deposits.length === 0) return 0;
    const maxPoints = Math.max(...bimesters.map((b) => getBimesterPoints(b.number)));
    const bimesterPoints = getBimesterPoints(bimester);
    return maxPoints > 0 ? (bimesterPoints / maxPoints) * 100 : 0;
};

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

const formatTime = (dateString: string) => {
    try {
        return new Date(dateString).toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch (error) {
        console.error('Error formatting time:', dateString, error);
        return 'Hora inválida';
    }
};
</script>