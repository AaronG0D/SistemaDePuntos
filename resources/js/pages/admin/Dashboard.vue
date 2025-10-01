<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Bar } from 'vue-chartjs'
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    Colors
} from 'chart.js'
import {
    ArrowDown, ArrowUp, Award, Book, Building, Calendar, Star, Users, TrendingUp
} from 'lucide-vue-next';
import { computed } from 'vue';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Colors)

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
}

const props = defineProps<{
    estadisticas: Estadisticas;
}>();

const formatNumber = (num: number) => new Intl.NumberFormat('es-ES').format(num);

const chartData = computed(() => ({
    labels: props.estadisticas.ranking_curso.map(c => c.curso),
    datasets: [
        {
            label: 'Puntos por Curso',
            backgroundColor: '#4ade80', // green-400
            borderColor: '#22c55e', // green-500
            borderWidth: 1,
            data: props.estadisticas.ranking_curso.map(c => c.puntos),
        },
    ],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: 'hsl(var(--background))',
            titleColor: 'hsl(var(--foreground))',
            bodyColor: 'hsl(var(--foreground))',
            borderColor: 'hsl(var(--border))',
            borderWidth: 1,
        },
    },
    scales: {
        x: {
            ticks: {
                color: 'hsl(var(--muted-foreground))',
            },
            grid: {
                display: false,
            },
        },
        y: {
            ticks: {
                color: 'hsl(var(--muted-foreground))',
            },
            grid: {
                color: 'hsl(var(--border))',
            },
        },
    },
}));

</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="space-y-8">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card class="bg-primary/5 dark:bg-primary/10">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Puntos del Mes</CardTitle>
                        <Star class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-primary">{{ formatNumber(props.estadisticas.puntos_mes) }}</div>
                        <p class="text-xs text-primary/80">Total de puntos generados en el mes</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Depósitos del Mes</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatNumber(props.estadisticas.depositos_mes) }}</div>
                        <p class="text-xs text-muted-foreground">{{ formatNumber(props.estadisticas.depositos_hoy) }} depósitos hoy</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Variación Mensual</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div :class="[
                            'text-2xl font-bold',
                            props.estadisticas.variacion_mes >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                        ]">
                            <component :is="props.estadisticas.variacion_mes >= 0 ? ArrowUp : ArrowDown" class="inline h-5 w-5" />
                            {{ props.estadisticas.variacion_mes }}%
                        </div>
                        <p class="text-xs text-muted-foreground">Comparado con el mes anterior</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Puntos de la Semana</CardTitle>
                        <Star class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatNumber(props.estadisticas.puntos_semana) }}</div>
                        <p class="text-xs text-muted-foreground">{{ formatNumber(props.estadisticas.puntos_hoy) }} puntos hoy</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
                <Card class="lg:col-span-2">
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
                                <div v-for="(est, i) in props.estadisticas.top_estudiantes" :key="i" class="flex items-center gap-4">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted font-bold text-muted-foreground">{{ i + 1 }}</div>
                                    <div class="flex-1">
                                        <p class="truncate font-medium">{{ est.nombre }}</p>
                                    </div>
                                    <Badge variant="secondary" class="shrink-0">{{ formatNumber(est.puntos) }} pts</Badge>
                                </div>
                            </div>
                        </ScrollArea>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-3">
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
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Users class="h-5 w-5 text-purple-500" />
                        Ranking por Paralelo
                    </CardTitle>
                    <CardDescription>Paralelos con más puntos en total.</CardDescription>
                </CardHeader>
                <CardContent>
                    <ScrollArea class="h-[300px]">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                            <div v-for="(paralelo, i) in props.estadisticas.ranking_paralelo" :key="i" class="flex items-center gap-4 rounded-lg border p-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted font-bold text-muted-foreground">{{ i + 1 }}</div>
                                <div class="flex-1">
                                    <p class="truncate font-medium">{{ paralelo.paralelo }}</p>
                                    <Badge variant="outline" class="mt-1">{{ formatNumber(paralelo.puntos) }} pts</Badge>
                                </div>
                            </div>
                        </div>
                    </ScrollArea>
                </CardContent>
                <CardFooter class="border-t px-6 py-4">
                    <Link href="#" class="text-xs font-medium text-muted-foreground hover:text-primary">Ver todos los paralelos</Link>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>