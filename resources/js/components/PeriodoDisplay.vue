```
<template>
    <div class="mb-4 rounded-md border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2 mb-3 border-b border-gray-100 pb-2">
            <div class="rounded-full bg-blue-50 p-1.5">
                <Icon name="calendar" class="h-4 w-4 text-blue-600" />
            </div>
            <h3 class="text-sm font-semibold text-gray-800">Resumen del Reporte</h3>
        </div>
        
        <div class="grid gap-3 text-sm">
            <!-- Reporte de Depósitos -->
            <template v-if="tipoReporte === 'depositos'">
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide mr-1.5">Residuos:</span>
                        <span class="font-medium text-blue-700">
                            {{ getTipoResiduoNombre(filtros.tipo_residuo_id) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide mr-1.5">Período:</span>
                        <span class="font-medium text-gray-800">
                            <template v-if="periodoNombre">
                                {{ periodoNombre }}
                            </template>
                            <template v-else-if="filtros.fecha_inicio && filtros.fecha_fin">
                                {{ formatDate(filtros.fecha_inicio) }} - {{ formatDate(filtros.fecha_fin) }}
                            </template>
                            <template v-else-if="filtros.fecha_inicio">
                                Desde {{ formatDate(filtros.fecha_inicio) }}
                            </template>
                            <template v-else-if="filtros.fecha_fin">
                                Hasta {{ formatDate(filtros.fecha_fin) }}
                            </template>
                            <template v-else>
                                Últimos 3 meses
                            </template>
                        </span>
                    </div>
                </div>
            </template>

            <!-- Reporte de Ranking -->
            <template v-else-if="tipoReporte === 'ranking'">
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide mr-1.5">Ranking de:</span>
                        <span class="font-bold text-blue-700 capitalize">
                            {{ tipoRanking || 'Estudiantes' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide mr-1.5">Tiempo:</span>
                        <span class="font-medium text-gray-800">
                            {{ periodoNombre || getPeriodoNombre(filtros.filtro || filtros.periodo || '') }}
                        </span>
                    </div>
                </div>
            </template>

            <!-- Reporte de Basurero -->
            <template v-else-if="tipoReporte === 'basurero'">
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide mr-1.5">Basurero:</span>
                        <span class="font-medium text-blue-700">
                            {{ getBasureroNombre(filtros.basurero_id) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide mr-1.5">Período:</span>
                        <span class="font-medium text-gray-800">
                            <template v-if="filtros.fecha_inicio && filtros.fecha_fin">
                                {{ formatDate(filtros.fecha_inicio) }} - {{ formatDate(filtros.fecha_fin) }}
                            </template>
                            <template v-else>
                                Últimos 3 meses
                            </template>
                        </span>
                    </div>
                </div>
            </template>

            <!-- Reporte de Estudiante -->
            <template v-else-if="tipoReporte === 'estudiante'">
                <div class="flex flex-col gap-2">
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide mr-1.5">Estudiante:</span>
                        <span class="font-bold text-blue-700 text-base">
                            {{ filtros.estudiante_id ? getEstudianteNombre(filtros.estudiante_id) : 'Seleccione un estudiante' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide mr-1.5">Período:</span>
                        <span class="font-medium text-gray-800">
                            <template v-if="periodoNombre">
                                {{ periodoNombre }}
                            </template>
                            <template v-else-if="filtros.fecha_inicio && filtros.fecha_fin">
                                {{ formatDate(filtros.fecha_inicio) }} - {{ formatDate(filtros.fecha_fin) }}
                            </template>
                            <template v-else>
                                Últimos 3 meses
                            </template>
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';

interface Props {
    tipoReporte: string;
    tipoRanking?: string;
    periodoNombre?: string;
    filtros: {      
        tipo_residuo_id?: number | string | (number | string)[];
        fecha_inicio?: string;
        fecha_fin?: string;
        periodo?: string;
        filtro?: string | number;
        basurero_id?: number | string;
        estudiante_id?: number | string;
        curso_id?: number | string;
        paralelo_id?: number | string;
    };
    tiposResiduos: Array<{ id: number; nombre: string }>;
    basureros: Array<{ id: number; ubicacion: string }>;
    estudiantes?: Array<{ id: number; nombre_completo: string }>;
}

const props = defineProps<Props>();

const formatDate = (dateString: string | undefined) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getPeriodoNombre = (periodo: string | number) => {
    const periodos = {
        semana: 'Esta Semana',
        mes: 'Este Mes',
        anio: 'Este Año',
        todo: 'Todo el Tiempo',
        todos: 'Todo el Tiempo'
    };
    return periodos[periodo as keyof typeof periodos] || periodo;
};

const getTipoResiduoNombre = (id: number | string | (number | string)[] | undefined) => {
    if (!id || id === 'todos') return 'Todos los tipos';
    
    if (Array.isArray(id)) {
        if (id.length === 0) return 'Todos los tipos';
        const nombres = id.map(typeId => {
            const tipo = props.tiposResiduos.find(t => t.id === Number(typeId));
            return tipo ? tipo.nombre : '';
        }).filter(Boolean);
        return nombres.join(', ');
    }

    const tipo = props.tiposResiduos.find((t) => String(t.id) === String(id));
    return tipo ? tipo.nombre : 'Desconocido';
};

const getBasureroNombre = (id: number | string | undefined) => {
    if (!id) return 'Desconocido';
    const basurero = props.basureros.find((b) => String(b.id) === String(id));
    return basurero ? basurero.ubicacion : 'Desconocido';
};

const getEstudianteNombre = (id: number | string | undefined) => {
    if (!id || !props.estudiantes) return 'Desconocido';
    const estudiante = props.estudiantes.find((e) => String(e.id) === String(id));
    return estudiante ? estudiante.nombre_completo : 'Desconocido';
};
</script>
