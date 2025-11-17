<script setup lang="ts">
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pagination, PaginationContent, PaginationItem, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import UserQrCode from '@/components/UserQrCode.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AdminDocentesProps } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Edit, Eye, GraduationCap, Search, Trash2, UserCheck, XCircle } from 'lucide-vue-next';
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

// ===== PROPS =====
const props = defineProps<AdminDocentesProps>();

// ===== ESTADOS REACTIVOS =====
const selectedMateria = ref<'all' | number>('all');
const selectedCurso = ref<'all' | number>('all');
const searchQuery = ref('');
const isInitialized = ref(false);
const docenteToDelete = ref<number | null>(null);
const dialogOpen = ref(false);
const tabActivo = ref<'activos' | 'inactivos'>('activos');
const docenteToRestore = ref<number | null>(null);

// ===== COMPUTED PROPERTIES =====
const filteredDocentes = computed(() => {
    if (tabActivo.value === 'inactivos') {
        return props.docentesInactivos?.data || [];
    }
    return props.docentes.data;
});

const paginatorDocentes = computed(() => {
    // Siempre devolver un paginador válido; si inactivos no existe aún, caer a activos
    if (tabActivo.value === 'inactivos') {
        return props.docentesInactivos || props.docentes;
    }
    return props.docentes;
});

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
        ...(tabActivo.value === 'inactivos' ? { inactivos_page: page } : { page }),
        materia: selectedMateria.value !== 'all' ? selectedMateria.value : undefined,
        curso: selectedCurso.value !== 'all' ? selectedCurso.value : undefined,
        search: searchQuery.value || undefined,
    };

    router.get('/admin/docentes', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

// Función para aplicar filtros
function aplicarFiltros() {
    const params: Record<string, any> = {
        ...(tabActivo.value === 'inactivos' ? { inactivos_page: 1 } : { page: 1 }), // Resetear a la primera página
    };

    // Solo agregar parámetros si tienen valores válidos
    if (selectedMateria.value !== 'all' && selectedMateria.value !== null) {
        params.materia = selectedMateria.value;
    }

    if (selectedCurso.value !== 'all' && selectedCurso.value !== null) {
        params.curso = selectedCurso.value;
    }

    if (searchQuery.value && searchQuery.value.trim() !== '') {
        params.search = searchQuery.value.trim();
    }

    console.log('Aplicando filtros:', params);

    router.get('/admin/docentes', params, {
        preserveState: true,
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

// Limpiar filtros y volver a Activos
function limpiarFiltrosDocentes() {
    searchQuery.value = '';
    selectedMateria.value = 'all';
    selectedCurso.value = 'all';
    tabActivo.value = 'activos';
    aplicarFiltros();
}

// Función debounced para búsqueda
const aplicarFiltrosDebounced = debounce(aplicarFiltros, 300);

// ===== MÉTODOS DE FILTROS =====
function handleSearchChange() {
    if (isInitialized.value) {
        aplicarFiltrosDebounced();
    }
}

function handleMateriaChange() {
    if (isInitialized.value) {
        aplicarFiltros();
    }
}

function handleCursoChange() {
    if (isInitialized.value) {
        aplicarFiltros();
    }
}

// ===== MÉTODOS DE ELIMINACIÓN =====
function abrirDialogoEliminar(docenteId: number) {
    docenteToDelete.value = docenteId;
    dialogOpen.value = true;
}

function confirmarEliminacion() {
    if (docenteToDelete.value) {
        // Cerrar el diálogo antes de eliminar
        dialogOpen.value = false;

        router.delete(`/admin/docentes/${docenteToDelete.value}`, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                docenteToDelete.value = null;
                toast.success('Docente eliminado correctamente', {
                    description: 'El docente ha sido eliminado correctamente',
                });
            },
            onError: () => {
                toast.error('Error al eliminar', {
                    description: 'No se pudo eliminar el docente',
                });
            },
        });
    }
}

function restaurarDocente(id: number) {
    router.post(
        `/admin/docentes/${id}/restore`,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                docenteToRestore.value = null;
                toast.success('Docente reactivado correctamente', {
                    description: 'El docente ha sido reactivado correctamente',
                });
            },
            onError: () => {
                toast.error('Error al reactivar', {
                    description: 'No se pudo reactivar el docente',
                });
            },
        },
    );
}

function limpiarAsignaciones(id: number) {
    router.post(
        `/admin/docentes/${id}/clear-assignments`,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Asignaciones eliminadas', {
                    description: 'Las asignaciones de materias han sido eliminadas correctamente',
                });
            },
            onError: () => {
                toast.error('Error al limpiar asignaciones', {
                    description: 'No se pudieron eliminar las asignaciones',
                });
            },
        },
    );
}

// ===== WATCHERS =====
// Inicializar filtros desde la URL
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || '';

    const materiaParam = urlParams.get('materia');
    selectedMateria.value = materiaParam ? Number(materiaParam) : 'all';

    const cursoParam = urlParams.get('curso');
    selectedCurso.value = cursoParam ? Number(cursoParam) : 'all';

    isInitialized.value = true;
});

// Debug temporal - ver datos que llegan
watch(
    () => props.docentes,
    (newData) => {
        console.log('Datos de docentes actualizados:', newData);
    },
    { deep: true },
);

// Sincronizar cuando cambia el tab
watch(tabActivo, (newTab) => {
    const params: Record<string, any> = {
        ...(newTab === 'inactivos' ? { inactivos_page: 1 } : { page: 1 }),
        materia: selectedMateria.value !== 'all' ? selectedMateria.value : undefined,
        curso: selectedCurso.value !== 'all' ? selectedCurso.value : undefined,
        search: searchQuery.value || undefined,
        tab: newTab,
    };

    router.get('/admin/docentes', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});
</script>

<template>
    <Head title="Docentes" />

    <AppLayout>
        <div class="container mx-auto px-3 sm:px-4 py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-4 sm:mb-6">
                <h1 class="flex items-center gap-3 text-2xl sm:text-3xl font-bold">
                    <UserCheck class="h-8 w-8 text-blue-600" />
                    Docentes
                </h1>
                <p class="text-muted-foreground text-sm sm:text-base">Gestiona la lista de docentes y sus materias</p>
            </header>

            <!-- ===== CONTROLES DE FILTRADO ===== -->
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex w-full sm:w-auto flex-wrap items-center gap-2">
                    <Input
                        v-model="searchQuery"
                        placeholder="Buscar docentes..."
                        class="w-full sm:w-[300px]"
                        @input="handleSearchChange"
                    >
                        <template #prefix>
                            <Search class="text-muted-foreground h-4 w-4" />
                        </template>
                    </Input>

                    <Select v-model="tabActivo">
                        <SelectTrigger class="w-full sm:w-[150px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="activos">Activos</SelectItem>
                            <SelectItem value="inactivos">Inactivos</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="selectedMateria" @update:model-value="handleMateriaChange">
                        <SelectTrigger class="w-full sm:w-[220px]">
                            <SelectValue placeholder="Todas las materias" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>Materias</SelectLabel>
                                <SelectItem value="all">Todas las materias</SelectItem>
                                <SelectItem v-for="materia in materias" :key="materia.idMateria" :value="materia.idMateria">
                                    {{ materia.nombre }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>

                    <Select v-model="selectedCurso" @update:model-value="handleCursoChange">
                        <SelectTrigger class="w-full sm:w-[180px]">
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
                </div>
                <div class="flex w-full sm:w-auto justify-end">
                    <Button as-child size="sm" class="bg-primary w-full sm:w-auto">
                        <Link href="/admin/docentes/create">Agregar Docente</Link>
                    </Button>
                </div>
            </div>

            <!-- ===== TABLA DE DOCENTES ===== -->
            <div v-if="filteredDocentes.length > 0" class="min-h-[500px] rounded-lg border overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="min-w-[140px]">Nombres</TableHead>
                            <TableHead class="min-w-[160px]">Apellidos</TableHead>
                            <TableHead class="hidden md:table-cell min-w-[200px]">Email</TableHead>
                            <TableHead class="hidden lg:table-cell min-w-[220px]">Materias</TableHead>
                            <TableHead class="hidden lg:table-cell min-w-[240px]">Cursos</TableHead>
                            <TableHead class="hidden xl:table-cell">Código QR</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="docente in filteredDocentes" :key="docente.idDocente" class="hover:bg-muted/50">
                            <TableCell class="max-w-[180px] truncate">{{ docente.user?.nombres }}</TableCell>
                            <TableCell class="max-w-[220px] truncate">
                                {{ docente.user?.primerApellido }}
                                {{ docente.user?.segundoApellido }}
                            </TableCell>
                            <TableCell class="hidden md:table-cell">{{ docente.user?.email }}</TableCell>
                            <TableCell class="hidden lg:table-cell">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="asignacion in docente.docente_materia_cursos"
                                        :key="`${asignacion.idMateria}-${asignacion.idCursoParalelo}`"
                                        class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-800"
                                    >
                                        <BookOpen class="h-3 w-3" />
                                        {{ asignacion.materia.nombre }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="hidden lg:table-cell">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="asignacion in docente.docente_materia_cursos"
                                        :key="`${asignacion.idMateria}-${asignacion.idCursoParalelo}`"
                                        class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs text-green-800"
                                    >
                                        <GraduationCap class="h-3 w-3" />
                                        {{ asignacion.curso_paralelo.curso.nombre }} - {{ asignacion.curso_paralelo.paralelo.nombre }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="hidden xl:table-cell">
                                <UserQrCode v-if="docente.user.qr_codigo" :user="formatUserForQr(docente.user)" />
                            </TableCell>
                            <TableCell class="text-right">
                                <template v-if="tabActivo === 'inactivos'">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="border-green-200 text-green-600 hover:bg-green-50 dark:border-green-800 dark:text-green-400 dark:hover:bg-green-950"
                                        @click="restaurarDocente(docente.idDocente)"
                                    >
                                        Reactivar
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="limpiarAsignaciones(docente.idDocente)">
                                        Limpiar Asignaciones
                                    </Button>
                                </template>
                                <template v-else>
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button variant="ghost" size="sm" as-child>
                                                    <Link :href="`/admin/docentes/${docente.idDocente}`">
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
                                                <Button variant="ghost" size="sm" as-child>
                                                    <Link :href="`/admin/docentes/${docente.idDocente}/edit`">
                                                        <Edit />
                                                    </Link>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Editar docente</TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>

                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button variant="destructive" size="sm" @click="abrirDialogoEliminar(docente.idDocente)">
                                                    <Trash2 />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Desactivar docente</TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </template>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- ===== ESTADO VACÍO ===== -->
            <div v-else class="min-h-[280px] rounded-lg border p-10 flex items-center justify-center text-center">
                <div>
                    <h3 class="text-lg font-semibold">No se encontraron docentes</h3>
                    <p class="mt-1 text-muted-foreground">Prueba limpiando los filtros o revisa los activos.</p>
                    <div class="mt-4 flex flex-col sm:flex-row items-center justify-center gap-2">
                        <Button size="sm" variant="outline" @click="limpiarFiltrosDocentes">Limpiar filtros</Button>
                        <Button size="sm" variant="secondary" @click="tabActivo = 'activos'">Ver Activos</Button>
                    </div>
                </div>
            </div>

            <!-- ===== PAGINACIÓN ===== -->
            <Pagination
                v-if="paginatorDocentes && (paginatorDocentes.last_page || 0) > 1"
                class="bg-rgb(214, 219, 216)"
                :total="paginatorDocentes?.total || 0"
                :items-per-page="paginatorDocentes?.per_page || 0"
                :default-page="paginatorDocentes?.current_page || 1"
                v-slot="{ page }"
            >
                <PaginationContent>
                    <PaginationPrevious v-if="paginatorDocentes && paginatorDocentes.current_page > 1" @click="goToPage(paginatorDocentes.current_page - 1)" />

                    <template v-for="p in (paginatorDocentes?.last_page || 0)" :key="p">
                        <PaginationItem :value="p" :is-active="p === (paginatorDocentes?.current_page || 1)" @click="goToPage(p)">
                            {{ p }}
                        </PaginationItem>
                    </template>

                    <PaginationNext
                        v-if="paginatorDocentes && paginatorDocentes.current_page < paginatorDocentes.last_page"
                        @click="goToPage(paginatorDocentes.current_page + 1)"
                    />
                </PaginationContent>
            </Pagination>

            <Toaster />
        </div>

        <!-- ===== DIÁLOGO DE CONFIRMACIÓN (Reutilizable) ===== -->
        <ConfirmDelete
            :open="dialogOpen"
            title="Confirmar eliminación"
            description="¿Estás seguro de que quieres eliminar este docente? Esta acción no se puede deshacer."
            @update:open="(v) => (dialogOpen = v)"
            @confirm="confirmarEliminacion"
            @cancel="dialogOpen = false"
        >
            <template #icon>
                <Trash2 class="mr-2 h-4 w-4" />
            </template>
            <template #confirmLabel>Eliminar</template>
        </ConfirmDelete>
    </AppLayout>
</template>
