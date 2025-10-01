<template>
    <StudentLayout :student="student">
        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-gray-900">🏆 Ranking del Curso</h1>
                <p class="text-gray-600">
                    {{ student.curso?.nombre }} "{{ student.paralelo?.nombre }}" ·
                    {{ currentPeriod?.nombre || 'Periodo Actual' }}
                </p>
            </div>

            <!-- Tu posición -->
            <div class="mb-8">
                <div class="mx-auto max-w-xl rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 p-6 text-center text-white shadow-lg">
                    <h2 class="mb-2 text-xl font-semibold">Tu posición actual</h2>
                    <div class="text-4xl font-bold">#{{ myPosition }}</div>
                    <p class="mt-2 text-blue-100">de {{ totalStudents }} estudiantes</p>
                </div>
            </div>

            <!-- Lista de Ranking -->
            <div class="mx-auto max-w-3xl space-y-4">
                <div
                    v-for="student in ranking"
                    :key="student.id"
                    :class="[
                        'flex items-center justify-between rounded-lg border p-4 transition-all',
                        student.id === props.student.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:bg-gray-50',
                    ]"
                >
                    <div class="flex items-center space-x-4">
                        <div
                            :class="[
                                'flex h-10 w-10 items-center justify-center rounded-full font-bold text-white',
                                student.posicion === 1
                                    ? 'bg-yellow-500'
                                    : student.posicion === 2
                                      ? 'bg-gray-400'
                                      : student.posicion === 3
                                        ? 'bg-orange-500'
                                        : 'bg-blue-500',
                            ]"
                        >
                            {{ student.posicion }}
                        </div>
                        <div>
                            <div class="font-semibold">{{ student.apellidos }}, {{ student.nombres }}</div>
                            <div v-if="student.id === props.student.id" class="text-sm text-blue-600">Tú</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-gray-900">{{ student.puntaje }}</div>
                        <div class="text-sm text-gray-500">puntos</div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';

interface Props {
    student: {
        id: number;
        nombres: string;
        apellidos: string;
        curso?: {
            nombre: string;
        };
        paralelo?: {
            nombre: string;
        };
    };
    ranking: Array<{
        id: number;
        nombres: string;
        apellidos: string;
        puntaje: number;
        posicion: number;
    }>;
    currentPeriod?: {
        nombre: string;
    };
    myPosition: number;
    totalStudents: number;
}

const props = defineProps<Props>();
</script>
