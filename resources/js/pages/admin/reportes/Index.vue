<template>
    <Head title="Reportes" />
    <AppLayout>
        <div class="container mx-auto py-6">
            <div class="mb-8">
                <h1 class="flex items-center gap-3 text-3xl font-bold">
                    <FileText class="h-8 w-8 text-emerald-600" />
                    Reportes de Gestión de Residuos
                </h1>
                <p class="text-muted-foreground mt-2">Estadísticas y reportes del sistema de gestión de residuos</p>
            </div>

            <!-- Panel de Filtros -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon name="filter" class="h-5 w-5" />
                        Configuración del Reporte
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Selección de tipo de reporte -->
                    <div class="mb-6">
                        <label class="mb-2 block font-semibold">Tipo de Reporte</label>
                        <Select v-model="tipoReporte">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Seleccionar tipo de reporte" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="depositos">Depósitos por Tipo de Basura</SelectItem>
                                <SelectItem value="ranking">Ranking por Periodo y Tipo de Basura</SelectItem>
                                <SelectItem value="basurero">Depósitos por Basurero</SelectItem>
                                <SelectItem value="fecha">Depósitos por Fecha</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filtros dinámicos -->
                    <div v-if="tipoReporte === 'depositos'" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Tipo de Residuo</label>
                                <Select v-model="filtros.tipo_residuo_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar tipo de residuo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todos los tipos</SelectItem>
                                        <SelectItem v-for="tipo in tiposResiduos" :key="tipo.id" :value="tipo.id">
                                            {{ tipo.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Fecha inicio</label>
                                <Input type="date" v-model="filtros.fecha_inicio" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Fecha fin</label>
                                <Input type="date" v-model="filtros.fecha_fin" />
                            </div>
                        </div>
                    </div>

                    <div v-else-if="tipoReporte === 'ranking'" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Periodo</label>
                                <Select v-model="filtros.periodo">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar período" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="semana">Esta Semana</SelectItem>
                                        <SelectItem value="mes">Este Mes</SelectItem>
                                        <SelectItem value="anio">Este Año</SelectItem>
                                        <SelectItem value="todo">Todo el Tiempo</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Tipo de Residuo</label>
                                <Select v-model="filtros.tipo_residuo_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar tipo de residuo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todos los tipos</SelectItem>
                                        <SelectItem v-for="tipo in tiposResiduos" :key="tipo.id" :value="tipo.id">
                                            {{ tipo.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="tipoReporte === 'basurero'" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Basurero</label>
                                <Select v-model="filtros.basurero_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar basurero" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="basurero in basureros" :key="basurero.id" :value="basurero.id">
                                            {{ basurero.ubicacion }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Fecha inicio</label>
                                <Input type="date" v-model="filtros.fecha_inicio" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Fecha fin</label>
                                <Input type="date" v-model="filtros.fecha_fin" />
                            </div>
                        </div>
                    </div>

                    <div v-else-if="tipoReporte === 'fecha'" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Fecha inicio</label>
                                <Input type="date" v-model="filtros.fecha_inicio" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Fecha fin</label>
                                <Input type="date" v-model="filtros.fecha_fin" />
                            </div>
                        </div>
                    </div>

                    <!-- Botones de exportación -->
                    <div class="mt-6 flex gap-3">
                        <Button @click="exportarPDF" :disabled="loading" class="flex items-center gap-2 bg-red-600 text-white hover:bg-red-700">
                            <FileText class="mr-2 h-4 w-4" />
                            <span v-if="!loading">Exportar PDF</span>
                            <span v-else>Cargando...</span>
                        </Button>
                        <Button
                            v-if="tipoReporte === 'depositos'"
                            @click="exportarExcel"
                            :disabled="loading"
                            class="flex items-center gap-2 bg-green-600 text-white hover:bg-green-700"
                        >
                            <Table2 class="mr-2 h-4 w-4" />
                            <span v-if="!loading">Exportar Excel</span>
                            <span v-else>Cargando...</span>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Período del Reporte -->
            <PeriodoDisplay :tipo-reporte="tipoReporte" :filtros="filtros" :tipos-residuos="tiposResiduos" :basureros="basureros" />

            <!-- Estadísticas Generales -->
            <div class="mb-8 grid gap-4 md:grid-cols-4">
                <Card class="border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium text-blue-900">
                            <Icon name="package" class="h-4 w-4" />
                            Total Depósitos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-900">{{ estadisticas.total_depositos.toLocaleString() }}</div>
                        <p class="mt-1 text-xs text-blue-700">Registros totales</p>
                    </CardContent>
                </Card>

                <Card class="border-green-200 bg-gradient-to-br from-green-50 to-green-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium text-green-900">
                            <Icon name="star" class="h-4 w-4" />
                            Total Puntos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-900">{{ estadisticas.total_puntos.toLocaleString() }}</div>
                        <p class="mt-1 text-xs text-green-700">Puntos acumulados</p>
                    </CardContent>
                </Card>

                <Card class="border-purple-200 bg-gradient-to-br from-purple-50 to-purple-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium text-purple-900">
                            <Icon name="recycle" class="h-4 w-4" />
                            Tipos de Residuos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-purple-900">{{ estadisticas.total_tipos_residuos }}</div>
                        <p class="mt-1 text-xs text-purple-700">Categorías activas</p>
                    </CardContent>
                </Card>

                <Card class="border-orange-200 bg-gradient-to-br from-orange-50 to-orange-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium text-orange-900">
                            <Icon name="trash-2" class="h-4 w-4" />
                            Basureros Activos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-900">{{ estadisticas.total_basureros }}</div>
                        <p class="mt-1 text-xs text-orange-700">Ubicaciones activas</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Gráficos Estadísticos -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Gráfico de Depósitos por Tipo -->
                <Card class="transition hover:shadow-md">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="bar-chart-3" class="h-5 w-5" />
                            Depósitos por Tipo de Residuo
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64 w-full overflow-hidden">
                            <DepositosChart
                                v-if="datosGraficos?.porTipo?.labels?.length"
                                :data="{
                                    labels: datosGraficos.porTipo.labels || [],
                                    datasets: (datosGraficos.porTipo.datasets || []).map((dataset) => {
                                        const colorMap: Record<string, string> = {
                                            papel: '#f97316',
                                            plastico: '#3b82f6',
                                            metal: '#ef4444',
                                            biodegradable: '#22c55e',
                                            default: '#9ca3af',
                                        };
                                        const labels = datosGraficos.porTipo.labels || [];
                                        const normalize = (s: string) =>
                                            s
                                                .normalize('NFD')
                                                .replace(/[\u0300-\u036f]/g, '')
                                                .toLowerCase();
                                        const backgroundColors = labels.map((label) => colorMap[normalize(label)] || colorMap.default);

                                        return {
                                            label: dataset.label || 'Sin etiqueta',
                                            data: dataset.data || [],
                                            backgroundColor: backgroundColors,
                                            borderColor: '#4b5563',
                                            borderWidth: 2,
                                        };
                                    }),
                                }"
                                type="doughnut"
                                :options="{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        title: {
                                            display: false,
                                        },
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                boxWidth: 12,
                                                padding: 10,
                                                usePointStyle: true,
                                                color: '#d1d5db',
                                            },
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function (context: { label: string; raw: number }) {
                                                    return `${context.label}: ${context.raw}`;
                                                },
                                            },
                                            backgroundColor: getThemeColors().tooltipBg,
                                            titleColor: getThemeColors().tooltipText,
                                            bodyColor: getThemeColors().tooltipText,
                                            borderColor: getThemeColors().tooltipBorder,
                                            borderWidth: 1,
                                        },
                                    },
                                }"
                            />
                            <div v-else class="text-muted-foreground flex h-full items-center justify-center">
                                <p>No hay datos disponibles para mostrar</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Gráfico de Depósitos por Mes -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="trending-up" class="h-5 w-5" />
                            Tendencia de Depósitos (Últimos 6 meses)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64 w-full overflow-hidden">
                            <DepositosChart
                                v-if="datosGraficos?.porMes?.labels?.length"
                                :data="{
                                    labels: datosGraficos.porMes.labels || [],
                                    datasets: (datosGraficos.porMes.datasets || []).map((dataset) => ({
                                        label: dataset.label || 'Depósitos',
                                        data: dataset.data || [],
                                        borderColor: dataset.borderColor || '#1E40AF',
                                        borderWidth: dataset.borderWidth || 2,
                                        fill: false,
                                        tension: 0.1,
                                    })),
                                }"
                                type="line"
                                :options="{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        title: {
                                            display: false,
                                        },
                                        legend: {
                                            position: 'top',
                                            labels: {
                                                boxWidth: 12,
                                                padding: 10,
                                                usePointStyle: true,
                                                color: '#d1d5db',
                                            },
                                        },
                                        tooltip: {
                                            backgroundColor: '#111827',
                                            titleColor: '#F9FAFB',
                                            bodyColor: '#F9FAFB',
                                            borderColor: '#374151',
                                            borderWidth: 1,
                                        },
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1,
                                            },
                                        },
                                        x: {
                                            ticks: {
                                                maxRotation: 45,
                                            },
                                        },
                                    },
                                }"
                            />
                            <div v-else class="text-muted-foreground flex h-full items-center justify-center">
                                <p>No hay datos de tendencias disponibles</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Top 10 Usuarios -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="trophy" class="h-5 w-5" />
                            Top 10 Usuarios con Más Puntos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64 w-full overflow-hidden">
                            <DepositosChart
                                v-if="datosGraficos?.topUsuarios?.labels?.length"
                                :data="{
                                    labels: datosGraficos.topUsuarios.labels || [],
                                    datasets: (datosGraficos.topUsuarios.datasets || []).map((dataset) => ({
                                        label: dataset.label || 'Puntos',
                                        data: dataset.data || [],
                                        backgroundColor: [
                                            '#3B82F6',
                                            '#10B981',
                                            '#F59E0B',
                                            '#EF4444',
                                            '#8B5CF6',
                                            '#06B6D4',
                                            '#84CC16',
                                            '#F97316',
                                            '#EC4899',
                                            '#6366F1',
                                        ],
                                        borderColor: dataset.borderColor || '#1E40AF',
                                        borderWidth: dataset.borderWidth || 1,
                                    })),
                                }"
                                type="bar"
                                :options="{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        title: {
                                            display: false,
                                        },
                                        legend: {
                                            display: false,
                                        },
                                        tooltip: {
                                            backgroundColor: '#111827',
                                            titleColor: '#FFFFFF',
                                            bodyColor: '#F9FAFB',
                                            borderColor: '#374151',
                                            borderWidth: 1,
                                        },
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1,
                                            },
                                        },
                                        x: {
                                            ticks: {
                                                maxRotation: 45,
                                            },
                                        },
                                    },
                                    indexAxis: 'y',
                                }"
                            />
                            <div v-else class="text-muted-foreground flex h-full items-center justify-center">
                                <p>No hay datos de usuarios disponibles</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import PeriodoDisplay from '@/components/PeriodoDisplay.vue';
import DepositosChart from '@/components/charts/DepositosChart.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Basurero, DatosGraficos, Estadisticas, TipoResiduo } from '@/types';
import { FileText, Table2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from '../../../composables/useToast';

const props = withDefaults(
    defineProps<{
        estadisticas: Estadisticas;
        tiposResiduos?: TipoResiduo[];
        basureros?: Basurero[];
        datosGraficos: DatosGraficos;
    }>(),
    {
        tiposResiduos: () => [],
        basureros: () => [],
    },
);

const tipoReporte = ref('depositos');
const filtros = ref({
    tipo_residuo_id: undefined as number | string | undefined,
    fecha_inicio: undefined as string | undefined,
    fecha_fin: undefined as string | undefined,
    periodo: 'mes' as string,
    basurero_id: undefined as number | undefined,
});

const loading = ref(false);
const { toast } = useToast();

// Función para detectar modo oscuro/claro
const isDarkMode = () => {
    if (typeof window === 'undefined') return true;
    return document.documentElement.classList.contains('dark');
};

// Colores adaptables según el tema
const getThemeColors = () => {
    const dark = isDarkMode();
    return {
        tooltipBg: dark ? '#111827' : '#FFFFFF',
        tooltipText: dark ? '#F9FAFB' : '#1F2937',
        tooltipBorder: dark ? '#374151' : '#E5E7EB',
        legendText: dark ? '#d1d5db' : '#4B5563',
        borderColor: dark ? '#374151' : '#E5E7EB',
    };
};

const exportarPDF = async () => {
    let url = '';
    const params = new URLSearchParams();
    if (tipoReporte.value === 'depositos') {
        url = '/admin/reportes/depositos/pdf';
        if (filtros.value.tipo_residuo_id && filtros.value.tipo_residuo_id !== undefined && filtros.value.tipo_residuo_id !== 'todos') {
            params.append('tipo_residuo_id', filtros.value.tipo_residuo_id.toString());
        }
        if (filtros.value.fecha_inicio) params.append('fecha_inicio', filtros.value.fecha_inicio);
        if (filtros.value.fecha_fin) params.append('fecha_fin', filtros.value.fecha_fin);
    } else if (tipoReporte.value === 'ranking') {
        url = '/admin/reportes/ranking/pdf';
        if (filtros.value.periodo) params.append('periodo', filtros.value.periodo);
        if (filtros.value.tipo_residuo_id && filtros.value.tipo_residuo_id !== undefined && filtros.value.tipo_residuo_id !== 'todos') {
            params.append('tipo_residuo_id', filtros.value.tipo_residuo_id.toString());
        }
    } else if (tipoReporte.value === 'basurero') {
        url = '/admin/reportes/basurero/pdf';
        if (filtros.value.basurero_id) params.append('basurero_id', filtros.value.basurero_id.toString());
        if (filtros.value.fecha_inicio) params.append('fecha_inicio', filtros.value.fecha_inicio);
        if (filtros.value.fecha_fin) params.append('fecha_fin', filtros.value.fecha_fin);
    } else if (tipoReporte.value === 'fecha') {
        url = '/admin/reportes/fecha/pdf';
        if (filtros.value.fecha_inicio) params.append('fecha_inicio', filtros.value.fecha_inicio);
        if (filtros.value.fecha_fin) params.append('fecha_fin', filtros.value.fecha_fin);
    }
    try {
        loading.value = true;
        const response = await fetch(`${url}?${params.toString()}`, {
            method: 'GET',
            headers: {
                Accept: 'application/pdf',
            },
        });
        if (!response.ok) throw new Error('No se pudo generar el PDF');
        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = 'reporte.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(downloadUrl);
        toast({
            title: 'PDF generado',
            description: 'El reporte se descargó correctamente.',
        });
    } catch (e) {
        toast({
            title: 'Error al exportar',
            description: 'Ocurrió un problema al intentar exportar el PDF.',
            variant: 'destructive',
        });
    } finally {
        loading.value = false;
    }
};

const exportarExcel = async () => {
    const params = new URLSearchParams();
    if (filtros.value.tipo_residuo_id && filtros.value.tipo_residuo_id !== undefined && filtros.value.tipo_residuo_id !== 'todos') {
        params.append('tipo_residuo_id', filtros.value.tipo_residuo_id.toString());
    }
    if (filtros.value.fecha_inicio) params.append('fecha_inicio', filtros.value.fecha_inicio);
    if (filtros.value.fecha_fin) params.append('fecha_fin', filtros.value.fecha_fin);

    try {
        loading.value = true;
        const response = await fetch(`/admin/reportes/depositos/excel?${params.toString()}`, {
            method: 'GET',
            headers: {
                Accept: 'text/csv',
            },
        });
        if (!response.ok) throw new Error('No se pudo generar el Excel');
        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = 'reporte_depositos.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(downloadUrl);
        toast({
            title: 'Excel generado',
            description: 'El reporte se descargó correctamente.',
        });
    } catch (e) {
        toast({
            title: 'Error al exportar',
            description: 'Ocurrió un problema al intentar exportar el Excel.',
            variant: 'destructive',
        });
    } finally {
        loading.value = false;
    }
};
</script>
