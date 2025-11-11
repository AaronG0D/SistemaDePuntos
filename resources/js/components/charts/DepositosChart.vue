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

// Helper para colores por tema
const cssVar = (name: string) => {
    if (typeof window === 'undefined') return '';
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
};

const isDarkMode = () => {
    if (typeof window === 'undefined') return true;
    return document.documentElement.classList.contains('dark');
};

const theme = () => {
    const dark = isDarkMode();
    return {
        bg: dark ? '#111827' : '#FFFFFF',
        fg: dark ? '#F9FAFB' : '#1F2937',
        border: dark ? '#374151' : '#E5E7EB',
        muted: dark ? '#9CA3AF' : '#6B7280',
        tooltipBg: dark ? '#111827' : '#FFFFFF',
        tooltipText: dark ? '#F9FAFB' : '#1F2937',
    };
};

// Observer para cambios de tema (clase 'dark' en <html>)
const themeObserver = ref<MutationObserver | null>(null);

function updateThemeOptions() {
    if (!chart) return;
    const t = theme();
    const opts: any = chart.options || {};

    // Plugins
    if (opts.plugins) {
        if (opts.plugins.legend?.labels) {
            opts.plugins.legend.labels.color = t.fg;
        }
        if (opts.plugins.title) {
            opts.plugins.title.color = t.fg;
        }
        if (opts.plugins.tooltip) {
            opts.plugins.tooltip.backgroundColor = t.tooltipBg;
            opts.plugins.tooltip.titleColor = t.tooltipText;
            opts.plugins.tooltip.bodyColor = t.tooltipText;
            opts.plugins.tooltip.borderColor = t.border;
        }
    }

    // Escalas
    if (opts.scales) {
        if (opts.scales.x) {
            if (opts.scales.x.ticks) opts.scales.x.ticks.color = t.muted;
            if (opts.scales.x.grid) opts.scales.x.grid.color = t.border;
        }
        if (opts.scales.y) {
            if (opts.scales.y.ticks) opts.scales.y.ticks.color = t.muted;
            if (opts.scales.y.grid) opts.scales.y.grid.color = t.border;
        }
    }
    chart.update();
}

const defaultOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top' as const,
            labels: {
                color: theme().fg,
                padding: 15,
                font: {
                    size: 12,
                },
            },
        },
        title: {
            display: true,
            text: 'Estadísticas de Depósitos',
            color: theme().fg,
        },
        tooltip: {
            backgroundColor: theme().tooltipBg,
            titleColor: theme().tooltipText,
            bodyColor: theme().tooltipText,
            borderColor: theme().border,
            borderWidth: 1,
            intersect: false,
            mode: 'index' as const,
            padding: 12,
            titleFont: {
                size: 14,
                weight: 'bold' as const,
            },
            bodyFont: {
                size: 13,
            },
        },
    },
    scales: {
        x: {
            ticks: { color: theme().muted },
            grid: { color: theme().border },
            border: { color: theme().border },
        },
        y: {
            ticks: { color: theme().muted },
            grid: { color: theme().border },
            border: { color: theme().border },
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
            // Sincronizar tema inicial y observar cambios
            updateThemeOptions();
            themeObserver.value = new MutationObserver(() => {
                updateThemeOptions();
            });
            themeObserver.value.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class'],
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
    if (themeObserver.value) {
        themeObserver.value.disconnect();
        themeObserver.value = null;
    }
});
</script>
