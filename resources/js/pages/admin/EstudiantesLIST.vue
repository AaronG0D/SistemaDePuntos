<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pagination, PaginationContent, PaginationItem, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { Estudiante } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, Eye, Search, SquarePen, Trash2, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { toast, Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';

// ===== UTILIDADES =====
function debounce<T extends (...args: any[]) => any>(func: T, wait: number): T {
    let timeout: NodeJS.Timeout;
    return ((...args: any[]) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    }) as T;
}

// ===== TIPOS Y INTERFACES =====
interface Curso {
    idCurso: number;
    nombre: string;
}

interface Paralelo {
    idParalelo: number;
    nombre: string;
}

interface PaginacionEstudiantes {
    total: number;
    per_page: number;
    data: Estudiante[];
    last_page: number;
    current_page: number;
}

interface EditEstudianteData {
    user: {
        nombres: string;
        primerApellido: string;
        segundoApellido: string;
        email: string;
    };
    curso_paralelo: {
        idCurso: number | null;
        idParalelo: number | null;
    };
}

// ===== PROPS =====
const props = defineProps<{
    estudiantes: PaginacionEstudiantes;
    cursos: Curso[];
    paralelos: Paralelo[];
}>();

// ===== ESTADOS REACTIVOS =====
const selectedCurso = ref<'all' | number>('all');
const selectedParalelo = ref<'all' | number>('all');
const searchQuery = ref('');
const dialogOpen = ref(false);
const editEstudiante = ref<Estudiante | null>(null);
const editCurso = ref<number | null>(null);
const editParalelo = ref<number | null>(null);
const isInitialized = ref(false); // Bandera para controlar inicialización

// ===== COMPUTED PROPERTIES =====
const filteredEstudiantes = computed(() => {
    return props.estudiantes.data;
});

// ===== MÉTODOS DE NAVEGACIÓN =====
function goToPage(page: number) {
    const params = {
        page,
        curso: selectedCurso.value !== 'all' ? selectedCurso.value : undefined,
        paralelo: selectedParalelo.value !== 'all' ? selectedParalelo.value : undefined,
        search: searchQuery.value || undefined,
    };

    router.get('/admin/estudiantes', params, {
        preserveState: false,
        preserveScroll: true,
        replace: true,
    });
}

// Función para aplicar filtros
function aplicarFiltros() {
    const params: Record<string, any> = {
        page: 1, // Resetear a la primera página
    };

    // Solo agregar parámetros si tienen valores válidos
    if (selectedCurso.value !== 'all' && selectedCurso.value !== null) {
        params.curso = selectedCurso.value;
    }

    if (selectedParalelo.value !== 'all' && selectedParalelo.value !== null) {
        params.paralelo = selectedParalelo.value;
    }

    if (searchQuery.value && searchQuery.value.trim() !== '') {
        params.search = searchQuery.value.trim();
    }

    console.log('Aplicando filtros:', params); // Debug temporal

    router.get('/admin/estudiantes', params, {
        preserveState: true, // Mantener el estado de los inputs
        preserveScroll: true,
        replace: true,
        onError: (errors) => {
            console.error('Error al aplicar filtros:', errors);
            toast('Error al filtrar', {
                description: 'No se pudieron aplicar los filtros',
                icon: XCircle,
                position: 'top-center',
            });
        },
    });
}

// Función debounced para búsqueda
const aplicarFiltrosDebounced = debounce(aplicarFiltros, 300);

// ===== MÉTODOS DE FILTROS =====
function handleSearchChange() {
    if (isInitialized.value) {
        aplicarFiltrosDebounced();
    }
}

function handleCursoChange() {
    if (isInitialized.value) {
        aplicarFiltros();
    }
}

function handleParaleloChange() {
    if (isInitialized.value) {
        aplicarFiltros();
    }
}

// ===== MÉTODOS DEL DIÁLOGO =====
function openEditDialog(estudiante: Estudiante) {
    editEstudiante.value = JSON.parse(JSON.stringify(estudiante));
    editCurso.value = estudiante.curso_paralelo?.curso?.idCurso || null;
    editParalelo.value = estudiante.curso_paralelo?.paralelo?.idParalelo || null;
    dialogOpen.value = true;
}

function closeEditDialog() {
    dialogOpen.value = false;
    editEstudiante.value = null;
    editCurso.value = null;
    editParalelo.value = null;
}

function guardarCambios() {
    if (!editEstudiante.value) return;

    const datos = {
        user: {
            nombres: editEstudiante.value.user.nombres,
            primerApellido: editEstudiante.value.user.primerApellido,
            segundoApellido: editEstudiante.value.user.segundoApellido || '',
            email: editEstudiante.value.user.email,
        },
        curso_paralelo: {
            idCurso: editCurso.value,
            idParalelo: editParalelo.value,
        },
    } as Record<string, any>;

    router.put(`/admin/estudiantes/${editEstudiante.value.idUser}`, datos, {
        onSuccess: () => {
            closeEditDialog();
            showSuccessToast();
        },
        onError: () => {
            showErrorToast();
        },
    });
}

// ===== MÉTODOS DE TOAST =====
function showSuccessToast() {
    toast('Estudiante actualizado', {
        description: 'Los cambios han sido guardados correctamente',
        icon: Check,
        position: 'top-center',
        action: {
            label: 'Ver detalles',
            onClick: () => router.visit(`/admin/estudiantes/${editEstudiante.value?.idUser}`),
        },
    });
}

function showErrorToast() {
    toast('Error al actualizar', {
        description: 'No se pudo actualizar el estudiante',
        icon: XCircle,
        position: 'top-center',
        action: {
            label: 'Intentar de nuevo',
            onClick: () => guardarCambios(),
        },
    });
}

// ===== MÉTODOS DE ELIMINACIÓN =====
function eliminarEstudiante(estudianteId: number) {
    router.delete(`/admin/estudiantes/${estudianteId}`, {
        onSuccess: () => {
            toast('Estudiante eliminado', {
                description: 'El estudiante ha sido eliminado correctamente',
                icon: Check,
                position: 'top-center',
            });
        },
        onError: () => {
            toast('Error al eliminar', {
                description: 'No se pudo eliminar el estudiante',
                icon: XCircle,
                position: 'top-center',
            });
        },
    });
}

// ===== WATCHERS =====
watch(dialogOpen, (open) => {
    if (open && editEstudiante.value) {
        editCurso.value = editEstudiante.value.curso_paralelo?.curso?.idCurso ?? null;
        editParalelo.value = editEstudiante.value.curso_paralelo?.paralelo?.idParalelo ?? null;
    }
});

// Inicializar filtros desde la URL
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || '';

    const cursoParam = urlParams.get('curso');
    selectedCurso.value = cursoParam ? Number(cursoParam) : 'all';

    const paraleloParam = urlParams.get('paralelo');
    selectedParalelo.value = paraleloParam ? Number(paraleloParam) : 'all';

    // Verificar si hay un estudiante para editar desde la vista de detalles
    const editEstudianteId = urlParams.get('editEstudianteId');
    if (editEstudianteId) {
        const estudiante = props.estudiantes.data.find((e) => e.idUser === Number(editEstudianteId));
        if (estudiante) {
            openEditDialog(estudiante);
        }
    }

    isInitialized.value = true;
});

// Debug temporal - ver datos que llegan
watch(
    () => props.estudiantes,
    (newData) => {
        console.log('Datos de estudiantes actualizados:', newData);
    },
    { deep: true },
);

watch(editCurso, (val) => {
    if (editEstudiante.value && val !== null) {
        if (!editEstudiante.value.curso_paralelo) {
            editEstudiante.value.curso_paralelo = {
                idCursoParalelo: 0,
                curso: undefined,
                paralelo: undefined,
            };
        }
        editEstudiante.value.curso_paralelo.curso = props.cursos.find((c) => c.idCurso === val);
    }
});

watch(editParalelo, (val) => {
    if (editEstudiante.value && val !== null) {
        if (!editEstudiante.value.curso_paralelo) {
            editEstudiante.value.curso_paralelo = {
                idCursoParalelo: 0,
                curso: undefined,
                paralelo: undefined,
            };
        }
        editEstudiante.value.curso_paralelo.paralelo = props.paralelos.find((p) => p.idParalelo === val);
    }
});
</script>

<template>
    <Head title="Estudiantes" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-6">
                <h1 class="text-3xl font-bold">Estudiantes</h1>
                <p class="text-muted-foreground">Gestiona la lista de estudiantes del sistema</p>
            </header>

            <!-- ===== CONTROLES DE FILTRADO ===== -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Input v-model="searchQuery" placeholder="Buscar estudiantes..." class="w-[300px]" @input="handleSearchChange">
                        <template #prefix>
                            <Search class="text-muted-foreground h-4 w-4" />
                        </template>
                    </Input>

                    <Select v-model="selectedCurso" @update:model-value="handleCursoChange">
                        <SelectTrigger>
                            <SelectValue placeholder="Todos los cursos" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>Cursos</SelectLabel>
                                <SelectItem value="all">Todos los cursos</SelectItem>
                                <SelectItem v-for="curso in cursos" :key="curso.idCurso" :value="curso.idCurso">
                                    {{ curso.nombre }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>

                    <Select v-model="selectedParalelo" @update:model-value="handleParaleloChange">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue placeholder="Todos los paralelos" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>Paralelos</SelectLabel>
                                <SelectItem value="all">Todos los paralelos</SelectItem>
                                <SelectItem v-for="paralelo in paralelos" :key="paralelo.idParalelo" :value="paralelo.idParalelo">
                                    {{ paralelo.nombre }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>

                <Button as-child size="sm" class="bg-primary">
                    <Link href="/admin/estudiantes/create">Agregar Estudiante</Link>
                </Button>
            </div>

            <!-- ===== TABLA DE ESTUDIANTES ===== -->
            <div class="min-h-[500px] rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombres</TableHead>
                            <TableHead>Apellidos</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Curso</TableHead>
                            <TableHead>Paralelo</TableHead>
                            <TableHead>Puntos</TableHead>
                            <TableHead>Código QR</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="estudiante in filteredEstudiantes" :key="estudiante.idUser" class="hover:bg-muted/50">
                            <TableCell>{{ estudiante.user?.nombres }}</TableCell>
                            <TableCell>
                                {{ estudiante.user?.primerApellido }}
                                {{ estudiante.user?.segundoApellido }}
                            </TableCell>
                            <TableCell>{{ estudiante.user?.email }}</TableCell>
                            <TableCell>
                                {{ estudiante.curso_paralelo?.curso?.nombre ?? '-' }}
                            </TableCell>
                            <TableCell>
                                {{ estudiante.curso_paralelo?.paralelo?.nombre ?? '-' }}
                            </TableCell>
                            <TableCell>
                                {{ estudiante.user?.puntaje?.puntajeTotal ?? '-' }}
                            </TableCell>
                            <TableCell>
                                <img v-if="estudiante.user?.qr_codigo" :src="estudiante.user.qr_codigo" alt="QR Code" class="h-8 w-8" />
                            </TableCell>
                            <TableCell class="text-right">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="sm" @click="openEditDialog(estudiante)">
                                                <SquarePen />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Editar estudiante</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="`/admin/estudiantes/${estudiante.idUser}`">
                                                    <Eye />
                                                </Link>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Ver detalles</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="destructive" size="sm" @click="eliminarEstudiante(estudiante.idUser)">
                                                <Trash2 />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Eliminar estudiante</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- ===== PAGINACIÓN ===== -->
            <Pagination
                v-if="estudiantes.last_page > 1"
                class="bg-rgb(214, 219, 216)"
                :total="estudiantes.total"
                :items-per-page="estudiantes.per_page"
                :default-page="estudiantes.current_page"
                v-slot="{ page }"
            >
                <PaginationContent>
                    <PaginationPrevious v-if="estudiantes.current_page > 1" @click="goToPage(estudiantes.current_page - 1)" />

                    <template v-for="p in estudiantes.last_page" :key="p">
                        <PaginationItem :value="p" :is-active="p === estudiantes.current_page" @click="goToPage(p)">
                            {{ p }}
                        </PaginationItem>
                    </template>

                    <PaginationNext v-if="estudiantes.current_page < estudiantes.last_page" @click="goToPage(estudiantes.current_page + 1)" />
                </PaginationContent>
            </Pagination>

            <!-- ===== DIÁLOGO DE EDICIÓN ===== -->
            <Dialog v-model:open="dialogOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Editar estudiante</DialogTitle>
                        <DialogDescription> Modifica los datos del estudiante y guarda los cambios. </DialogDescription>
                    </DialogHeader>

                    <div v-if="editEstudiante" class="grid gap-4 py-4">
                        <!-- Nombres -->
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="nombres" class="text-right">Nombres</Label>
                            <Input id="nombres" v-model="editEstudiante.user.nombres" class="col-span-3" />
                        </div>

                        <!-- Primer Apellido -->
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="primerApellido" class="text-right">Primer Apellido</Label>
                            <Input id="primerApellido" v-model="editEstudiante.user.primerApellido" class="col-span-3" />
                        </div>

                        <!-- Segundo Apellido -->
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="segundoApellido" class="text-right">Segundo Apellido</Label>
                            <Input id="segundoApellido" v-model="editEstudiante.user.segundoApellido" class="col-span-3" />
                        </div>

                        <!-- Email -->
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="email" class="text-right">Email</Label>
                            <Input id="email" v-model="editEstudiante.user.email" class="col-span-3" />
                        </div>

                        <!-- Curso -->
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Curso</Label>
                            <Select v-model="editCurso" class="col-span-3">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona un curso" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null">Sin curso</SelectItem>
                                    <SelectItem v-for="curso in cursos" :key="curso.idCurso" :value="curso.idCurso">
                                        {{ curso.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Paralelo -->
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label class="text-right">Paralelo</Label>
                            <Select v-model="editParalelo" class="col-span-3">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona un paralelo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null">Sin paralelo</SelectItem>
                                    <SelectItem v-for="paralelo in paralelos" :key="paralelo.idParalelo" :value="paralelo.idParalelo">
                                        {{ paralelo.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button @click="guardarCambios">Guardar cambios</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Toaster />
        </div>
    </AppLayout>
</template>
