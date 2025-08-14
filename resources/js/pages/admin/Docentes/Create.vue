<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { AlertCircle, MinusCircle, PlusCircle } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';

interface Materia {
    idMateria: number;
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

interface AsignacionMateria {
    idMateria: number | null;
    idCurso: number | null;
    idParalelo: number | null;
    idCursoParalelo: number | null;
    materia?: Materia | null;
    cursoParalelo?: CursoParalelo | null;
}

interface Usuario {
    id: number;
    nombres: string;
    primerApellido: string;
    segundoApellido: string;
    email: string;
}

const props = defineProps<{
    usuariosDisponibles: Usuario[];
    materias: Materia[];
    cursos: {
        idCurso: number;
        nombre: string;
        paralelos: {
            idParalelo: number;
            nombre: string;
            materias: Materia[];
        }[];
    }[];
    paralelos: Paralelo[];
    cursoParalelos: CursoParalelo[];
}>();

const form = ref({
    idUser: null as number | null,
    materias_cursos: [] as AsignacionMateria[],
});

const errors = ref<Record<string, string>>({});
const showConflictAlert = ref(false);
const conflictMessage = ref('');
const materiasDisponiblesPorAsignacion = ref<Record<number, Materia[]>>({});
const isLoadingMaterias = ref<Record<number, boolean>>({});

// Agregar nueva asignación de materia
function agregarAsignacion() {
    const newIndex = form.value.materias_cursos.length;
    form.value.materias_cursos.push({
        idMateria: null,
        idCurso: null,
        idParalelo: null,
        idCursoParalelo: null,
        materia: null,
        cursoParalelo: null,
    });
    materiasDisponiblesPorAsignacion.value[newIndex] = [];
    isLoadingMaterias.value[newIndex] = false;
}

// Remover una asignación de materia
function removerAsignacion(index: number) {
    form.value.materias_cursos.splice(index, 1);
    delete materiasDisponiblesPorAsignacion.value[index];
    delete isLoadingMaterias.value[index];
    // Reindexar las claves
    const newMaterias: Record<number, Materia[]> = {};
    const newLoading: Record<number, boolean> = {};
    form.value.materias_cursos.forEach((_, newIndex) => {
        const oldIndex = newIndex >= index ? newIndex + 1 : newIndex;
        newMaterias[newIndex] = materiasDisponiblesPorAsignacion.value[oldIndex] || [];
        newLoading[newIndex] = isLoadingMaterias.value[oldIndex] || false;
    });
    materiasDisponiblesPorAsignacion.value = newMaterias;
    isLoadingMaterias.value = newLoading;
}

// Obtener materias disponibles para una asignación específica
async function obtenerMateriasDisponibles(index: number, idCurso: number, idParalelo: number) {
    if (!idCurso || !idParalelo) {
        materiasDisponiblesPorAsignacion.value[index] = [];
        return;
    }

    isLoadingMaterias.value[index] = true;
    
    try {
        const response = await fetch(`/admin/materias-disponibles?idCurso=${idCurso}&idParalelo=${idParalelo}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        });

        const data = await response.json();
        if (response.ok) {
            // Filtrar materias ya asignadas en otras asignaciones de este formulario
            const materiasYaAsignadas = form.value.materias_cursos
                .filter((_, i) => i !== index && form.value.materias_cursos[i].idMateria)
                .map(a => a.idMateria);
            
            const materiasDisponibles = data.materiasDisponibles.filter(
                (materia: Materia) => !materiasYaAsignadas.includes(materia.idMateria)
            );
            
            materiasDisponiblesPorAsignacion.value[index] = materiasDisponibles;
        } else {
            console.error('Error al obtener materias:', data.error);
            materiasDisponiblesPorAsignacion.value[index] = [];
        }
    } catch (error) {
        console.error('Error al obtener materias disponibles:', error);
        materiasDisponiblesPorAsignacion.value[index] = [];
    } finally {
        isLoadingMaterias.value[index] = false;
    }
}

// Verificar conflictos antes de guardar
async function verificarConflictos() {
    // Verificar duplicados en el formulario actual
    const asignaciones = form.value.materias_cursos;
    const combinaciones = new Set();
    
    for (const asignacion of asignaciones) {
        if (asignacion.idCurso && asignacion.idParalelo && asignacion.idMateria) {
            const key = `${asignacion.idCurso}-${asignacion.idParalelo}-${asignacion.idMateria}`;
            if (combinaciones.has(key)) {
                showConflictAlert.value = true;
                conflictMessage.value = 'No puedes asignar la misma materia dos veces al mismo curso y paralelo.';
                return true;
            }
            combinaciones.add(key);
        }
    }
    
    return false;
}

// Manejar el envío del formulario
async function handleSubmit() {
    // Verificar conflictos
    if (await verificarConflictos()) {
        return;
    }

    router.post('/admin/docentes', form.value, {
        onSuccess: () => {
            toast.success('Docente creado correctamente');
        },
        onError: (e) => {
            errors.value = e;
            toast.error('Error al crear el docente');
        },
    });
}

// Watchers para cargar materias cuando cambian curso y paralelo
watch(
    () => form.value.materias_cursos,
    (newAsignaciones) => {
        newAsignaciones.forEach((asignacion, index) => {
            if (asignacion.idCurso && asignacion.idParalelo) {
                obtenerMateriasDisponibles(index, asignacion.idCurso, asignacion.idParalelo);
            }
        });
    },
    { deep: true }
);
</script>

<template>
    <Head title="Crear Docente" />

    <AppLayout :breadcrumbs="[
  { title: 'Gestión Académica', href: route('admin.cursos.materias') },
  { title: 'Docentes', href: route('admin.docentes') },
  { title: 'Crear', href: route('admin.docentes.create') },
]">
        <div class="container mx-auto py-6">
            <div class="mx-auto max-w-2xl">
                <h1 class="mb-6 text-2xl font-bold">Crear Nuevo Docente</h1>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div class="grid gap-4">
                        <!-- Selección de Usuario -->
                        <div class="space-y-4">
                            <h2 class="text-lg font-semibold">Seleccionar Usuario</h2>

                            <div class="space-y-2">
                                <Label for="idUser">Usuario Disponible</Label>
                                <Select v-model="form.idUser">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona un usuario" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="usuario in usuariosDisponibles" :key="usuario.id" :value="usuario.id">
                                            {{ usuario.nombres }} {{ usuario.primerApellido }} {{ usuario.segundoApellido }} ({{ usuario.email }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="errors.idUser" class="text-sm text-red-500">
                                    {{ errors.idUser }}
                                </span>
                            </div>
                        </div>

                        <!-- Asignación de Materias -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold">Asignación de Materias</h2>
                                <Button type="button" variant="outline" size="sm" @click="agregarAsignacion">
                                    <PlusCircle class="mr-2 h-4 w-4" />
                                    Agregar Materia
                                </Button>
                            </div>

                            <div v-for="(asignacion, index) in form.materias_cursos" :key="index" class="space-y-4 rounded-lg border p-4">
                                <div class="flex justify-between">
                                    <h3 class="font-medium">Asignación {{ index + 1 }}</h3>
                                    <Button type="button" variant="destructive" size="sm" @click="removerAsignacion(index)">
                                        <MinusCircle class="h-4 w-4" />
                                    </Button>
                                </div>

                                <!-- Materia -->
                                <!-- Curso -->
                                <div class="space-y-2">
                                    <Label>Curso</Label>
                                    <Select v-model="asignacion.idCurso">
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

                                <!-- Paralelo -->
                                <div class="space-y-2">
                                    <Label>Paralelo</Label>
                                    <Select v-model="asignacion.idParalelo" :disabled="!asignacion.idCurso">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona un paralelo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="paralelo in cursos.find((c) => c.idCurso === asignacion.idCurso)?.paralelos || []"
                                                :key="paralelo.idParalelo"
                                                :value="paralelo.idParalelo"
                                            >
                                                {{ paralelo.nombre }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Materia -->
                                <div class="space-y-2">
                                    <Label>Materia</Label>
                                    <Select v-model="asignacion.idMateria" :disabled="!asignacion.idCurso || !asignacion.idParalelo || isLoadingMaterias[index]">
                                        <SelectTrigger>
                                            <SelectValue :placeholder="isLoadingMaterias[index] ? 'Cargando materias...' : 'Selecciona una materia'" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="materia in materiasDisponiblesPorAsignacion[index] || []"
                                                :key="materia.idMateria"
                                                :value="materia.idMateria"
                                            >
                                                {{ materia.nombre }}
                                            </SelectItem>
                                            <div v-if="!materiasDisponiblesPorAsignacion[index]?.length && !isLoadingMaterias[index]" class="p-2 text-sm text-muted-foreground">
                                                No hay materias disponibles
                                            </div>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <!-- Alerta de conflictos -->
                            <div v-if="showConflictAlert" class="rounded-md bg-yellow-50 p-4">
                                <div class="flex">
                                    <AlertCircle class="h-5 w-5 text-yellow-400" />
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800">Alerta de conflicto</h3>
                                        <div class="mt-2 text-sm text-yellow-700">
                                            {{ conflictMessage }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex justify-end gap-4">
                        <Button type="button" variant="outline" @click="router.get('/admin/docentes')">Cancelar</Button>
                        <Button type="submit">Crear Docente</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
