<template>
    <AppLayout>
        <div class="container mx-auto space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Generar QRs</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Genera PDF con códigos QR para imprimir</p>
                </div>
            </div>

            <!-- Botones principales -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Button
                    @click="generateAllStudents"
                    :disabled="isGenerating"
                    class="w-full bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700"
                >
                    {{ isGenerating ? 'Generando...' : 'Generar PDF - Todos' }}
                </Button>

                <Button
                    @click="showCourseDialog = true"
                    :disabled="isGenerating"
                    class="w-full bg-green-600 text-white hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700"
                >
                    Generar PDF - Por Curso
                </Button>

                <Button
                    @click="cleanupFiles"
                    :disabled="isGenerating"
                    variant="outline"
                    class="w-full border-red-300 text-red-700 hover:bg-red-50 dark:border-red-600 dark:text-red-400 dark:hover:bg-red-900/20"
                >
                    Limpiar Archivos
                </Button>
            </div>

            <!-- Lista de estudiantes -->
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Estudiantes ({{ students.length }})</h3>
                <div class="max-h-96 space-y-2 overflow-y-auto">
                    <div
                        v-for="student in students"
                        :key="student.id"
                        class="flex items-center justify-between rounded border border-gray-200 bg-gray-50 p-2 dark:border-gray-600 dark:bg-gray-700"
                    >
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ student.nombres }} {{ student.primerApellido }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ student.curso_nombre }} - {{ student.paralelo_nombre }}</p>
                        </div>
                        <Button @click="generateStudentQr(student.id)" size="sm" variant="outline" class="border-gray-300 dark:border-gray-600">
                            Generar QR
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Dialog para seleccionar curso -->
            <Dialog v-model:open="showCourseDialog">
                <DialogContent class="border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <DialogHeader>
                        <DialogTitle class="text-gray-900 dark:text-white">Generar QR por Curso</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4">
                        <div>
                            <Label class="text-gray-700 dark:text-gray-300">Seleccionar Curso</Label>
                            <Select v-model="selectedCourse">
                                <SelectTrigger class="border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700">
                                    <SelectValue placeholder="Selecciona un curso" />
                                </SelectTrigger>
                                <SelectContent class="border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <SelectItem
                                        v-for="course in courses"
                                        :key="course.id"
                                        :value="course.id"
                                        class="text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                                    >
                                        {{ course.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex justify-end gap-2">
                            <Button @click="showCourseDialog = false" variant="outline" class="border-gray-300 dark:border-gray-600">Cancelar</Button>
                            <Button
                                @click="generateCourseQr"
                                :disabled="!selectedCourse || isGenerating"
                                class="bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700"
                            >
                                Generar PDF
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Toaster para notificaciones -->
        <Toaster position="top-center" />
    </AppLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { toast, Toaster } from 'vue-sonner';
import { XCircle } from 'lucide-vue-next';
import 'vue-sonner/style.css';
import { route } from 'ziggy-js';

// Estado reactivo
const students = ref<Student[]>([]);
const courses = ref<Course[]>([]);
const isGenerating = ref(false);
const showCourseDialog = ref(false);
const selectedCourse = ref<number | null>(null);

interface Student {
    id: number;
    nombres: string;
    primerApellido: string;
    curso_nombre: string;
    paralelo_nombre: string;
}

interface Course {
    id: number;
    nombre: string;
}

// Métodos
const loadStudents = async () => {
    try {
        const response = await axios.get('/api/students');
        students.value = response.data;
    } catch (error) {
        console.error('Error cargando estudiantes:', error);
        alert('Error cargando estudiantes');
    }
};

const loadCourses = async () => {
    try {
        const response = await axios.get('/api/courses');
        courses.value = response.data;
    } catch (error) {
        console.error('Error cargando cursos:', error);
        alert('Error cargando cursos');
    }
};

const generateAllStudents = async () => {
    isGenerating.value = true;
    try {
        const response = await axios.get(route('qr.generate.pdf'));
        if (response.data.success) {
            alert(`PDF generado con ${response.data.total_qrs} códigos QR`);
            window.open(route('qr.download.pdf'), '_blank');
        } else {
            alert('Error: ' + response.data.error);
        }
    } catch (error) {
        console.error('Error generando PDF:', error);
        alert('Error al generar PDF');
    } finally {
        isGenerating.value = false;
    }
};

const generateCourseQr = async () => {
    if (!selectedCourse.value) return;

    // Para cursos grandes, abrir descarga directa (stream)
    try {
        isGenerating.value = true;
        showCourseDialog.value = false;
        const url = `${route('qr.download.pdf')}?curso_paralelo_id=${selectedCourse.value}`;
        window.open(url, '_blank');

        // Mostrar notificación de éxito
        toast('PDF generado', {
            description: 'El PDF se está descargando. Puede demorar unos segundos en generarse.',
            duration: 5000,
        });
    } catch (error) {
        console.error('Error generando/descargando PDF del curso:', error);
        toast('Error al generar PDF', {
            description: 'No se pudo generar el PDF del curso',
            icon: XCircle,
        });
    } finally {
        isGenerating.value = false;
        selectedCourse.value = null;
    }
};

const generateStudentQr = async (studentId: number) => {
    isGenerating.value = true;
    try {
        const response = await axios.get(route('qr.generate.user', studentId));
        if (response.data.success) {
            alert('QR generado exitosamente');
        } else {
            alert('Error: ' + response.data.error);
        }
    } catch (error) {
        console.error('Error generando QR:', error);
        alert('Error al generar QR');
    } finally {
        isGenerating.value = false;
    }
};

const cleanupFiles = async () => {
    if (!confirm('¿Estás seguro de que quieres limpiar los archivos QR antiguos?')) return;

    isGenerating.value = true;
    try {
        const response = await axios.post(route('qr.cleanup'), { days: 7 });
        if (response.data.success) {
            alert(response.data.message);
        } else {
            alert('Error: ' + response.data.error);
        }
    } catch (error) {
        console.error('Error limpiando archivos:', error);
        alert('Error al limpiar archivos');
    } finally {
        isGenerating.value = false;
    }
};

// Lifecycle
onMounted(async () => {
    await Promise.all([loadStudents(), loadCourses()]);
});
</script>
