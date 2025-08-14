<template>
    <div class="w-full">
        <canvas ref="chartRef"></canvas>
    </div>
</template>

<script setup lang="ts">
import { Chart, registerables } from 'chart.js';
import { onMounted, onUnmounted, ref, watch } from 'vue';

Chart.register(...registerables);

interface Props {
    data: {
        labels: string[];
        datasets: Array<{
            label: string;
            data: number[];
            backgroundColor?: string[];
            borderColor?: string;
            borderWidth?: number;
        }>;
    };
    type?: 'bar' | 'pie' | 'line' | 'doughnut';
    options?: any;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'bar',
    options: () => ({}),
});

const chartRef = ref<HTMLCanvasElement>();
let chart: Chart | null = null;

const defaultOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top' as const,
        },
        title: {
            display: true,
            text: 'Estadísticas de Depósitos',
        },
    },
};

onMounted(() => {
    if (chartRef.value) {
        const ctx = chartRef.value.getContext('2d');
        if (ctx) {
            chart = new Chart(ctx, {
                type: props.type,
                data: props.data,
                options: { ...defaultOptions, ...props.options },
            });
        }
    }
});

watch(
    () => props.data,
    (newData) => {
        if (chart) {
            chart.data = newData;
            chart.update();
        }
    },
    { deep: true },
);

watch(
    () => props.type,
    (newType) => {
        if (chart) {
            chart.destroy();
            const ctx = chartRef.value?.getContext('2d');
            if (ctx) {
                chart = new Chart(ctx, {
                    type: newType,
                    data: props.data,
                    options: { ...defaultOptions, ...props.options },
                });
            }
        }
    },
);

onUnmounted(() => {
    if (chart) {
        chart.destroy();
    }
});
</script>
