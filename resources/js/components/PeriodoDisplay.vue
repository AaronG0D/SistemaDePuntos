<template>
    <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
        <div class="mb-2 flex items-center gap-2">
            <Icon name="calendar" class="h-4 w-4 text-blue-600" />
            <h3 class="font-semibold text-blue-900">Período del Reporte</h3>
        </div>
        <div class="text-sm text-blue-800">
            <div v-if="tipoReporte === 'depositos'">
                <span v-if="filtros.tipo_residuo_id"> <strong>Tipo de Residuo:</strong> {{ getTipoResiduoNombre(filtros.tipo_residuo_id) }} </span>
                <span v-else><strong>Tipo de Residuo:</strong> Todos los tipos</span>
                <br v-if="filtros.fecha_inicio || filtros.fecha_fin" />
                <span v-if="filtros.fecha_inicio && filtros.fecha_fin">
                    <strong>Período:</strong> {{ formatDate(filtros.fecha_inicio) }} - {{ formatDate(filtros.fecha_fin) }}
                </span>
                <span v-else-if="filtros.fecha_inicio"> <strong>Desde:</strong> {{ formatDate(filtros.fecha_inicio) }} </span>
                <span v-else-if="filtros.fecha_fin"> <strong>Hasta:</strong> {{ formatDate(filtros.fecha_fin) }} </span>
                <span v-else><strong>Período:</strong> Todos los registros</span>
            </div>

            <div v-else-if="tipoReporte === 'ranking'">
                <strong>Período:</strong> {{ getPeriodoNombre(filtros.periodo || '') }}
                <br v-if="filtros.tipo_residuo_id" />
                <span v-if="filtros.tipo_residuo_id"> <strong>Tipo de Residuo:</strong> {{ getTipoResiduoNombre(filtros.tipo_residuo_id) }} </span>
                <span v-else><strong>Tipo de Residuo:</strong> Todos los tipos</span>
            </div>

            <div v-else-if="tipoReporte === 'basurero'">
                <strong>Basurero:</strong> {{ getBasureroNombre(filtros.basurero_id) }}
                <br v-if="filtros.fecha_inicio || filtros.fecha_fin" />
                <span v-if="filtros.fecha_inicio && filtros.fecha_fin">
                    <strong>Período:</strong> {{ formatDate(filtros.fecha_inicio) }} - {{ formatDate(filtros.fecha_fin) }}
                </span>
                <span v-else-if="filtros.fecha_inicio"> <strong>Desde:</strong> {{ formatDate(filtros.fecha_inicio) }} </span>
                <span v-else-if="filtros.fecha_fin"> <strong>Hasta:</strong> {{ formatDate(filtros.fecha_fin) }} </span>
                <span v-else><strong>Período:</strong> Todos los registros</span>
            </div>

            <div v-else-if="tipoReporte === 'fecha'">
                <span v-if="filtros.fecha_inicio && filtros.fecha_fin">
                    <strong>Período:</strong> {{ formatDate(filtros.fecha_inicio) }} - {{ formatDate(filtros.fecha_fin) }}
                </span>
                <span v-else><strong>Período:</strong> Todos los registros</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';

interface Props {
    tipoReporte: string;
    filtros: {
        tipo_residuo_id?: number | string;
        fecha_inicio?: string;
        fecha_fin?: string;
        periodo?: string;
        basurero_id?: number;
    };
    tiposResiduos: Array<{ id: number; nombre: string }>;
    basureros: Array<{ id: number; ubicacion: string }>;
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

const getPeriodoNombre = (periodo: string) => {
    const periodos = {
        semana: 'Esta Semana',
        mes: 'Este Mes',
        anio: 'Este Año',
        todo: 'Todo el Tiempo',
    };
    return periodos[periodo as keyof typeof periodos] || periodo;
};

const getTipoResiduoNombre = (id: number | string) => {
    if (typeof id === 'string') return 'Todos los tipos';
    const tipo = props.tiposResiduos.find((t) => t.id === id);
    return tipo ? tipo.nombre : 'Desconocido';
};

const getBasureroNombre = (id: number | undefined) => {
    if (!id) return 'Desconocido';
    const basurero = props.basureros.find((b) => b.id === id);
    return basurero ? basurero.ubicacion : 'Desconocido';
};
</script>
