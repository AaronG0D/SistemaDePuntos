<script setup lang="ts">
import { Button } from '@/components/ui/button';
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import { Input } from '@/components/ui/input';
import { Pagination, PaginationContent, PaginationItem, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import UserQrCode from '@/components/UserQrCode.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AdminDocentesProps } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Check, Edit, Eye, GraduationCap, Search, Trash2, XCircle } from 'lucide-vue-next';
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

// ===== COMPUTED PROPERTIES =====
const filteredDocentes = computed(() => {
    return props.docentes.data;
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
        page,
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
        page: 1, // Resetear a la primera página
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
</script>

<template>
    <Head title="Docentes" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-6">
                <h1 class="text-3xl font-bold">Docentes</h1>
                <p class="text-muted-foreground">Gestiona la lista de docentes y sus materias</p>
            </header>

            <!-- ===== CONTROLES DE FILTRADO ===== -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Input v-model="searchQuery" placeholder="Buscar docentes..." class="w-[300px]" @input="handleSearchChange">
                        <template #prefix>
                            <Search class="text-muted-foreground h-4 w-4" />
                        </template>
                    </Input>

                    <Select v-model="selectedMateria" @update:model-value="handleMateriaChange">
                        <SelectTrigger>
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
                        <SelectTrigger class="w-[180px]">
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

                <Button as-child size="sm" class="bg-primary">
                    <Link href="/admin/docentes/create">Agregar Docente</Link>
                </Button>
            </div>

            <!-- ===== TABLA DE DOCENTES ===== -->
            <div class="min-h-[500px] rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombres</TableHead>
                            <TableHead>Apellidos</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Materias</TableHead>
                            <TableHead>Cursos</TableHead>
                            <TableHead>Código QR</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="docente in filteredDocentes" :key="docente.idDocente" class="hover:bg-muted/50">
                            <TableCell>{{ docente.user?.nombres }}</TableCell>
                            <TableCell>
                                {{ docente.user?.primerApellido }}
                                {{ docente.user?.segundoApellido }}
                            </TableCell>
                            <TableCell>{{ docente.user?.email }}</TableCell>
                            <TableCell>
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
                            <TableCell>
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
                            <TableCell>
                                <UserQrCode v-if="docente.user.qr_codigo" :user="formatUserForQr(docente.user)" />
                            </TableCell>
                            <TableCell class="text-right">
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
                                        <TooltipContent>Eliminar docente</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- ===== PAGINACIÓN ===== -->
            <Pagination
                v-if="docentes.last_page > 1"
                class="bg-rgb(214, 219, 216)"
                :total="docentes.total"
                :items-per-page="docentes.per_page"
                :default-page="docentes.current_page"
                v-slot="{ page }"
            >
                <PaginationContent>
                    <PaginationPrevious v-if="docentes.current_page > 1" @click="goToPage(docentes.current_page - 1)" />

                    <template v-for="p in docentes.last_page" :key="p">
                        <PaginationItem :value="p" :is-active="p === docentes.current_page" @click="goToPage(p)">
                            {{ p }}
                        </PaginationItem>
                    </template>

                    <PaginationNext v-if="docentes.current_page < docentes.last_page" @click="goToPage(docentes.current_page + 1)" />
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
