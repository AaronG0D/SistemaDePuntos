<template>
    <AppLayout>
        <Head title="Gestión de Códigos QR" />
        <div class="container mx-auto space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gestión de Códigos QR</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Administra y genera códigos QR de estudiantes</p>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Estudiantes</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                            </div>
                            <Users class="h-8 w-8 text-blue-600" />
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">QRs Activos</p>
                                <p class="text-2xl font-bold text-green-600">{{ stats.activos }}</p>
                            </div>
                            <CheckCircle class="h-8 w-8 text-green-600" />
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">QRs Inactivos</p>
                                <p class="text-2xl font-bold text-red-600">{{ stats.inactivos }}</p>
                            </div>
                            <XCircle class="h-8 w-8 text-red-600" />
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">% Activos</p>
                                <p class="text-2xl font-bold text-blue-600">{{ stats.porcentaje }}%</p>
                            </div>
                            <Activity class="h-8 w-8 text-blue-600" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Botones de acción -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Button
                    @click="generateAllStudents"
                    :disabled="isGenerating"
                    class="w-full bg-blue-600 text-white hover:bg-blue-700"
                >
                    <FileDown class="mr-2 h-4 w-4" />
                    {{ isGenerating ? 'Generando...' : 'Generar PDF - Todos' }}
                </Button>

                <Button
                    @click="showCourseDialog = true"
                    :disabled="isGenerating"
                    class="w-full bg-green-600 text-white hover:bg-green-700"
                >
                    <FileText class="mr-2 h-4 w-4" />
                    Generar PDF - Por Curso
                </Button>
            </div>

            <!-- Filtros y búsqueda -->
            <Card>
                <CardHeader>
                    <CardTitle>Buscar Estudiantes</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <Label>Búsqueda General</Label>
                            <div class="relative">
                                <Search class="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                <Input
                                    v-model="searchTerm"
                                    placeholder="Nombre, correo..."
                                    class="pl-10"
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Curso</Label>
                            <Select v-model="selectedCurso">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los cursos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los cursos</SelectItem>
                                    <SelectItem v-for="curso in cursos" :key="curso" :value="curso">
                                        {{ curso }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Paralelo</Label>
                            <Select v-model="selectedParalelo">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos los paralelos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los paralelos</SelectItem>
                                    <SelectItem v-for="paralelo in paralelos" :key="paralelo" :value="paralelo">
                                        {{ paralelo }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Estado QR</Label>
                            <Select v-model="selectedEstado">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem value="activo">Activos</SelectItem>
                                    <SelectItem value="inactivo">Inactivos</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabla de estudiantes -->
            <Card>
                <CardHeader>
                    <CardTitle>Estudiantes ({{ filteredStudents.length }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Correo</TableHead>
                                <TableHead>Curso</TableHead>
                                <TableHead>Paralelo</TableHead>
                                <TableHead>Estado QR</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="student in paginatedStudents" :key="student.id">
                                <TableCell class="font-medium">
                                    {{ student.nombres }} {{ student.primerApellido }} {{ student.segundoApellido }}
                                </TableCell>
                                <TableCell>{{ student.email }}</TableCell>
                                <TableCell>{{ student.curso_nombre }}</TableCell>
                                <TableCell>{{ student.paralelo_nombre }}</TableCell>
                                <TableCell>
                                    <Badge :class="student.qr_codigo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                        {{ student.qr_codigo ? 'Activo' : 'Inactivo' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            v-if="student.qr_codigo"
                                            @click="deactivateQr(student.id)"
                                            size="sm"
                                            variant="destructive"
                                        >
                                            <XCircle class="mr-1 h-3 w-3" />
                                            Desactivar
                                        </Button>
                                        <Button
                                            v-else
                                            @click="activateQr(student.id)"
                                            size="sm"
                                            variant="default"
                                        >
                                            <CheckCircle class="mr-1 h-3 w-3" />
                                            Activar
                                        </Button>
                                        <Button
                                            @click="viewQr(student)"
                                            size="sm"
                                            variant="outline"
                                            :disabled="!student.qr_codigo"
                                        >
                                            <QrCode class="mr-1 h-3 w-3" />
                                            Ver
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    
                    <!-- Paginación -->
                    <div class="mt-4 flex items-center justify-between">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Mostrando {{ ((currentPage - 1) * itemsPerPage) + 1 }} a {{ Math.min(currentPage * itemsPerPage, filteredStudents.length) }} de {{ filteredStudents.length }} estudiantes
                        </p>
                        <div class="flex gap-2">
                            <Button
                                @click="currentPage--"
                                :disabled="currentPage === 1"
                                size="sm"
                                variant="outline"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            <Button
                                @click="currentPage++"
                                :disabled="currentPage >= totalPages"
                                size="sm"
                                variant="outline"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Dialog para ver QR -->
            <Dialog v-model:open="showQrDialog">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Código QR</DialogTitle>
                    </DialogHeader>
                    <div v-if="selectedStudent" class="flex flex-col items-center gap-4 py-4">
                        <!-- QR Code -->
                        <div class="relative flex h-48 w-48 items-center justify-center overflow-hidden rounded-md border bg-white">
                            <img
                                v-if="qrUrl"
                                :src="qrUrl"
                                :alt="`QR Code para ${selectedStudent.nombres}`"
                                class="h-full w-full object-contain"
                                v-show="!qrLoading && !qrError"
                            />
                            <div v-if="qrLoading" class="text-muted-foreground flex flex-col items-center justify-center">
                                <div class="h-6 w-6 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>
                                <span class="mt-2 text-xs">Cargando QR...</span>
                            </div>
                            <div v-if="qrError" class="px-2 text-center text-xs text-red-600">
                                No se pudo cargar el QR.
                            </div>
                        </div>
                        <!-- Información -->
                        <div class="text-center">
                            <p class="font-medium">{{ selectedStudent.nombres }} {{ selectedStudent.primerApellido }} {{ selectedStudent.segundoApellido }}</p>
                            <p class="text-sm text-gray-500">{{ selectedStudent.email }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ selectedStudent.curso_nombre }} - {{ selectedStudent.paralelo_nombre }}</p>
                        </div>
                        <!-- Botones -->
                        <div class="flex gap-2">
                            <Button @click="downloadQr" variant="outline" :disabled="!qrUrl">
                                <FileDown class="mr-2 h-4 w-4" />
                                Descargar
                            </Button>
                            <Button @click="showQrDialog = false" variant="outline">
                                Cerrar
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Head } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import UserQrCode from '@/components/UserQrCode.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { Activity, CheckCircle, ChevronLeft, ChevronRight, FileDown, FileText, QrCode, Search, Users, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast, Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';
import { route } from 'ziggy-js';

// Estado reactivo
const students = ref<Student[]>([]);
const courses = ref<Course[]>([]);
const isGenerating = ref(false);
const showCourseDialog = ref(false);
const selectedCourse = ref<number | null>(null);
const searchTerm = ref('');
const selectedCurso = ref('all');
const selectedParalelo = ref('all');
const selectedEstado = ref('all');
const currentPage = ref(1);
const itemsPerPage = 10;
const selectedStudent = ref<Student | null>(null);
const showQrDialog = ref(false);
const qrUrl = ref('');
const qrLoading = ref(false);
const qrError = ref(false);

interface Student {
    id: number;
    nombres: string;
    primerApellido: string;
    segundoApellido: string;
    email: string;
    curso_nombre: string;
    paralelo_nombre: string;
    qr_codigo: string | null;
}

interface Course {
    id: number;
    nombre: string;
}

// Computados para filtros
const cursos = computed(() => {
    const uniqueCursos = new Set(students.value.map(s => s.curso_nombre));
    return Array.from(uniqueCursos).sort();
});

const paralelos = computed(() => {
    const uniqueParalelos = new Set(students.value.map(s => s.paralelo_nombre));
    return Array.from(uniqueParalelos).sort();
});

// Estadísticas
const stats = computed(() => {
    const total = students.value.length;
    const activos = students.value.filter(s => s.qr_codigo).length;
    const inactivos = total - activos;
    const porcentaje = total > 0 ? Math.round((activos / total) * 100) : 0;
    
    return { total, activos, inactivos, porcentaje };
});

// Estudiantes filtrados
const filteredStudents = computed(() => {
    return students.value.filter(student => {
        // Filtro de búsqueda
        if (searchTerm.value) {
            const search = searchTerm.value.toLowerCase();
            const fullName = `${student.nombres} ${student.primerApellido} ${student.segundoApellido}`.toLowerCase();
            const email = student.email.toLowerCase();
            if (!fullName.includes(search) && !email.includes(search)) {
                return false;
            }
        }
        
        // Filtro de curso
        if (selectedCurso.value !== 'all' && student.curso_nombre !== selectedCurso.value) {
            return false;
        }
        
        // Filtro de paralelo
        if (selectedParalelo.value !== 'all' && student.paralelo_nombre !== selectedParalelo.value) {
            return false;
        }
        
        // Filtro de estado
        if (selectedEstado.value === 'activo' && !student.qr_codigo) {
            return false;
        }
        if (selectedEstado.value === 'inactivo' && student.qr_codigo) {
            return false;
        }
        
        return true;
    });
});

// Paginación
const totalPages = computed(() => Math.ceil(filteredStudents.value.length / itemsPerPage));

const paginatedStudents = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredStudents.value.slice(start, end);
});

// Métodos
const loadStudents = async () => {
    try {
        const response = await axios.get(route('qr.students'));
        students.value = response.data;
    } catch (error) {
        console.error('Error cargando estudiantes:', error);
        toast.error('Error al cargar estudiantes');
    }
};

const loadCourses = async () => {
    try {
        const response = await axios.get(route('qr.courses'));
        courses.value = response.data;
    } catch (error) {
        console.error('Error cargando cursos:', error);
        toast.error('Error al cargar cursos');
    }
};

// Activar QR (generar nuevo código)
const activateQr = async (studentId: number) => {
    try {
        const response = await axios.post(route('qr.activate', studentId));
        if (response.data.success) {
            toast.success('QR activado correctamente');
            await loadStudents(); // Recargar lista
        } else {
            toast.error(response.data.error || 'Error al activar QR');
        }
    } catch (error) {
        console.error('Error activando QR:', error);
        toast.error('Error al activar QR');
    }
};

// Desactivar QR (establecer como string vacío)
const deactivateQr = async (studentId: number) => {
    if (!confirm('¿Estás seguro de desactivar este QR? El estudiante no podrá usarlo hasta que lo reactives.')) {
        return;
    }
    
    try {
        const response = await axios.post(route('qr.deactivate', studentId));
        if (response.data.success) {
            toast.success('QR desactivado correctamente');
            await loadStudents(); // Recargar lista
        } else {
            toast.error(response.data.error || 'Error al desactivar QR');
        }
    } catch (error) {
        console.error('Error desactivando QR:', error);
        toast.error('Error al desactivar QR');
    }
};

// Ver QR
const viewQr = async (student: Student) => {
    selectedStudent.value = student;
    showQrDialog.value = true;
    qrLoading.value = true;
    qrError.value = false;
    qrUrl.value = '';
    
    try {
        const response = await fetch(route('qr.generate.user', student.id));
        const data = await response.json();
        
        if (data.success && data.qr_url) {
            qrUrl.value = data.qr_url;
        } else {
            qrError.value = true;
        }
    } catch (error) {
        console.error('Error cargando QR:', error);
        qrError.value = true;
    } finally {
        qrLoading.value = false;
    }
};

// Descargar QR
const downloadQr = () => {
    if (!qrUrl.value || !selectedStudent.value) return;
    const link = document.createElement('a');
    link.href = qrUrl.value;
    link.download = `qr_${selectedStudent.value.nombres}_${selectedStudent.value.primerApellido}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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


// Lifecycle
onMounted(async () => {
    await Promise.all([loadStudents(), loadCourses()]);
});
</script>
