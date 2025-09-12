<script setup lang="ts">
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pagination, PaginationContent, PaginationItem, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import UserQrCode from '@/components/UserQrCode.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Estudiante } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, Eye, FileSpreadsheet, Search, SquarePen, Trash2, Upload, XCircle } from 'lucide-vue-next';
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
const descargandoPlantilla = ref(false);
const dialogOpen = ref(false);
const confirmOpen = ref(false);
const estudianteToDelete = ref<number | null>(null);
const editEstudiante = ref<Estudiante | null>(null);
const errorOpen = ref(false);
const errorText = ref('');
const editCurso = ref<number | null>(null);
const editParalelo = ref<number | null>(null);
const isInitialized = ref(false); // Bandera para controlar inicialización

// Estados para importación
const importDialogOpen = ref(false);
const importFile = ref<File | null>(null);
const importResults = ref<any>(null);
const isImporting = ref(false);

// CSRF token para peticiones fetch (Laravel)
const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

// ===== COMPUTED PROPERTIES =====
const filteredEstudiantes = computed(() => {
    return props.estudiantes.data;
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

// ===== MÉTODOS DE IMPORTACIÓN =====
function openImportDialog() {
    importDialogOpen.value = true;
    importFile.value = null;
    importResults.value = null;
}

function closeImportDialog() {
    importDialogOpen.value = false;
    importFile.value = null;
    importResults.value = null;
}

function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];

        // Validar extensión en el frontend
        const allowedExtensions = ['xlsx', 'xls'];
        const fileName = file.name.toLowerCase();
        const fileExtension = fileName.split('.').pop();

        if (!fileExtension || !allowedExtensions.includes(fileExtension)) {
            toast('Archivo no válido', {
                description: `Solo se permiten archivos Excel (.xlsx, .xls). Archivo seleccionado: .${fileExtension}`,
                icon: XCircle,
                position: 'top-center',
            });
            // Limpiar el input
            target.value = '';
            importFile.value = null;
            return;
        }

        // Validar tamaño (10MB máximo)
        const maxSize = 10 * 1024 * 1024; // 10MB en bytes
        if (file.size > maxSize) {
            toast('Archivo muy grande', {
                description: `El archivo debe ser menor a 10MB. Tamaño actual: ${(file.size / 1024 / 1024).toFixed(2)}MB`,
                icon: XCircle,
                position: 'top-center',
            });
            // Limpiar el input
            target.value = '';
            importFile.value = null;
            return;
        }

        importFile.value = file;
        toast('Archivo válido', {
            description: `Archivo Excel seleccionado correctamente: ${file.name}`,
            icon: Check,
            position: 'top-center',
        });
    }
}

async function importarEstudiantes() {
    if (!importFile.value) {
        toast('Error', {
            description: 'Por favor selecciona un archivo Excel',
            icon: XCircle,
            position: 'top-center',
        });
        return;
    }

    isImporting.value = true;

    try {
        const formData = new FormData();
        formData.append('archivo', importFile.value);

        const response = await fetch('/admin/estudiantes/importar', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: formData,
        });

        const data = await response.json();

        if (response.ok && data.success) {
            importResults.value = data.data;
            toast('Importación exitosa', {
                description: `Se procesaron ${data.data.insertados} estudiantes correctamente`,
                icon: Check,
                position: 'top-center',
            });

            // Recargar la página después de un momento para mostrar los nuevos estudiantes
            setTimeout(() => {
                router.reload();
            }, 2000);
        } else {
            throw new Error(data.message || 'Error en la importación');
        }
    } catch (error: any) {
        toast('Error en importación', {
            description: error.message || 'No se pudo importar el archivo',
            icon: XCircle,
            position: 'top-center',
        });
    } finally {
        isImporting.value = false;
    }
}

const descargarPlantilla = async () => {
  try {
    const response = await fetch(route("admin.estudiantes.plantilla"), {
      method: 'GET',
      headers: {
        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      },
      credentials: 'same-origin',
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", "Plantilla_Estudiantes_" + new Date().toISOString().split("T")[0] + ".xlsx");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error("Error al descargar la plantilla:", error);
    toast("Error al descargar", {
      description: "No se pudo descargar la plantilla Excel",
      icon: XCircle,
      position: "top-center",
    });
  }
};


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

                <div class="flex gap-2">
                    <Button variant="outline" size="sm" @click="descargarPlantilla">
                        <FileSpreadsheet class="mr-2 h-4 w-4" />
                        Descargar Plantilla
                    </Button>
                    <Button variant="outline" size="sm" @click="openImportDialog">
                        <Upload class="mr-2 h-4 w-4" />
                        Importar Excel
                    </Button>
                    <Button as-child size="sm" class="bg-primary">
                        <Link href="/admin/estudiantes/create">Agregar Estudiante</Link>
                    </Button>
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

            <Toaster />
            <!-- ===== DIÁLOGO DE IMPORTACIÓN ===== -->
            <Dialog v-model:open="importDialogOpen">
                <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-[700px]">
                    <DialogHeader class="text-center">
                        <DialogTitle class="flex items-center justify-center gap-2 text-2xl font-bold text-blue-900">
                            📤 Importar Estudiantes desde Excel
                        </DialogTitle>
                        <DialogDescription class="text-blue-700">
                            Carga masiva de estudiantes utilizando archivos Excel con formato específico.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-6 py-4">
                        <!-- Paso 1: Descargar Plantilla -->
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div class="mb-3 flex items-center gap-2">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">1</div>
                                <h3 class="font-semibold text-blue-900">📋 Paso 1: Descargar Plantilla</h3>
                            </div>
                            <p class="mb-3 text-sm text-blue-700">
                                Descarga la plantilla Excel con el formato correcto y las instrucciones para importar estudiantes.
                            </p>
                            <Button
                                variant="default"
                                size="sm"
                                @click="descargarPlantilla"
                                class="border-blue-600 bg-blue-600 text-white hover:bg-blue-700"
                            >
                                <FileSpreadsheet class="mr-2 h-4 w-4" />
                                Descargar Plantilla Excel
                            </Button>
                        </div>

                        <!-- Paso 2: Subir Archivo -->
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <div class="mb-3 flex items-center gap-2">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-600 text-xs font-bold text-white">2</div>
                                <h3 class="font-semibold text-amber-900">📁 Paso 2: Subir Archivo Excel</h3>
                            </div>
                            <p class="mb-3 text-sm text-amber-700">Selecciona el archivo Excel completado con los datos de los estudiantes.</p>

                            <div class="space-y-3">
                                <div class="relative">
                                    <Input
                                        id="file-input"
                                        type="file"
                                        accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                                        @change="handleFileChange"
                                        :disabled="isImporting"
                                        class="hover:file:bg-50 file:mr-4 file:rounded-full file:border-0 file:bg-green-500 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-amber-700 hover:file:bg-green-300"
                                    />
                                </div>

                                <div class="flex items-center gap-2 text-xs text-amber-600">
                                    <span class="font-medium">📋 Formatos:</span>
                                    <span>.xlsx, .xls</span>
                                    <span class="font-medium">📏 Tamaño máximo:</span>
                                    <span>10MB</span>
                                </div>

                                <div v-if="importFile" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full bg-emerald-500"></div>
                                        <p class="text-sm font-medium text-emerald-900">Archivo seleccionado:</p>
                                    </div>
                                    <p class="mt-1 font-mono text-sm text-emerald-700">{{ importFile.name }}</p>
                                    <p class="mt-1 text-xs text-emerald-600">Tamaño: {{ (importFile.size / 1024 / 1024).toFixed(2) }} MB</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <Button
                                    @click="importarEstudiantes"
                                    :disabled="!importFile || isImporting"
                                    class="w-full bg-emerald-600 text-white hover:bg-emerald-700"
                                    size="lg"
                                >
                                    <Upload v-if="!isImporting" class="mr-2 h-4 w-4" />
                                    <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></div>
                                    {{ isImporting ? '📤 Importando Estudiantes...' : '📤 Importar Estudiantes' }}
                                </Button>
                            </div>
                        </div>

                        <!-- Resultados de Importación -->
                        <div v-if="importResults" class="rounded-lg border border-green-200 bg-green-50 p-4">
                            <div class="mb-3 flex items-center gap-2">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-green-600 text-xs font-bold text-white">✓</div>
                                <h3 class="font-semibold text-green-900">🎉 Importación Completada</h3>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-4">
                                <!-- Insertados -->
                                <div class="rounded-lg border border-green-200 bg-white p-3">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-green-600">{{ importResults.insertados || 0 }}</div>
                                        <div class="text-sm text-green-700">✅ Insertados</div>
                                    </div>
                                </div>

                                <!-- Actualizados -->
                                <div class="rounded-lg border border-blue-200 bg-white p-3">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600">{{ importResults.actualizados || 0 }}</div>
                                        <div class="text-sm text-blue-700">🔄 Actualizados</div>
                                    </div>
                                </div>

                                <!-- Omitidos -->
                                <div class="rounded-lg border border-amber-200 bg-white p-3">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-amber-600">{{ importResults.omitidos || 0 }}</div>
                                        <div class="text-sm text-amber-700">⚠️ Omitidos</div>
                                    </div>
                                </div>

                                <!-- Errores -->
                                <div class="rounded-lg border border-red-200 bg-white p-3">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-red-600">{{ importResults.errores_count || 0 }}</div>
                                        <div class="text-sm text-red-700">❌ Errores</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Procesados -->
                            <div class="mt-3">
                                <div class="rounded-lg border border-gray-200 bg-white p-3">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-600">
                                            {{ importResults.estudiantes_afectados || 0 }}
                                        </div>
                                        <div class="text-sm text-gray-700">👥 Total Procesados Exitosamente</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-lg border border-green-200 bg-white p-3">
                                <p class="text-center text-sm text-green-700">
                                    🔄 La página se actualizará automáticamente para mostrar los nuevos estudiantes...
                                </p>
                            </div>
                        </div>

                        <!-- Instrucciones de Uso -->
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="mb-3 flex items-center gap-2">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-600 text-xs font-bold text-white">💡</div>
                                <h3 class="font-semibold text-gray-900">💡 Instrucciones de Uso</h3>
                            </div>
                            <div class="space-y-2 text-sm text-gray-700">
                                <div class="flex items-start gap-2">
                                    <span class="font-medium text-emerald-600">1.</span>
                                    <span>Descarga la plantilla Excel haciendo clic en el botón "Descargar Plantilla Excel".</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="font-medium text-emerald-600">2.</span>
                                    <span>Completa la plantilla con los datos de los estudiantes siguiendo las instrucciones incluidas.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="font-medium text-emerald-600">3.</span>
                                    <span>Asegúrate de usar los IDs de curso-paralelo correctos que aparecen en la plantilla.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="font-medium text-emerald-600">4.</span>
                                    <span>Sube el archivo completado y haz clic en "Importar Estudiantes".</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="font-medium text-emerald-600">5.</span>
                                    <span>Revisa los resultados y corrige cualquier error si es necesario.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="flex justify-between">
                        <Button variant="outline" @click="closeImportDialog" :disabled="isImporting"> Cancelar </Button>
                        <div class="text-xs text-gray-500">💾 Los datos se guardarán automáticamente en el sistema</div>
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
