<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { Calendar, Pencil, Plus, Trash } from 'lucide-vue-next';
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Toaster, toast } from 'vue-sonner';
import ConfirmDelete from '@/components/ConfirmDelete.vue';

const props = defineProps({
    periodos: Array,
    filters: Object,
    years: Array,
});

const filters = ref({
    search: props.filters?.search || '',
    year: props.filters?.year || 'all',
    trashed: props.filters?.trashed || 'active',
});

// Vigilar cambios en filtros
watch(
    filters,
    (newFilters) => {
        router.get(route('admin.periodos.index'), newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    },
    { deep: true },
);

const formatDate = (date) => {
    if (!date) return '';
    const dateObj = new Date(date);
    return format(dateObj, 'dd/MM/yyyy', { locale: es });
};

const form = useForm({
    nombre: '',
    codigo: '',
    fecha_inicio: '',
    fecha_fin: '',
    activo: 0,
});

const editForm = useForm({
    idPeriodo: '', // Añadir el campo idPeriodo
    nombre: '',
    codigo: '',
    fecha_inicio: '',
    fecha_fin: '',
    activo: 0,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);

// Estado para diálogos de confirmación
const showConfirmDeleteDialog = ref(false);
const itemToDelete = ref(null);
const showConfirmRestoreDialog = ref(false);
const itemToRestore = ref(null);

const submit = () => {
    form.post(route('admin.periodos.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
            toast.success('Período creado', {
                description: 'El período académico ha sido creado exitosamente',
            });
        },
        onError: () => {
            toast.error('Error', {
                description: 'No se pudo crear el período académico',
            });
        },
    });
};

const editPeriodo = (periodo) => {
    editForm.reset();
    editForm.idPeriodo = periodo.idPeriodo;
    editForm.nombre = periodo.nombre;
    editForm.codigo = periodo.codigo;
    // Formatear las fechas a YYYY-MM-DD para el input type="date"
    editForm.fecha_inicio = periodo.fecha_inicio ? format(new Date(periodo.fecha_inicio), 'yyyy-MM-dd') : '';
    editForm.fecha_fin = periodo.fecha_fin ? format(new Date(periodo.fecha_fin), 'yyyy-MM-dd') : '';
    editForm.activo = periodo.activo ? 1 : 0;
    showEditModal.value = true;
};

const updatePeriodo = () => {
    editForm.put(route('admin.periodos.update', editForm.idPeriodo), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            toast.success('Período actualizado', {
                description: 'El período académico ha sido actualizado exitosamente',
            });
        },
        onError: (errors) => {
            toast.error('Error', {
                description: errors.codigo || 'No se pudo actualizar el período académico',
            });
        },
    });
};

const deletePeriodo = (id) => {
    itemToDelete.value = id;
    showConfirmDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (itemToDelete.value === null) return;
    
    router.delete(route('admin.periodos.destroy', itemToDelete.value), {
        onSuccess: () => {
            toast.success('Período eliminado', {
                description: 'El período académico ha sido eliminado exitosamente',
            });
            showConfirmDeleteDialog.value = false;
            itemToDelete.value = null;
        },
        onError: () => {
            toast.error('Error', {
                description: 'No se pudo eliminar el período académico',
            });
            showConfirmDeleteDialog.value = false;
            itemToDelete.value = null;
        },
    });
};

const restorePeriodo = (id) => {
    itemToRestore.value = id;
    showConfirmRestoreDialog.value = true;
};

const confirmRestore = () => {
    if (itemToRestore.value === null) return;
    
    router.post(route('admin.periodos.restore', itemToRestore.value), {}, {
        onSuccess: () => {
            toast.success('Período restaurado', {
                description: 'El período académico ha sido restaurado exitosamente',
            });
            showConfirmRestoreDialog.value = false; // Corrected variable name
            itemToRestore.value = null; // Corrected variable name
        },
        onError: () => {
            toast.error('Error', {
                description: 'No se pudo restaurar el período académico',
            });
            showConfirmRestoreDialog.value = false; // Corrected variable name
            itemToRestore.value = null; // Corrected variable name
        },
    });
};

// Añadir un método para debug
const debugForm = () => {
    console.log('Formulario:', {
        idPeriodo: editForm.idPeriodo,
        nombre: editForm.nombre,
        codigo: editForm.codigo,
        fecha_inicio: editForm.fecha_inicio,
        fecha_fin: editForm.fecha_fin,
        activo: editForm.activo,
    });
};
</script>

<template>
    <Head>
        <title>Gestión de Períodos</title>
    </Head>
    <AppLayout>
         <Toaster 
            position="top-right" 
            richColors 
            :toastOptions="{
                style: {
                    maxWidth: '400px',
                },
                duration: 3000,
            }"
        />
        <div class="container mx-auto p-4 sm:py-6">
           
            <!-- Header -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="flex items-center gap-3 text-xl font-semibold sm:text-2xl">
                        <Calendar class="h-7 w-7 text-blue-600" />
                        Períodos Académicos
                    </h1>
                    <p class="text-muted-foreground text-sm">Gestiona los períodos académicos del sistema</p>
                </div>
                <!-- Modal de Crear -->
                <Dialog v-model:open="showCreateModal">
                    <DialogTrigger asChild>
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Nuevo Período
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle>Crear Período Académico</DialogTitle>
                            <DialogDescription> Ingrese los datos para crear un nuevo período académico </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid gap-4">
                                <div class="grid gap-2">
                                    <Label>Nombre</Label>
                                    <Input v-model="form.nombre" required />
                                </div>
                                <div class="grid gap-2">
                                    <Label>Código</Label>
                                    <Input v-model="form.codigo" required />
                                </div>
                                <!-- Fecha Inicio -->
                                <div class="grid gap-2">
                                    <Label>Fecha Inicio</Label>
                                    <Input type="date" v-model="form.fecha_inicio" required />
                                </div>
                                <!-- Fecha Fin -->
                                <div class="grid gap-2">
                                    <Label>Fecha Fin</Label>
                                    <Input type="date" v-model="form.fecha_fin" required />
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Switch v-model="form.activo" />
                                    <Label>Activo</Label>
                                </div>
                            </div>
                            <Button type="submit" :disabled="form.processing">Guardar</Button>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Filtros -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="w-full sm:w-auto sm:flex-1">
                    <Input v-model="filters.search" placeholder="Buscar..." class="w-full sm:max-w-sm" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <select v-model="filters.year" class="flex h-10 w-full sm:w-[180px] items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <option value="all">Todos los años</option>
                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                    </select>
                    <select v-model="filters.trashed" class="flex h-10 w-full sm:w-[180px] items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <option value="active">Activos</option>
                        <option value="only">Eliminados</option>
                        <option value="with">Todos</option>
                    </select>
                    
                </div>
            </div>

            <!-- Tabla Responsive -->
            <div class="overflow-x-auto rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="whitespace-nowrap">Nombre</TableHead>
                            <TableHead class="whitespace-nowrap">Código</TableHead>
                            <TableHead class="whitespace-nowrap">Fechas</TableHead>
                            <TableHead class="whitespace-nowrap">Estado</TableHead>
                            <TableHead class="whitespace-nowrap">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="periodo in periodos" :key="periodo.idPeriodo">
                            <TableCell class="font-medium">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:gap-2">
                                    <span>{{ periodo.nombre }}</span>
                                </div>
                            </TableCell>
                            <TableCell>{{ periodo.codigo }}</TableCell>
                            <TableCell>
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <Badge variant="outline" class="whitespace-nowrap">Inicio</Badge>
                                        <span class="text-sm">{{ formatDate(periodo.fecha_inicio) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge variant="outline" class="whitespace-nowrap">Fin</Badge>
                                        <span class="text-sm">{{ formatDate(periodo.fecha_fin) }}</span>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="periodo.activo ? 'success' : 'secondary'" class="whitespace-nowrap">
                                    {{ periodo.activo ? 'Activo' : 'Inactivo' }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap items-center gap-2">
                                    <template v-if="periodo.deleted_at">
                                        <Button variant="outline" size="sm" @click="restorePeriodo(periodo.idPeriodo)">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                                            Restaurar
                                        </Button>
                                    </template>
                                    <template v-else>
                                        <Button variant="outline" size="sm" @click="editPeriodo(periodo)">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button variant="destructive" size="sm" @click="deletePeriodo(periodo.idPeriodo)">
                                            <Trash class="h-4 w-4" />
                                        </Button>
                                    </template>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Modal de Edición -->
            <Dialog v-model:open="showEditModal">
                <DialogContent class="w-[95vw] sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>Editar Período Académico</DialogTitle>
                        <DialogDescription> ID del período: {{ editForm.idPeriodo }} </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="updatePeriodo" class="space-y-4">
                        <input type="hidden" v-model="editForm.idPeriodo" />
                        <div class="grid gap-4">
                            <!-- Campos ocultos para debug -->
                            <div class="text-muted-foreground text-xs">ID: {{ editForm.idPeriodo }}</div>

                            <div class="grid gap-2">
                                <Label>Nombre</Label>
                                <Input v-model="editForm.nombre" required />
                            </div>
                            <div class="grid gap-2">
                                <Label>Código</Label>
                                <Input v-model="editForm.codigo" required />
                                <span v-if="editForm.errors.codigo" class="text-sm text-red-500">
                                    {{ editForm.errors.codigo }}
                                </span>
                            </div>
                            <!-- Fecha Inicio -->
                            <div class="grid gap-2">
                                <Label>Fecha Inicio</Label>
                                <Input type="date" v-model="editForm.fecha_inicio" :min="'2000-01-01'" :max="'2099-12-31'" required />
                                <span class="text-muted-foreground text-xs"> Fecha seleccionada: {{ formatDate(editForm.fecha_inicio) }} </span>
                            </div>

                            <!-- Fecha Fin -->
                            <div class="grid gap-2">
                                <Label>Fecha Fin</Label>
                                <Input type="date" v-model="editForm.fecha_fin" :min="editForm.fecha_inicio" :max="'2099-12-31'" required />
                                <span class="text-muted-foreground text-xs"> Fecha seleccionada: {{ formatDate(editForm.fecha_fin) }} </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Switch v-model="editForm.activo" :true-value="1" :false-value="0" />
                                <Label>Activo</Label>
                            </div>
                        </div>
                        <div class="flex justify-between">
                            <Button type="button" variant="outline" @click="showEditModal = false"> Cancelar </Button>
                            <Button type="submit" :disabled="editForm.processing"> Actualizar </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
        
        <!-- Confirm Dialogs -->
        <ConfirmDelete
            :open="showConfirmDeleteDialog"
            title="¿Eliminar período académico?"
            description="Esta acción moverá el período a la papelera. Podrás restaurarlo después si es necesario."
            @update:open="(v) => showConfirmDeleteDialog = v"
            @confirm="confirmDelete"
            @cancel="showConfirmDeleteDialog = false"
        />
        
        <ConfirmDelete
            :open="showConfirmRestoreDialog"
            title="¿Restaurar período académico?"
            description="El período será restaurado y estará disponible nuevamente."
            confirmText="Restaurar"
            @update:open="(v) => showConfirmRestoreDialog = v"
            @confirm="confirmRestore"
            @cancel="showConfirmRestoreDialog = false"
        />
        </AppLayout>
    
</template>

<style>
@media (max-width: 640px) {
    .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .table-container {
        margin-left: -1rem;
        margin-right: -1rem;
        width: calc(100% + 2rem);
    }
}
</style>
