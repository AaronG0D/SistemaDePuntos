<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft, BookOpen, Check, GraduationCap, Plus, Trash2, User, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { toast, Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';

// ===== TIPOS Y INTERFACES =====
interface Materia {
    idMateria: number;
    nombre: string;
}

interface Curso {
    idCurso: number;
    nombre: string;
}

interface Paralelo {
    idParalelo: number;
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
}

interface Asignacion {
    idMateria: number;
    idCursoParalelo: number;
    materia: {
        idMateria: number;
        nombre: string;
    };
    curso_paralelo: {
        idCursoParalelo: number;
        curso: {
            idCurso: number;
            nombre: string;
        };
        paralelo: {
            idParalelo: number;
            nombre: string;
        };
    };
}

interface Docente {
    idDocente: number;
    user: {
        id: number;
        nombres: string;
        primerApellido: string;
        segundoApellido?: string;
        email: string;
    };
    docente_materia_cursos: Asignacion[];
}

// ===== PROPS =====
const props = defineProps<{
    docente: Docente;
    materias: Materia[];
    cursos: Curso[];
    paralelos: Paralelo[];
    cursoParalelos: CursoParalelo[];
    materiasPorCurso?: Record<number, Materia[]>;
    materiasPorCursoParalelo?: Record<number, Materia[]>;
}>();

// ===== ESTADOS REACTIVOS =====
const editUser = ref({
    nombres: props.docente.user.nombres,
    primerApellido: props.docente.user.primerApellido,
    segundoApellido: props.docente.user.segundoApellido || '',
    email: props.docente.user.email,
});

const asignaciones = ref<
    Array<{
        idMateria: number;
        idCursoParalelo: number;
        materia: Materia | null;
        cursoParalelo: CursoParalelo | null;
    }>
>([]);

const showAddDialog = ref(false);
const newAsignacion = ref({
    idCurso: null as number | null,
    idParalelo: null as number | null,
    idMateria: null as number | null,
    idCursoParalelo: null as number | null,
});

const isSubmitting = ref(false);

// ===== COMPUTED PROPERTIES =====
const paralelosDisponibles = computed(() => {
    if (!newAsignacion.value.idCurso) {
        return [];
    }
    // Mostrar todos los paralelos siempre
    return props.paralelos;
});

const materiasDisponibles = computed(() => {
    if (!cursoParaleloSeleccionado.value) return [];
    const todas = props.materiasPorCursoParalelo?.[cursoParaleloSeleccionado.value.idCursoParalelo] || [];
    const yaAsignadas = asignaciones.value
        .filter((a) => cursoParaleloSeleccionado.value && a.idCursoParalelo === cursoParaleloSeleccionado.value.idCursoParalelo)
        .map((a) => a.idMateria);
    return todas.filter((m) => !yaAsignadas.includes(m.idMateria));
});

const cursoParaleloSeleccionado = computed(() => {
    if (!newAsignacion.value.idCurso || !newAsignacion.value.idParalelo) {
        return null;
    }

    return props.cursoParalelos.find(
        (cp) => cp.curso.idCurso === newAsignacion.value.idCurso && cp.paralelo.idParalelo === newAsignacion.value.idParalelo,
    );
});

const canAddAsignacion = computed(() => {
    return newAsignacion.value.idCurso && newAsignacion.value.idParalelo && newAsignacion.value.idMateria && cursoParaleloSeleccionado.value;
});

// ===== MÉTODOS =====
function inicializarAsignaciones() {
    asignaciones.value = props.docente.docente_materia_cursos.map((asignacion) => ({
        idMateria: asignacion.idMateria,
        idCursoParalelo: asignacion.idCursoParalelo,
        materia: asignacion.materia,
        cursoParalelo: asignacion.curso_paralelo,
    }));
}

async function verificarConflictos() {
    if (!canAddAsignacion.value || !cursoParaleloSeleccionado.value) {
        return false;
    }

    // Log de depuración
    console.log('Verificando conflictos con:', {
        idDocente: props.docente.idDocente,
        idMateria: newAsignacion.value.idMateria,
        idCursoParalelo: cursoParaleloSeleccionado.value.idCursoParalelo,
    });

    try {
        const response = await axios.post('/admin/verificar-conflictos', {
            idDocente: props.docente.idDocente,
            idMateria: newAsignacion.value.idMateria,
            idCursoParalelo: cursoParaleloSeleccionado.value.idCursoParalelo,
        });

        const data = response.data;

        if (data.conflicto) {
            toast('Conflicto detectado', {
                description: `${data.mensaje}. La materia ${data.detalles.materia} ya está asignada al ${data.detalles.curso} ${data.detalles.paralelo} por ${data.detalles.nombres} ${data.detalles.primerApellido}`,
                icon: XCircle,
                position: 'top-center',
            });
            return false;
        }

        return true;
    } catch (error) {
        console.error('Error verificando conflictos:', error);
        toast('Error', {
            description: 'No se pudo verificar los conflictos',
            icon: XCircle,
            position: 'top-center',
        });
        return false;
    }
}

async function agregarAsignacion() {
    if (!canAddAsignacion.value || !cursoParaleloSeleccionado.value) {
        toast('Error', {
            description: 'Debes seleccionar curso, paralelo y materia válidos',
            icon: XCircle,
            position: 'top-center',
        });
        return;
    }

    // Verificar conflictos antes de agregar
    const sinConflictos = await verificarConflictos();
    if (!sinConflictos) {
        return;
    }

    const materia = props.materias.find((m) => m.idMateria === newAsignacion.value.idMateria);

    if (materia && cursoParaleloSeleccionado.value && cursoParaleloSeleccionado.value.curso && cursoParaleloSeleccionado.value.paralelo) {
        asignaciones.value.push({
            idMateria: newAsignacion.value.idMateria!,
            idCursoParalelo: cursoParaleloSeleccionado.value.idCursoParalelo,
            materia: materia,
            cursoParalelo: cursoParaleloSeleccionado.value,
        });

        // Resetear formulario
        newAsignacion.value = {
            idCurso: null,
            idParalelo: null,
            idMateria: null,
            idCursoParalelo: null,
        };
        showAddDialog.value = false;

        toast('Asignación agregada', {
            description: `${materia.nombre} asignada a ${cursoParaleloSeleccionado.value.curso.nombre} ${cursoParaleloSeleccionado.value.paralelo.nombre}`,
            icon: Check,
            position: 'top-center',
        });
    } else {
        toast('Error', {
            description: 'Debes seleccionar curso, paralelo y materia válidos',
            icon: XCircle,
            position: 'top-center',
        });
    }
}

function eliminarAsignacion(index: number) {
    const asignacion = asignaciones.value[index];
    asignaciones.value.splice(index, 1);

    toast('Asignación eliminada', {
        description: `${asignacion.materia?.nombre} removida de ${asignacion.cursoParalelo?.curso.nombre} ${asignacion.cursoParalelo?.paralelo.nombre}`,
        icon: Check,
        position: 'top-center',
    });
}

function guardarCambios() {
    if (isSubmitting.value) return;

    isSubmitting.value = true;

    const datos = {
        user: editUser.value,
        materias_cursos: asignaciones.value.map((asignacion) => ({
            idMateria: asignacion.idMateria,
            idCursoParalelo: asignacion.idCursoParalelo,
        })),
    };

    router.put(`/admin/docentes/${props.docente.idDocente}`, datos, {
        onSuccess: () => {
            toast('Docente actualizado', {
                description: 'Los cambios han sido guardados correctamente',
                icon: Check,
                position: 'top-center',
            });
            router.visit(`/admin/docentes/${props.docente.idDocente}`);
        },
        onError: (errors) => {
            console.error('Error al actualizar:', errors);
            toast('Error al actualizar', {
                description: 'No se pudo actualizar el docente',
                icon: XCircle,
                position: 'top-center',
            });
            isSubmitting.value = false;
        },
    });
}

// ===== WATCHERS =====
watch(
    () => newAsignacion.value.idCurso,
    (newCurso) => {
        // Resetear paralelo y materia cuando cambia el curso
        newAsignacion.value.idParalelo = null;
        newAsignacion.value.idMateria = null;
    },
);

watch(
    () => newAsignacion.value.idParalelo,
    (newParalelo) => {
        // Resetear materia cuando cambia el paralelo
        newAsignacion.value.idMateria = null;
    },
);

// ===== LIFECYCLE =====
onMounted(() => {
    inicializarAsignaciones();
});
</script>

<template>
    <Head title="Editar Docente" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-6">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="`/admin/docentes/${docente.idDocente}`">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold">Editar Docente</h1>
                        <p class="text-muted-foreground">Modifica la información del docente y sus asignaciones</p>
                    </div>
                </div>
            </header>

            <!-- ===== FORMULARIO ===== -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- ===== INFORMACIÓN PERSONAL ===== -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Información Personal
                        </CardTitle>
                        <CardDescription> Datos básicos del docente </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="nombres">Nombres</Label>
                                <Input id="nombres" v-model="editUser.nombres" placeholder="Ingrese los nombres" />
                            </div>
                            <div class="space-y-2">
                                <Label for="primerApellido">Primer Apellido</Label>
                                <Input id="primerApellido" v-model="editUser.primerApellido" placeholder="Ingrese el primer apellido" />
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="segundoApellido">Segundo Apellido</Label>
                                <Input id="segundoApellido" v-model="editUser.segundoApellido" placeholder="Ingrese el segundo apellido (opcional)" />
                            </div>
                            <div class="space-y-2">
                                <Label for="email">Email</Label>
                                <Input id="email" v-model="editUser.email" type="email" placeholder="Ingrese el email" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- ===== ASIGNACIONES ===== -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BookOpen class="h-5 w-5" />
                            Asignaciones de Materias
                        </CardTitle>
                        <CardDescription> Materias y cursos asignados al docente </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- ===== LISTA DE ASIGNACIONES ===== -->
                        <div v-if="asignaciones.length > 0" class="space-y-2">
                            <div
                                v-for="(asignacion, index) in asignaciones"
                                :key="`${asignacion.idMateria}-${asignacion.idCursoParalelo}`"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <BookOpen class="h-4 w-4 text-blue-600" />
                                        <span class="font-medium">{{ asignacion.materia?.nombre }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <GraduationCap class="h-4 w-4 text-green-600" />
                                        <span class="text-muted-foreground text-sm">
                                            {{ asignacion.cursoParalelo?.curso.nombre }} - {{ asignacion.cursoParalelo?.paralelo.nombre }}
                                        </span>
                                    </div>
                                </div>
                                <Button variant="ghost" size="sm" @click="eliminarAsignacion(index)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- ===== MENSAJE SIN ASIGNACIONES ===== -->
                        <div v-else class="text-muted-foreground py-8 text-center">
                            <BookOpen class="mx-auto mb-4 h-12 w-12 opacity-50" />
                            <p>No hay asignaciones de materias</p>
                            <p class="text-sm">Agrega materias y cursos al docente</p>
                        </div>

                        <!-- ===== BOTÓN AGREGAR ===== -->
                        <Button variant="outline" class="w-full" @click="showAddDialog = true">
                            <Plus class="mr-2 h-4 w-4" />
                            Agregar Asignación
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- ===== BOTONES DE ACCIÓN ===== -->
            <div class="mt-6 flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link :href="`/admin/docentes/${docente.idDocente}`"> Cancelar </Link>
                </Button>
                <Button @click="guardarCambios" :disabled="isSubmitting">
                    <Check v-if="!isSubmitting" class="mr-2 h-4 w-4" />
                    {{ isSubmitting ? 'Guardando...' : 'Guardar Cambios' }}
                </Button>
            </div>

            <Toaster />
        </div>

        <!-- ===== DIÁLOGO AGREGAR ASIGNACIÓN ===== -->
        <Dialog v-model:open="showAddDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Agregar Asignación</DialogTitle>
                    <DialogDescription> Selecciona el curso, materia y paralelo para asignar al docente </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <!-- ===== SELECCIÓN DE CURSO ===== -->
                    <div class="space-y-2">
                        <Label for="curso">Curso</Label>
                        <Select v-model="newAsignacion.idCurso">
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona un curso" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="curso in cursos" :key="curso.idCurso" :value="curso.idCurso">
                                    {{ curso.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- ===== SELECCIÓN DE PARALELO ===== -->
                    <div class="space-y-2">
                        <Label for="paralelo">Paralelo</Label>
                        <Select v-model="newAsignacion.idParalelo" :disabled="!newAsignacion.idCurso">
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona un paralelo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="paralelo in paralelosDisponibles" :key="paralelo.idParalelo" :value="paralelo.idParalelo">
                                    {{ paralelo.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- ===== SELECCIÓN DE MATERIA ===== -->
                    <div class="space-y-2">
                        <Label for="materia">Materia</Label>
                        <Select v-model="newAsignacion.idMateria" :disabled="!newAsignacion.idCurso || !newAsignacion.idParalelo">
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
                    <Button variant="outline" @click="showAddDialog = false"> Cancelar </Button>
                    <Button @click="agregarAsignacion" :disabled="!canAddAsignacion">
                        <Plus class="mr-2 h-4 w-4" />
                        Agregar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
