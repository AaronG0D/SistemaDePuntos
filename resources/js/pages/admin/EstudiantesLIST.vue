<script setup lang="ts">
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pagination, PaginationContent, PaginationItem, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import UserQrCode from '@/components/UserQrCode.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Estudiante } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, Eye, Search, SquarePen, Trash2, Upload, XCircle } from 'lucide-vue-next';
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

// Función para formatear fechas y horas
function formatDateTime(dateStr: string | null): string {
    if (!dateStr) return '-';
    try {
        const date = new Date(dateStr);
        return date.toLocaleString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
    } catch (error) {
        console.error('Error formateando fecha:', error);
        return '-';
    }
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
const { estudiantes, cursos, paralelos } = defineProps<{
    estudiantes: any;
    cursos: any[];
    paralelos: any[];
}>();

// ===== ESTADOS REACTIVOS =====
const selectedCurso = ref<'all' | number>('all');
const selectedParalelo = ref<'all' | number>('all');
const searchQuery = ref('');
const dialogOpen = ref(false);
const confirmOpen = ref(false);
const estudianteToDelete = ref<number | null>(null);
const editEstudiante = ref<Estudiante | null>(null);
const editCurso = ref<number | null>(null);
const editParalelo = ref<number | null>(null);
const isInitialized = ref(false); // Bandera para controlar inicialización

// CSRF token para peticiones fetch (Laravel)
const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

// ===== COMPUTED PROPERTIES =====
const filteredEstudiantes = computed(() => {
    return estudiantes.data;
});

// Función para formatear los datos del usuario para el QR
const formatUserForQr = (user: any) => {
    if (!user) {
        return {
            id: 0,
            nombres: '',
            primerApellido: '',
            segundoApellido: '',
            email: '',
            qr_codigo: { id: '' },
        };
    }
    return {
        id: Number(user.id),
        nombres: user.nombres || '',
        primerApellido: user.primerApellido || '',
        segundoApellido: user.segundoApellido || '',
        email: user.email || '',
        qr_codigo: user.qr_codigo || '',
    };
};

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

// Método para limpiar filtros
function limpiarFiltros() {
    selectedCurso.value = 'all';
    selectedParalelo.value = 'all';
    searchQuery.value = '';
    aplicarFiltros();
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
function promptEliminarEstudiante(estudianteId: number) {
    estudianteToDelete.value = estudianteId;
    confirmOpen.value = true;
}

function eliminarEstudiante() {
    if (!estudianteToDelete.value) return;
    const id = estudianteToDelete.value;
    confirmOpen.value = false;
    router.delete(`/admin/estudiantes/${id}`, {
        onSuccess: () => {
            toast('Estudiante eliminado', {
                description: 'El estudiante ha sido eliminado correctamente',
                icon: Check,
                position: 'top-center',
            });
            estudianteToDelete.value = null;
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
        const estudiante = estudiantes.data.find((e: Estudiante) => e.idUser === Number(editEstudianteId));
        if (estudiante) {
            openEditDialog(estudiante);
        }
    }

    isInitialized.value = true;
});

// Debug temporal - ver datos que llegan
watch(
    () => estudiantes,
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
        editEstudiante.value.curso_paralelo.curso = cursos.find((c) => c.idCurso === val);
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
        editEstudiante.value.curso_paralelo.paralelo = paralelos.find((p) => p.idParalelo === val);
    }
});
</script>

<template>
    <Head title="Estudiantes" />

    <AppLayout>
        <!-- Toaster para notificaciones -->
        <Toaster position="top-center" />
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-6">
                <h1 class="text-3xl font-bold">Estudiantes</h1>
                <p class="text-muted-foreground">Gestiona la lista de estudiantes del sistema</p>
            </header>

            <!-- ===== CONTROLES DE FILTRO (STICKY) ===== -->
            <div class="sticky top-0 z-20 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/60">
                <div class="space-y-4 border-b pb-4">
                    <!-- Título y búsqueda -->
                    <div class="flex items-center justify-between">
                        <Input v-model="searchQuery" placeholder="Buscar estudiantes..." class="w-[300px]" @input="handleSearchChange">
                            <template #prefix>
                                <Search class="text-muted-foreground h-4 w-4" />
                            </template>
                        </Input>

                        <div class="flex gap-2">
                            <Link
                                :href="route('admin.estudiantes.import')"
                                class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 font-medium text-white transition-colors hover:bg-emerald-700"
                            >
                                <Upload class="mr-2 h-4 w-4" />
                                Importar estudiantes
                            </Link>
                            <Button as-child size="sm" class="bg-primary">
                                <Link href="/admin/estudiantes/create">Agregar Estudiante</Link>
                            </Button>
                        </div>
                    </div>

                    <!-- Filtros de Curso y Paralelo -->
                    <div class="flex flex-wrap gap-2">
                        <!-- Chips de Curso -->
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-gray-700">Cursos:</span>
                            <button
                                @click="selectedCurso = 'all'"
                                class="rounded-full px-3 py-1 text-sm transition-colors"
                                :class="selectedCurso === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            >
                                Todos
                            </button>
                            <button
                                v-for="curso in cursos"
                                :key="curso.idCurso"
                                @click="
                                    selectedCurso = curso.idCurso;
                                    handleCursoChange();
                                "
                                class="rounded-full px-3 py-1 text-sm transition-colors"
                                :class="selectedCurso === curso.idCurso ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                            >
                                {{ curso.nombre }}
                            </button>
                        </div>

                        <!-- Separador vertical -->
                        <div class="h-6 w-px bg-gray-300"></div>

                        <!-- Chips de Paralelo -->
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-gray-700">Paralelos:</span>
                            <button
                                @click="selectedParalelo = 'all'"
                                class="rounded-full px-3 py-1 text-sm transition-colors"
                                :class="selectedParalelo === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            >
                                Todos
                            </button>
                            <button
                                v-for="paralelo in paralelos"
                                :key="paralelo.idParalelo"
                                @click="
                                    selectedParalelo = paralelo.idParalelo;
                                    handleParaleloChange();
                                "
                                class="rounded-full px-3 py-1 text-sm transition-colors"
                                :class="
                                    selectedParalelo === paralelo.idParalelo
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                "
                            >
                                {{ paralelo.nombre }}
                            </button>
                        </div>
                    </div>

                    <!-- Indicadores de filtros activos -->
                    <div v-if="selectedCurso !== 'all' || selectedParalelo !== 'all'" class="flex items-center gap-2 text-sm text-gray-600">
                        <span class="font-medium">Filtros activos:</span>
                        <div v-if="selectedCurso !== 'all'" class="flex items-center gap-1">
                            <span class="rounded-md bg-blue-100 px-2 py-0.5 text-blue-700">
                                {{ cursos.find((c) => c.idCurso === selectedCurso)?.nombre }}
                            </span>
                        </div>
                        <div v-if="selectedParalelo !== 'all'" class="flex items-center gap-1">
                            <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-emerald-700">
                                {{ paralelos.find((p) => p.idParalelo === selectedParalelo)?.nombre }}
                            </span>
                        </div>
                        <button @click="limpiarFiltros" class="text-gray-500 hover:text-gray-700">
                            <XCircle class="h-4 w-4" />
                        </button>
                    </div>
                </div>
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
                                <UserQrCode v-if="estudiante.user.qr_codigo" :user="formatUserForQr(estudiante.user)" />
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
                                            <Button variant="destructive" size="sm" @click="promptEliminarEstudiante(estudiante.idUser)">
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

            <ConfirmDelete
                :open="confirmOpen"
                title="Confirmar eliminación"
                description="¿Estás seguro de que quieres eliminar este estudiante? Esta acción no se puede deshacer."
                @update:open="(v) => (confirmOpen = v)"
                @confirm="eliminarEstudiante"
                @cancel="confirmOpen = false"
            >
                <template #icon>
                    <Trash2 class="mr-2 h-4 w-4" />
                </template>
                <template #confirmLabel>Eliminar</template>
            </ConfirmDelete>
        </div>
    </AppLayout>
</template>
