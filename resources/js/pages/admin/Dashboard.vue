<script setup lang="ts">
import DepositosCharts from '@/components/charts/DepositosChart.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { BarElement, CategoryScale, Chart as ChartJS, Colors, Legend, LinearScale, Title, Tooltip } from 'chart.js';
import { ArrowDown, ArrowUp, Award, Building, Calendar, LayoutGrid, Recycle, Star, TrendingUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Bar } from 'vue-chartjs';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Colors);

interface Estadisticas {
    depositos_hoy: number;
    depositos_semana: number;
    depositos_mes: number;
    puntos_hoy: number;
    puntos_semana: number;
    puntos_mes: number;
    variacion_mes: number;
    top_estudiantes: Array<{ nombre: string; puntos: number }>;
    ranking_curso: Array<{ curso: string; puntos: number }>;
    ranking_paralelo: Array<{ paralelo: string; puntos: number }>;
    depositos_por_tipo?: Array<{ tipo: string; cantidad: number }>;
}

const props = defineProps<{
    estadisticas: Estadisticas;
}>();

const formatNumber = (num: number) => new Intl.NumberFormat('es-ES').format(num);

// Estado para filtro de tipos de basura
const filtrosBasura = ref<Set<string>>(new Set());

const toggleFiltroBasura = (tipo: string) => {
    if (filtrosBasura.value.has(tipo)) {
        filtrosBasura.value.delete(tipo);
    } else {
        filtrosBasura.value.add(tipo);
    }
};

const cssVar = (name: string) => {
    if (typeof window === 'undefined') return '';
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
};

const chartData = computed(() => {
    const bgColor =  '#4ade80'; // Valor predeterminado: verde
    const borderColor =  '#22c55e'; // Valor predeterminado: verde oscuro

    return {
        labels: props.estadisticas.ranking_curso.map((c) => c.curso),
        datasets: [
            {
                label: 'Puntos por Curso',
                backgroundColor: bgColor,
                borderColor: borderColor,
                borderWidth: 1,
                data: props.estadisticas.ranking_curso.map((c) => c.puntos),
            },
        ],
    };
});

const chartOptions = computed(() => {
    const bg = cssVar('--background') || '#ffffff'; // Blanco por defecto
    const fg = cssVar('--foreground') || '#000000'; // Negro por defecto
    const border = cssVar('--border') || '#e5e7eb'; // Gris claro por defecto
    const muted = cssVar('--muted-foreground') || '#6b7280'; // Gris oscuro por defecto

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
                labels: { color: fg },
            },
            tooltip: {
                backgroundColor: bg,
                titleColor: fg,
                bodyColor: fg,
                borderColor: border,
                borderWidth: 1,
                intersect: false,
                mode: 'index' as 'index',
            },
        },
        scales: {
            x: {
                ticks: {
                    color: muted,
                },
                grid: {
                    display: false,
                },
            },
            y: {
                ticks: {
                    color: muted,
                },
                grid: {
                    color: border,
                    borderColor: border,
                },
                border: {
                    color: border,
                },
            },
        },
    };
});

const depositTypeChartData = computed(() => {
    let labels = props.estadisticas.depositos_por_tipo?.map((t) => t.tipo) ?? [];
    let data = props.estadisticas.depositos_por_tipo?.map((t) => t.cantidad) ?? [];

    // Filtrar según los tipos desactivados
    const filteredLabels: string[] = [];
    const filteredData: number[] = [];

    labels.forEach((label, index) => {
        const normalize = (s: string) =>
            s
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();

        if (!filtrosBasura.value.has(normalize(label))) {
            filteredLabels.push(label);
            filteredData.push(data[index]);
        }
    });

    const colorMap: Record<string, string> = {
        papel: '#f97316', // orange-500
        plastico: '#3b82f6', // blue-500
        metal: '#ef4444', // red-500
        bio: '#22c55e', // green-500
        default: '#22c55e', // gray-500
    };

    const normalize = (s: string) =>
        s
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();

    const backgroundColors = filteredLabels.map((label) => colorMap[normalize(label)] || colorMap.default);

    const datasets = [
        {
            label: 'Depósitos por tipo',
            data: filteredData,
            backgroundColor: backgroundColors,
            borderColor: '#1f2937',
            borderWidth: 2,
        },
    ];

    return {
        labels: filteredLabels,
        datasets,
    };
});

const depositTypeOptions = computed(() => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: undefined,
        plugins: {
            legend: {
                position: 'bottom' as const,
                labels: {
                    color: '#d1d5db',
                    padding: 15,
                    font: {
                        size: 12,
                        weight: 'normal' as const,
                    },
                    usePointStyle: true,
                    pointStyle: 'circle',
                },
            },
            tooltip: {
                enabled: true,
                callbacks: {
                    label: function (context: any) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a: number, b: number) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return `${label}: ${value} (${percentage}%)`;
                    },
                },
                backgroundColor: '#111827',
                titleColor: '#F9FAFB',
                bodyColor: '#F9FAFB',
                borderColor: '#374151',
                borderWidth: 1,
                padding: 12,
                displayColors: true,
            },
        },
    };
});
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="space-y-8">
            <!-- Título del Dashboard -->
            <div class="relative mb-8">
                <div
                    class="absolute inset-0 rounded-xl bg-gradient-to-r from-green-100/40 via-emerald-100/30 to-teal-100/40 blur-xl dark:from-green-900/20 dark:via-emerald-900/20 dark:to-teal-900/20"
                />
                <div class="relative">
                    <h1 class="text-foreground flex items-center gap-3 text-3xl font-bold">
                        <LayoutGrid class="text-primary h-8 w-8" />
                        Dashboard Administrativo
                    </h1>
                    <p class="text-muted-foreground mt-2">Panel de control y estadísticas del sistema de puntos</p>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card class="bg-primary/5 dark:bg-primary/10 hover:bg-primary/10 transition hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Puntos del Mes</CardTitle>
                        <Star class="text-primary h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-primary text-2xl font-bold">{{ formatNumber(props.estadisticas.puntos_mes) }}</div>
                        <p class="text-primary/80 text-xs">Total de puntos generados en el mes</p>
                    </CardContent>
                </Card>
                <Card class="hover:bg-muted/30 transition hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Depósitos del Mes</CardTitle>
                        <Calendar class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatNumber(props.estadisticas.depositos_mes) }}</div>
                        <p class="text-muted-foreground text-xs">{{ formatNumber(props.estadisticas.depositos_hoy) }} depósitos hoy</p>
                    </CardContent>
                </Card>
                <Card class="hover:bg-muted/30 transition hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Variación Mensual</CardTitle>
                        <TrendingUp class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div
                            :class="[
                                'text-2xl font-bold',
                                props.estadisticas.variacion_mes >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400',
                            ]"
                        >
                            <component :is="props.estadisticas.variacion_mes >= 0 ? ArrowUp : ArrowDown" class="inline h-5 w-5" />
                            {{ props.estadisticas.variacion_mes }}%
                        </div>
                        <p class="text-muted-foreground text-xs">Comparado con el mes anterior</p>
                    </CardContent>
                </Card>
                <Card class="hover:bg-muted/30 transition hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Puntos de la Semana</CardTitle>
                        <Star class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatNumber(props.estadisticas.puntos_semana) }}</div>
                        <p class="text-muted-foreground text-xs">{{ formatNumber(props.estadisticas.puntos_hoy) }} puntos hoy</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
                <Card class="transition hover:shadow-md lg:col-span-3">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Recycle class="h-5 w-5 text-green-500" />
                            Depósitos por Tipo de Basura
                        </CardTitle>
                        <CardDescription>Distribución de depósitos por tipo (mes actual). Haz clic para activar/desactivar tipos.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Filtros de tipos de basura -->
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="tipo in props.estadisticas.depositos_por_tipo"
                                :key="tipo.tipo"
                                @click="
                                    toggleFiltroBasura(
                                        tipo.tipo
                                            .normalize('NFD')
                                            .replace(/[\u0300-\u036f]/g, '')
                                            .toLowerCase(),
                                    )
                                "
                                :class="[
                                    'rounded-full px-3 py-1 text-sm font-medium transition',
                                    filtrosBasura.has(
                                        tipo.tipo
                                            .normalize('NFD')
                                            .replace(/[\u0300-\u036f]/g, '')
                                            .toLowerCase(),
                                    )
                                        ? 'bg-gray-600 text-gray-300 opacity-50'
                                        : 'bg-gray-700 text-white hover:bg-gray-600',
                                ]"
                            >
                                {{ tipo.tipo }} ({{ tipo.cantidad }})
                            </button>
                        </div>
                        <!-- Gráfico -->
                        <div class="h-[300px]">
                            <template v-if="depositTypeChartData.labels && depositTypeChartData.labels.length > 0">
                                <DepositosCharts :data="depositTypeChartData" type="doughnut" :options="depositTypeOptions" />
                            </template>
                            <template v-else>
                                <div class="text-muted-foreground flex h-full items-center justify-center">
                                    <p>Todos los tipos han sido desactivados</p>
                                </div>
                            </template>
                        </div>
                    </CardContent>
                </Card>

                <Card class="transition hover:shadow-md lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Award class="h-5 w-5 text-yellow-500" />
                            Top 10 Estudiantes
                        </CardTitle>
                        <CardDescription>Ranking de los mejores estudiantes por puntos.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ScrollArea class="h-[350px]">
                            <div class="space-y-4">
                                <div
                                    v-for="(est, i) in props.estadisticas.top_estudiantes"
                                    :key="i"
                                    class="hover:bg-muted/40 flex items-center gap-4 rounded-lg border p-3 transition"
                                >
                                    <div class="bg-muted text-muted-foreground flex h-8 w-8 items-center justify-center rounded-full font-bold">
                                        {{ i + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <p class="truncate font-medium">{{ est.nombre }}</p>
                                    </div>
                                    <Badge variant="secondary" class="shrink-0">{{ formatNumber(est.puntos) }} pts</Badge>
                                </div>
                            </div>
                        </ScrollArea>
                    </CardContent>
                </Card>
            </div>

            <!-- Ranking por Curso -->
            <Card class="transition hover:shadow-md">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Building class="h-5 w-5 text-blue-500" />
                        Ranking por Curso
                    </CardTitle>
                    <CardDescription>Cursos con el mayor puntaje acumulado.</CardDescription>
                </CardHeader>
                <CardContent class="h-[350px] pr-6">
                    <Bar :data="chartData" :options="chartOptions" />
                </CardContent>
            </Card>

            <!-- Eliminado: Ranking por Paralelo -->
            <!-- Se ha removido la sección de "Ranking por Paralelo" a solicitud -->
        </div>
    </AppLayout>
</template>
