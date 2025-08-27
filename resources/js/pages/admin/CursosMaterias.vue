<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import { Head, router } from '@inertiajs/vue3';
import { BookOpen, Check, Edit, GraduationCap, Plus, Settings, Trash2, Users, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast, Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';

// ===== TIPOS =====
interface Curso {
    idCurso: number;
    nombre: string;
    cursoParalelos?: CursoParalelo[];
    curso_paralelos?: CursoParalelo[];
}

interface Paralelo {
    idParalelo: number;
    nombre: string;
}

interface Materia {
    idMateria: number;
    nombre: string;
}

interface CursoParalelo {
    idCursoParalelo: number;
    curso: {
        idCurso: number;
        nombre: string;
    };
    paralelo: {
        idParalelo: number;
        nombre: string;
    };
    materias: Materia[];
}

// ===== PROPS =====
const props = defineProps<{
    cursos: Curso[];
    paralelos: Paralelo[];
    materias: Materia[];
}>();

// ===== ESTADOS REACTIVOS =====
const selectedCurso = ref<number | null>(null);
const selectedParalelo = ref<number | null>(null);
const activeTab = ref('cursos');

// Estados para formularios
const showCursoDialog = ref(false);
const showParaleloDialog = ref(false);
const showMateriaDialog = ref(false);
const showAsignarMateriaDialog = ref(false);

const editMode = ref(false);
const editItem = ref<any>(null);

const formData = ref({
    nombre: '',
});

const asignarMateriaData = ref({
    idMateria: null as number | null,
});

// ===== COMPUTED PROPERTIES =====
const cursoSeleccionado = computed(() => {
    return props.cursos.find((c) => c.idCurso === selectedCurso.value);
});

const paralelosDelCurso = computed(() => {
    if (!cursoSeleccionado.value) return [];
    // El backend envía 'curso_paralelos' pero el frontend espera 'cursoParalelos'
    const paralelos = cursoSeleccionado.value.curso_paralelos || cursoSeleccionado.value.cursoParalelos || [];
    return paralelos;
});

const paraleloSeleccionado = computed(() => {
    if (!selectedParalelo.value) return null;
    return paralelosDelCurso.value.find((p: CursoParalelo) => p.paralelo.idParalelo === selectedParalelo.value);
});

const materiasDisponibles = computed(() => {
    if (!paraleloSeleccionado.value) return [];

    const materiasAsignadas = (paraleloSeleccionado.value.materias || []).map((m: Materia) => m.idMateria);
    return props.materias.filter((m: Materia) => !materiasAsignadas.includes(m.idMateria));
});

// ===== MÉTODOS =====
function seleccionarCurso(cursoId: number) {
    selectedCurso.value = cursoId;
    selectedParalelo.value = null;
}

function seleccionarParalelo(paraleloId: number) {
    selectedParalelo.value = paraleloId;
}

// ===== CRUD CURSOS =====
function abrirDialogoCurso(item?: any) {
    editMode.value = !!item;
    editItem.value = item;
    formData.value.nombre = item?.nombre || '';
    showCursoDialog.value = true;
}

async function guardarCurso() {
    if (!formData.value.nombre.trim()) {
        toast('Error', {
            description: 'El nombre del curso es requerido',
            icon: XCircle,
        });
        return;
    }

    const url = editMode.value ? `/admin/cursos/${editItem.value.idCurso}` : '/admin/cursos';
    const method = editMode.value ? 'put' : 'post';

    router[method](url, formData.value, {
        onSuccess: () => {
            toast('Éxito', {
                description: editMode.value ? 'Curso actualizado correctamente' : 'Curso creado correctamente',
                icon: Check,
            });
            showCursoDialog.value = false;
            // Limpiar el formulario
            formData.value.nombre = '';
            editMode.value = false;
            editItem.value = null;
        },
        onError: (errors: any) => {
            toast('Error', {
                description: errors.nombre?.[0] || 'Error al guardar el curso',
                icon: XCircle,
            });
        },
    });
}

// ===== CONFIRM DIALOG STATE =====
const confirmOpen = ref(false);
const confirmContext = ref<{ type: 'curso' | 'paralelo' | 'materia' | 'quitar'; id?: number } | null>(null);

function promptEliminarCurso(cursoId: number) {
    confirmContext.value = { type: 'curso', id: cursoId };
    confirmOpen.value = true;
}

// ===== CRUD PARALELOS =====
function abrirDialogoParalelo(item?: any) {
    editMode.value = !!item;
    editItem.value = item;
    formData.value.nombre = item?.nombre || '';
    showParaleloDialog.value = true;
}

async function guardarParalelo() {
    if (!formData.value.nombre.trim()) {
        toast('Error', {
            description: 'El nombre del paralelo es requerido',
            icon: XCircle,
        });
        return;
    }

    const url = editMode.value ? `/admin/paralelos/${editItem.value.idParalelo}` : '/admin/paralelos';
    const method = editMode.value ? 'put' : 'post';

    router[method](url, formData.value, {
        onSuccess: () => {
            toast('Éxito', {
                description: editMode.value ? 'Paralelo actualizado correctamente' : 'Paralelo creado correctamente',
                icon: Check,
            });
            showParaleloDialog.value = false;
            // Limpiar el formulario
            formData.value.nombre = '';
            editMode.value = false;
            editItem.value = null;
        },
        onError: (errors: any) => {
            toast('Error', {
                description: errors.nombre?.[0] || 'Error al guardar el paralelo',
                icon: XCircle,
            });
        },
    });
}

function promptEliminarParalelo(paraleloId: number) {
    confirmContext.value = { type: 'paralelo', id: paraleloId };
    confirmOpen.value = true;
}

// ===== CRUD MATERIAS =====
function abrirDialogoMateria(item?: any) {
    editMode.value = !!item;
    editItem.value = item;
    formData.value.nombre = item?.nombre || '';
    showMateriaDialog.value = true;
}

async function guardarMateria() {
    if (!formData.value.nombre.trim()) {
        toast('Error', {
            description: 'El nombre de la materia es requerido',
            icon: XCircle,
        });
        return;
    }

    const url = editMode.value ? `/admin/materias/${editItem.value.idMateria}` : '/admin/materias';
    const method = editMode.value ? 'put' : 'post';

    router[method](url, formData.value, {
        onSuccess: () => {
            toast('Éxito', {
                description: editMode.value ? 'Materia actualizada correctamente' : 'Materia creada correctamente',
                icon: Check,
            });
            showMateriaDialog.value = false;
            // Limpiar el formulario
            formData.value.nombre = '';
            editMode.value = false;
            editItem.value = null;
        },
        onError: (errors: any) => {
            toast('Error', {
                description: errors.nombre?.[0] || 'Error al guardar la materia',
                icon: XCircle,
            });
        },
    });
}

function promptEliminarMateria(materiaId: number) {
    confirmContext.value = { type: 'materia', id: materiaId };
    confirmOpen.value = true;
}

// ===== GESTIÓN DE MATERIAS POR CURSO-PARALELO =====
function abrirDialogoAsignarMateria() {
    if (!paraleloSeleccionado.value) {
        toast('Error', {
            description: 'Debes seleccionar un curso y paralelo',
            icon: XCircle,
        });
        return;
    }

    asignarMateriaData.value.idMateria = null;
    showAsignarMateriaDialog.value = true;
}

async function asignarMateria() {
    if (!asignarMateriaData.value.idMateria || !paraleloSeleccionado.value) {
        toast('Error', {
            description: 'Debes seleccionar una materia',
            icon: XCircle,
        });
        return;
    }

    router.post(
        '/admin/asignar-materia',
        {
            idCurso: selectedCurso.value,
            idParalelo: selectedParalelo.value,
            idMateria: asignarMateriaData.value.idMateria,
        },
        {
            onSuccess: () => {
                toast('Éxito', {
                    description: 'Materia asignada correctamente',
                    icon: Check,
                });
                showAsignarMateriaDialog.value = false;
                // Limpiar el formulario
                asignarMateriaData.value.idMateria = null;
            },
            onError: (errors: any) => {
                toast('Error', {
                    description: errors.error?.[0] || 'Error al asignar la materia',
                    icon: XCircle,
                });
            },
        },
    );
}

function promptQuitarMateria(materiaId: number) {
    confirmContext.value = { type: 'quitar', id: materiaId };
    confirmOpen.value = true;
}

function onConfirmDelete() {
    if (!confirmContext.value) return;
    const ctx = confirmContext.value;
    confirmOpen.value = false;

    if (ctx.type === 'curso' && ctx.id) {
        router.delete(`/admin/cursos/${ctx.id}`, {
            onSuccess: () => {
                toast('Éxito', { description: 'Curso eliminado correctamente', icon: Check });
                confirmContext.value = null;
            },
            onError: (errors: any) => {
                toast('Error', { description: errors.error?.[0] || 'Error al eliminar el curso', icon: XCircle });
            },
        });
        return;
    }

    if (ctx.type === 'paralelo' && ctx.id) {
        router.delete(`/admin/paralelos/${ctx.id}`, {
            onSuccess: () => {
                toast('Éxito', { description: 'Paralelo eliminado correctamente', icon: Check });
                confirmContext.value = null;
            },
            onError: (errors: any) => {
                toast('Error', { description: errors.error?.[0] || 'Error al eliminar el paralelo', icon: XCircle });
            },
        });
        return;
    }

    if (ctx.type === 'materia' && ctx.id) {
        router.delete(`/admin/materias/${ctx.id}`, {
            onSuccess: () => {
                toast('Éxito', { description: 'Materia eliminada correctamente', icon: Check });
                confirmContext.value = null;
            },
            onError: (errors: any) => {
                toast('Error', { description: errors.error?.[0] || 'Error al eliminar la materia', icon: XCircle });
            },
        });
        return;
    }

    if (ctx.type === 'quitar' && ctx.id) {
        router.delete('/admin/quitar-materia', {
            data: { idCurso: selectedCurso.value, idParalelo: selectedParalelo.value, idMateria: ctx.id },
            onSuccess: () => {
                toast('Éxito', { description: 'Materia quitada correctamente', icon: Check });
                confirmContext.value = null;
            },
            onError: (errors: any) => {
                toast('Error', { description: errors.error?.[0] || 'Error al quitar la materia', icon: XCircle });
            },
        });
    }
}
</script>

<template>
    <Head title="Cursos y Materias" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-6">
                <h1 class="text-3xl font-bold">Cursos y Materias</h1>
                <p class="text-muted-foreground">Gestiona los cursos, paralelos, materias y sus asignaciones</p>
            </header>

            <!-- ===== TABS PRINCIPALES ===== -->
            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="grid w-full grid-cols-4">
                    <TabsTrigger value="cursos" class="flex items-center gap-2">
                        <GraduationCap class="h-4 w-4" />
                        Cursos
                    </TabsTrigger>
                    <TabsTrigger value="paralelos" class="flex items-center gap-2">
                        <Users class="h-4 w-4" />
                        Paralelos
                    </TabsTrigger>
                    <TabsTrigger value="materias" class="flex items-center gap-2">
                        <BookOpen class="h-4 w-4" />
                        Materias
                    </TabsTrigger>
                    <TabsTrigger value="asignaciones" class="flex items-center gap-2">
                        <Settings class="h-4 w-4" />
                        Asignaciones
                    </TabsTrigger>
                </TabsList>

                <!-- ===== TAB CURSOS ===== -->
                <TabsContent value="cursos" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-semibold">Gestión de Cursos</h2>
                        <Button @click="abrirDialogoCurso()">
                            <Plus class="mr-2 h-4 w-4" />
                            Agregar Curso
                        </Button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <Card
                            v-for="curso in cursos"
                            :key="curso.idCurso"
                            class="cursor-pointer transition-shadow hover:shadow-md"
                            @click="seleccionarCurso(curso.idCurso)"
                            :class="{ 'ring-primary ring-2': selectedCurso === curso.idCurso }"
                        >
                            <CardHeader>
                                <CardTitle class="flex items-center justify-between">
                                    {{ curso.nombre }}
                                    <div class="flex gap-1">
                                        <Button variant="ghost" size="sm" @click.stop="abrirDialogoCurso(curso)">
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="sm" @click.stop="promptEliminarCurso(curso.idCurso)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </CardTitle>
                                <CardDescription> {{ (curso.curso_paralelos || curso.cursoParalelos || []).length }} paralelos </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="cursoParalelo in curso.curso_paralelos || curso.cursoParalelos || []"
                                        :key="cursoParalelo.idCursoParalelo"
                                        class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-800"
                                    >
                                        <Users class="h-3 w-3" />
                                        {{ cursoParalelo.paralelo.nombre }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- ===== TAB PARALELOS ===== -->
                <TabsContent value="paralelos" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-semibold">Gestión de Paralelos</h2>
                        <Button @click="abrirDialogoParalelo()">
                            <Plus class="mr-2 h-4 w-4" />
                            Agregar Paralelo
                        </Button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <Card v-for="paralelo in paralelos" :key="paralelo.idParalelo" class="transition-shadow hover:shadow-md">
                            <CardHeader>
                                <CardTitle class="flex items-center justify-between">
                                    Paralelo {{ paralelo.nombre }}
                                    <div class="flex gap-1">
                                        <Button variant="ghost" size="sm" @click="abrirDialogoParalelo(paralelo)">
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="sm" @click="promptEliminarParalelo(paralelo.idParalelo)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </CardTitle>
                            </CardHeader>
                        </Card>
                    </div>
                </TabsContent>

                <!-- ===== TAB MATERIAS ===== -->
                <TabsContent value="materias" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-semibold">Gestión de Materias</h2>
                        <Button @click="abrirDialogoMateria()">
                            <Plus class="mr-2 h-4 w-4" />
                            Agregar Materia
                        </Button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <Card v-for="materia in materias" :key="materia.idMateria" class="transition-shadow hover:shadow-md">
                            <CardHeader>
                                <CardTitle class="flex items-center justify-between">
                                    {{ materia.nombre }}
                                    <div class="flex gap-1">
                                        <Button variant="ghost" size="sm" @click="abrirDialogoMateria(materia)">
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="sm" @click="promptEliminarMateria(materia.idMateria)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </CardTitle>
                            </CardHeader>
                        </Card>
                    </div>
                </TabsContent>

                <!-- ===== TAB ASIGNACIONES ===== -->
                <TabsContent value="asignaciones" class="space-y-4">
                    <h2 class="text-2xl font-semibold">Asignación de Materias</h2>

                    <div class="grid gap-6 lg:grid-cols-3">
                        <!-- ===== SELECCIÓN DE CURSO ===== -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Seleccionar Curso</CardTitle>
                                <CardDescription>Elige un curso para ver sus paralelos</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-2">
                                <div
                                    v-for="curso in cursos"
                                    :key="curso.idCurso"
                                    class="hover:bg-green-400 cursor-pointer rounded-lg border p-3 transition-colors"
                                    :class="{ 'bg-primary text-primary-foreground': selectedCurso === curso.idCurso }"
                                    @click="seleccionarCurso(curso.idCurso)"
                                >
                                    <div class="font-medium">{{ curso.nombre }}</div>
                                    <div class="text-sm opacity-75">{{ (curso.curso_paralelos || curso.cursoParalelos || []).length }} paralelos</div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- ===== SELECCIÓN DE PARALELO ===== -->
                        <Card v-if="cursoSeleccionado">
                            <CardHeader>
                                <CardTitle>Seleccionar Paralelo</CardTitle>
                                <CardDescription>Elige un paralelo para gestionar sus materias</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-2">
                                <div
                                    v-for="cursoParalelo in paralelosDelCurso"
                                    :key="cursoParalelo.idCursoParalelo"
                                    class="hover:bg-green-400 cursor-pointer rounded-lg border p-3 transition-colors"
                                    :class="{ 'bg-primary text-primary-foreground': selectedParalelo === cursoParalelo.paralelo.idParalelo }"
                                    @click="seleccionarParalelo(cursoParalelo.paralelo.idParalelo)"
                                >
                                    <div class="font-medium">Paralelo {{ cursoParalelo.paralelo.nombre }}</div>
                                    <div class="text-sm opacity-75">{{ (cursoParalelo.materias || []).length }} materias</div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- ===== GESTIÓN DE MATERIAS ===== -->
                        <Card v-if="paraleloSeleccionado">
                            <CardHeader>
                                <CardTitle class="flex items-center justify-between">
                                    Materias del {{ cursoSeleccionado?.nombre }} {{ paraleloSeleccionado?.paralelo.nombre }}
                                    <Button size="sm" @click="abrirDialogoAsignarMateria">
                                        <Plus class="h-4 w-4" />
                                    </Button>
                                </CardTitle>
                                <CardDescription>Gestiona las materias asignadas a este curso-paralelo</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-2">
                                <div
                                    v-for="materia in paraleloSeleccionado.materias || []"
                                    :key="materia.idMateria"
                                    class="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <BookOpen class="h-4 w-4 text-blue-600" />
                                        <span>{{ materia.nombre }}</span>
                                    </div>
                                    <Button variant="ghost" size="sm" @click="promptQuitarMateria(materia.idMateria)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>

                                <div v-if="(paraleloSeleccionado.materias || []).length === 0" class="text-muted-foreground py-8 text-center">
                                    <BookOpen class="mx-auto mb-4 h-12 w-12 opacity-50" />
                                    <p>No hay materias asignadas</p>
                                    <p class="text-sm">Haz clic en el botón + para agregar materias</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>
            </Tabs>

            <Toaster />
            <ConfirmDelete
                :open="confirmOpen"
                :title="'Confirmar eliminación'"
                :description="
                    confirmContext?.type === 'curso'
                        ? '¿Estás seguro de que quieres eliminar este curso? Esto puede afectar paralelos relacionados.'
                        : confirmContext?.type === 'paralelo'
                        ? '¿Estás seguro de que quieres eliminar este paralelo?'
                        : confirmContext?.type === 'materia'
                        ? '¿Estás seguro de que quieres eliminar esta materia?'
                        : '¿Estás seguro de que quieres quitar esta materia del curso-paralelo seleccionado?'
                "
                @update:open="(v) => (confirmOpen = v)"
                @confirm="onConfirmDelete"
                @cancel="confirmOpen = false"
            >
                <template #icon>
                    <Trash2 class="mr-2 h-4 w-4" />
                </template>
                <template #confirmLabel>Eliminar</template>
            </ConfirmDelete>
        </div>

        <!-- ===== DIÁLOGO CURSO ===== -->
        <Dialog v-model:open="showCursoDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editMode ? 'Editar' : 'Crear' }} Curso</DialogTitle>
                    <DialogDescription> {{ editMode ? 'Modifica' : 'Agrega' }} la información del curso </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="nombre-curso">Nombre del Curso</Label>
                        <Input id="nombre-curso" v-model="formData.nombre" placeholder="Ej: Primero, Segundo, etc." />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showCursoDialog = false"> Cancelar </Button>
                    <Button @click="guardarCurso">
                        {{ editMode ? 'Actualizar' : 'Crear' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ===== DIÁLOGO PARALELO ===== -->
        <Dialog v-model:open="showParaleloDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editMode ? 'Editar' : 'Crear' }} Paralelo</DialogTitle>
                    <DialogDescription> {{ editMode ? 'Modifica' : 'Agrega' }} la información del paralelo </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="nombre-paralelo">Nombre del Paralelo</Label>
                        <Input id="nombre-paralelo" v-model="formData.nombre" placeholder="Ej: A, B, C, etc." />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showParaleloDialog = false"> Cancelar </Button>
                    <Button @click="guardarParalelo">
                        {{ editMode ? 'Actualizar' : 'Crear' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ===== DIÁLOGO MATERIA ===== -->
        <Dialog v-model:open="showMateriaDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editMode ? 'Editar' : 'Crear' }} Materia</DialogTitle>
                    <DialogDescription> {{ editMode ? 'Modifica' : 'Agrega' }} la información de la materia </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="nombre-materia">Nombre de la Materia</Label>
                        <Input id="nombre-materia" v-model="formData.nombre" placeholder="Ej: Matemáticas, Lenguaje, etc." />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showMateriaDialog = false"> Cancelar </Button>
                    <Button @click="guardarMateria">
                        {{ editMode ? 'Actualizar' : 'Crear' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ===== DIÁLOGO ASIGNAR MATERIA ===== -->
        <Dialog v-model:open="showAsignarMateriaDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Asignar Materia</DialogTitle>
                    <DialogDescription>
                        Selecciona una materia para asignar al {{ cursoSeleccionado?.nombre }} {{ paraleloSeleccionado?.paralelo.nombre }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="materia-select">Materia</Label>
                        <Select v-model="asignarMateriaData.idMateria">
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona una materia" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="materia in materiasDisponibles" :key="materia.idMateria" :value="materia.idMateria">
                                    {{ materia.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showAsignarMateriaDialog = false"> Cancelar </Button>
                    <Button @click="asignarMateria" :disabled="!asignarMateriaData.idMateria"> Asignar </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
