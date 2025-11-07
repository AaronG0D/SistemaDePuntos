<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Clock, Edit, PlusCircle, Save, Users, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { toast, Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';

interface Props {
    teacher: {
        id: number;
        nombres: string;
        apellidos: string;
    };
    coursesParallels: Array<{
        id: number;
        nombre_completo: string;
    }>;
    subjects: Array<{
        id: number;
        nombre: string;
        curso_paralelo_id?: number;
    }>;
    periods: Array<{
        id: number;
        nombre: string;
    }>;
    students: Array<{
        id: number;
        nombres: string;
        apellidos: string;
        curso_paralelo_id?: number;
    }>;
    recentAssignments: Array<{
        id: number;
        puntos: number;
        fecha_asignacion: string;
        estudiante: {
            nombres: string;
            apellidos: string;
        };
        materia: {
            nombre: string;
        };
        periodo: {
            nombre: string;
        };
    }>;
}

const props = defineProps<Props>();

// Tabs
const activeTab = ref('individual');

// Individual Assignment
const selectedCourseParallel = ref('');
const isSubmitting = ref(false);
const showConfirmDialog = ref(false);
const confirmData = ref<{ studentName?: string }>({});
const form = ref({
    materia_id: '',
    periodo_id: '',
    estudiante_id: '',
    puntos: 0 | 0,
    fecha_asignacion: new Date().toISOString().split('T')[0],
    comentario: '',
});

// Bulk Assignment
const isBulkSubmitting = ref(false);
const showBulkConfirmDialog = ref(false);
const bulkConfirmData = ref<{ numStudents?: number }>({});
const bulkForm = ref({
    curso_paralelo_id: '',
    materia_id: '',
    periodo_id: '',
    puntos: 0 | 0,
    estudiantes_ids: [] as number[],
});

// Computed properties for cascading dropdowns
const availableSubjects = computed(() => {
    if (!selectedCourseParallel.value) return [];
    const courseParallelId = parseInt(selectedCourseParallel.value);
    return props.subjects.filter((s) => s.curso_paralelo_id === courseParallelId);
});

const availableStudents = computed(() => {
    if (!selectedCourseParallel.value) return [];
    const courseParallelId = parseInt(selectedCourseParallel.value);
    return props.students.filter((s) => s.curso_paralelo_id === courseParallelId);
});

const bulkAvailableSubjects = computed(() => {
    if (!bulkForm.value.curso_paralelo_id) return [];
    const courseParallelId = parseInt(bulkForm.value.curso_paralelo_id);
    return props.subjects.filter((s) => s.curso_paralelo_id === courseParallelId);
});

const courseStudents = computed(() => {
    if (!bulkForm.value.curso_paralelo_id) return [];
    const courseParallelId = parseInt(bulkForm.value.curso_paralelo_id);
    return props.students.filter((s) => s.curso_paralelo_id === courseParallelId);
});

// Methods
function onCourseParallelChange() {
    form.value.materia_id = '';
    form.value.estudiante_id = '';
}

function onBulkCourseChange() {
    bulkForm.value.materia_id = '';
    bulkForm.value.estudiantes_ids = [];
}

function selectAllStudents() {
    bulkForm.value.estudiantes_ids = courseStudents.value.map((s) => s.id);
}

function deselectAllStudents() {
    bulkForm.value.estudiantes_ids = [];
}

function openConfirmDialog() {
    if (!form.value.estudiante_id || !form.value.puntos) {
        toast.error('Campos requeridos', { description: 'Por favor, complete todos los campos obligatorios.' });
        return;
    }
    const student = availableStudents.value.find((s) => s.id === parseInt(form.value.estudiante_id));
    confirmData.value = { studentName: student ? `${student.apellidos}, ${student.nombres}` : 'el estudiante' };
    showConfirmDialog.value = true; 
}

function submitAssignment() {
    showConfirmDialog.value = false;
    isSubmitting.value = true;
    router.post(route('docente.curso.asignar-extracurriculares', { idCursoParalelo: selectedCourseParallel.value }), {
        estudiantes: [parseInt(form.value.estudiante_id)],
        idMateria: parseInt(form.value.materia_id),
        idPeriodo: parseInt(form.value.periodo_id),
        puntos: form.value.puntos,
        comentario: form.value.comentario,
    }, {
        onSuccess: (response) => {
            toast.success(' Puntos asignados correctamente', {
                description: `Se asignaron ${form.value.puntos} puntos extracurriculares al estudiante`,
            });
            resetForm();
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(' Error al asignar puntos', {
                description: firstError || 'No se pudieron asignar los puntos. Intenta nuevamente.',
            });
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
}

function openBulkConfirmDialog() {
    if (bulkForm.value.estudiantes_ids.length === 0) {
        toast.error('Selección requerida', { description: 'Debes seleccionar al menos un estudiante' });
        return;
    }
    if (!bulkForm.value.puntos) {
        toast.error('Campos requeridos', { description: 'Por favor, ingrese los puntos a asignar.' });
        return;
    }
    bulkConfirmData.value = { numStudents: bulkForm.value.estudiantes_ids.length };
    showBulkConfirmDialog.value = true;
}

function submitBulkAssignment() {
    showBulkConfirmDialog.value = false;
    isBulkSubmitting.value = true;
    router.post(route('docente.curso.asignar-extracurriculares', { idCursoParalelo: bulkForm.value.curso_paralelo_id }), {
        estudiantes: bulkForm.value.estudiantes_ids,
        idMateria: parseInt(bulkForm.value.materia_id),
        idPeriodo: parseInt(bulkForm.value.periodo_id),
        puntos: bulkForm.value.puntos,
        comentario: 'Asignación masiva',
    }, {
        onSuccess: (response) => {
            const numEstudiantes = bulkConfirmData.value.numStudents || bulkForm.value.estudiantes_ids.length;
            toast.success('Asignación masiva completada', {
                description: `Se asignaron ${bulkForm.value.puntos} puntos extracurriculares a ${numEstudiantes} estudiante(s)`,
            });
            resetBulkForm();
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(' Error en asignación masiva', {
                description: firstError || 'No se pudieron asignar los puntos. Intenta nuevamente.',
            });
        },
        onFinish: () => {
            isBulkSubmitting.value = false;
        },
    });
}

function resetForm() {
    form.value = {
        materia_id: '',
        periodo_id: '',
        estudiante_id: '',
        puntos: 0 | 0,
        fecha_asignacion: new Date().toISOString().split('T')[0],
        comentario: '',
    };
    selectedCourseParallel.value = '';
}

function resetBulkForm() {
    bulkForm.value = {
        curso_paralelo_id: '',
        materia_id: '',
        periodo_id: '',
        puntos: 0 | 0,
        estudiantes_ids: [],
    };
}

const getInitials = (nombres: string, apellidos: string) => {
    const firstInitial = nombres?.charAt(0).toUpperCase() || '';
    const lastInitial = apellidos?.charAt(0).toUpperCase() || '';
    return `${firstInitial}${lastInitial}`;
};

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    try {
        return new Date(dateString).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch (error) {
        return 'Fecha inválida';
    }
};
</script>

<template>
    <Head title="Asignación de Puntos" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-8">
                <div
                    class="rounded-xl border border-slate-200 bg-gradient-to-r from-slate-50 to-gray-50 p-8 shadow-sm dark:border-slate-700 dark:from-slate-800 dark:to-gray-800"
                >
                    <h1 class="mb-2 flex items-center gap-3 text-3xl font-semibold text-slate-800 dark:text-slate-100">
                        <div class="rounded-lg bg-slate-100 p-2 dark:bg-slate-700">
                            <PlusCircle class="h-6 w-6 text-slate-600 dark:text-slate-300" />
                        </div>
                        Asignación de Puntos
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400">Asigna puntos a estudiantes de forma individual o masiva con facilidad</p>
                </div>
            </header>

            <!-- ===== TABS PRINCIPALES ===== -->
            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="grid w-full grid-cols-3 rounded-lg bg-slate-100 p-1 dark:bg-slate-800">
                    <TabsTrigger
                        value="individual"
                        class="flex items-center gap-2 data-[state=active]:bg-white data-[state=active]:text-slate-900 data-[state=active]:shadow-sm dark:data-[state=active]:bg-slate-700 dark:data-[state=active]:text-slate-100"
                    >
                        <Edit class="h-4 w-4" />
                        Asignación Individual
                    </TabsTrigger>
                    <TabsTrigger
                        value="bulk"
                        class="flex items-center gap-2 data-[state=active]:bg-white data-[state=active]:text-slate-900 data-[state=active]:shadow-sm dark:data-[state=active]:bg-slate-700 dark:data-[state=active]:text-slate-100"
                    >
                        <Users class="h-4 w-4" />
                        Asignación Masiva
                    </TabsTrigger>
                    <TabsTrigger
                        value="history"
                        class="flex items-center gap-2 data-[state=active]:bg-white data-[state=active]:text-slate-900 data-[state=active]:shadow-sm dark:data-[state=active]:bg-slate-700 dark:data-[state=active]:text-slate-100"
                    >
                        <Clock class="h-4 w-4" />
                        Historial Reciente
                    </TabsTrigger>
                </TabsList>

                <!-- ===== TAB ASIGNACIÓN INDIVIDUAL ===== -->
                <TabsContent value="individual">
                    <Card class="border border-slate-200 shadow-sm dark:border-slate-700">
                        <CardHeader class="bg-slate-50 dark:bg-slate-800/50">
                            <CardTitle class="flex items-center gap-3 text-slate-800 dark:text-slate-200">
                                <div class="rounded-md bg-slate-100 p-2 dark:bg-slate-700">
                                    <Edit class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                </div>
                                Nueva Asignación Individual
                            </CardTitle>
                            <CardDescription class="text-slate-600 dark:text-slate-400">
                                Completa el formulario para asignar puntos a un solo estudiante.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="openConfirmDialog" class="space-y-6">
                                <div class="grid gap-6 md:grid-cols-3">
                                    <div class="space-y-2">
                                        <Label>Curso-Paralelo *</Label>
                                        <Select v-model="selectedCourseParallel" @update:modelValue="onCourseParallelChange" required>
                                            <SelectTrigger><SelectValue placeholder="Seleccionar curso-paralelo" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="cp in coursesParallels" :key="cp.id" :value="cp.id.toString()">{{
                                                    cp.nombre_completo
                                                }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Materia *</Label>
                                        <Select v-model="form.materia_id" :disabled="!selectedCourseParallel" required>
                                            <SelectTrigger><SelectValue placeholder="Seleccionar materia" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="subject in availableSubjects" :key="subject.id" :value="subject.id.toString()">{{
                                                    subject.nombre
                                                }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Período *</Label>
                                        <Select v-model="form.periodo_id" required>
                                            <SelectTrigger><SelectValue placeholder="Seleccionar período" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="period in periods" :key="period.id" :value="period.id.toString()">{{
                                                    period.nombre
                                                }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div class="grid gap-6 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label>Estudiante *</Label>
                                        <Select v-model="form.estudiante_id" :disabled="!selectedCourseParallel" required>
                                            <SelectTrigger><SelectValue placeholder="Seleccionar estudiante" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="student in availableStudents" :key="student.id" :value="student.id.toString()"
                                                    >{{ student.apellidos }}, {{ student.nombres }}</SelectItem
                                                >
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="puntos">Puntos *</Label>
                                        <Input
                                            id="puntos"
                                            v-model.number="form.puntos"
                                            type="number"
                                            min="1"
                                            max="100"
                                            required
                                            placeholder="Puntos (1-100)"
                                        />
                                    </div>
                                </div>

                                <div class="grid gap-6 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="fecha">Fecha de Asignación</Label>
                                        <Input id="fecha" v-model="form.fecha_asignacion" type="date" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="comentario">Comentario</Label>
                                        <Textarea id="comentario" v-model="form.comentario" placeholder="Comentario opcional..." />
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-4 pt-6">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="resetForm"
                                        class="border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                                    >
                                        <X class="mr-2 h-4 w-4" /> Cancelar
                                    </Button>
                                    <Button type="submit" :disabled="isSubmitting" class="bg-slate-600 text-white hover:bg-slate-700">
                                        <Save class="mr-2 h-4 w-4" /> {{ isSubmitting ? 'Guardando...' : 'Asignar Puntos' }}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- ===== TAB ASIGNACIÓN MASIVA ===== -->
                <TabsContent value="bulk">
                    <Card class="border border-slate-200 shadow-sm dark:border-slate-700">
                        <CardHeader class="bg-slate-50 dark:bg-slate-800/50">
                            <CardTitle class="flex items-center gap-3 text-slate-800 dark:text-slate-200">
                                <div class="rounded-md bg-slate-100 p-2 dark:bg-slate-700">
                                    <Users class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                </div>
                                Nueva Asignación Masiva
                            </CardTitle>
                            <CardDescription class="text-slate-600 dark:text-slate-400">
                                Asigna los mismos puntos a múltiples estudiantes de un curso.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="openBulkConfirmDialog" class="space-y-6">
                                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                                    <div class="space-y-2">
                                        <Label>Curso-Paralelo *</Label>
                                        <Select v-model="bulkForm.curso_paralelo_id" required @update:modelValue="onBulkCourseChange">
                                            <SelectTrigger><SelectValue placeholder="Seleccionar curso" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="cp in coursesParallels" :key="cp.id" :value="cp.id.toString()">{{
                                                    cp.nombre_completo
                                                }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Materia *</Label>
                                        <Select v-model="bulkForm.materia_id" :disabled="!bulkForm.curso_paralelo_id" required>
                                            <SelectTrigger><SelectValue placeholder="Seleccionar materia" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="subject in bulkAvailableSubjects"
                                                    :key="subject.id"
                                                    :value="subject.id.toString()"
                                                    >{{ subject.nombre }}</SelectItem
                                                >
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Período *</Label>
                                        <Select v-model="bulkForm.periodo_id" required>
                                            <SelectTrigger><SelectValue placeholder="Seleccionar período" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="period in periods" :key="period.id" :value="period.id.toString()">{{
                                                    period.nombre
                                                }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Puntos *</Label>
                                        <Input
                                            v-model.number="bulkForm.puntos"
                                            type="number"
                                            min="1"
                                            max="100"
                                            required
                                            placeholder="Puntos a asignar"
                                        />
                                    </div>
                                </div>

                                <div v-if="courseStudents.length > 0" class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <Label>Estudiantes del curso ({{ courseStudents.length }})</Label>
                                        <div class="flex space-x-2">
                                            <Button type="button" variant="outline" size="sm" @click="selectAllStudents">Seleccionar Todos</Button>
                                            <Button type="button" variant="outline" size="sm" @click="deselectAllStudents">Limpiar</Button>
                                        </div>
                                    </div>
                                    <div class="grid max-h-60 gap-2 overflow-y-auto rounded-lg border p-4 md:grid-cols-2 lg:grid-cols-3">
                                        <Label v-for="student in courseStudents" :key="student.id" class="flex items-center space-x-2 font-normal">
                                            <input type="checkbox" :value="student.id" v-model="bulkForm.estudiantes_ids" class="rounded" />
                                            <span>{{ student.apellidos }}, {{ student.nombres }}</span>
                                        </Label>
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-4 pt-6">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="resetBulkForm"
                                        class="border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
                                    >
                                        <X class="mr-2 h-4 w-4" /> Cancelar
                                    </Button>
                                    <Button
                                        type="submit"
                                        :disabled="isBulkSubmitting || bulkForm.estudiantes_ids.length === 0"
                                        class="bg-slate-600 text-white hover:bg-slate-700"
                                    >
                                        <Users class="mr-2 h-4 w-4" />
                                        {{ isBulkSubmitting ? 'Asignando...' : `Asignar a ${bulkForm.estudiantes_ids.length} Estudiantes` }}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- ===== TAB HISTORIAL RECIENTE ===== -->
                <TabsContent value="history">
                    <Card class="border border-slate-200 shadow-sm dark:border-slate-700">
                        <CardHeader class="bg-slate-50 dark:bg-slate-800/50">
                            <CardTitle class="flex items-center gap-3 text-slate-800 dark:text-slate-200">
                                <div class="rounded-md bg-slate-100 p-2 dark:bg-slate-700">
                                    <Clock class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                </div>
                                Asignaciones Recientes
                            </CardTitle>
                            <CardDescription class="text-slate-600 dark:text-slate-400"
                                >Últimas asignaciones de puntos que has realizado.</CardDescription
                            >
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div v-if="recentAssignments.length === 0" class="text-muted-foreground py-12 text-center">
                                <Clock class="mx-auto mb-4 h-12 w-12 opacity-50" />
                                <p>No hay asignaciones recientes.</p>
                            </div>
                            <div
                                v-for="assignment in recentAssignments"
                                :key="assignment.id"
                                class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-4 transition-colors hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:bg-slate-700"
                            >
                                <div class="flex items-center space-x-4">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 font-bold text-slate-700 dark:bg-slate-600 dark:text-slate-300"
                                    >
                                        {{ getInitials(assignment.estudiante.nombres, assignment.estudiante.apellidos) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-slate-100">
                                            {{ assignment.estudiante.apellidos }}, {{ assignment.estudiante.nombres }}
                                        </p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">
                                            {{ assignment.materia.nombre }} • {{ formatDate(assignment.fecha_asignacion) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-slate-700 dark:text-slate-300">+{{ assignment.puntos }} pts</div>
                                    <div class="text-sm text-slate-500 dark:text-slate-500">{{ assignment.periodo.nombre }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>

        <!-- Dialogs and Toaster -->
        <AlertDialog v-model:open="showConfirmDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Confirmar Asignación</AlertDialogTitle>
                    <AlertDialogDescription>
                        ¿Estás seguro de asignar <strong>{{ form.puntos }} puntos</strong> a <strong>{{ confirmData.studentName }}</strong
                        >?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="submitAssignment">Confirmar</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="showBulkConfirmDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Confirmar Asignación Masiva</AlertDialogTitle>
                    <AlertDialogDescription>
                        ¿Estás seguro de asignar <strong>{{ bulkForm.puntos }} puntos</strong> a
                        <strong>{{ bulkConfirmData.numStudents }} estudiante(s)</strong>?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="submitBulkAssignment">Confirmar</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <Toaster />
    </AppLayout>
</template>
