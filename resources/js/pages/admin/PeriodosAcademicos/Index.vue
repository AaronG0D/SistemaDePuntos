<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { Pencil, Plus, Trash } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Toaster, toast } from 'vue-sonner';

const props = defineProps({
    periodos: Array,
    filters: Object,
    years: Array,
});

const filters = ref({
    search: props.filters?.search || '',
    year: props.filters?.year || '',
    estado: props.filters?.estado || '',
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
    activo: false,
});

const editForm = useForm({
    idPeriodo: '', // Añadir el campo idPeriodo
    nombre: '',
    codigo: '',
    fecha_inicio: '',
    fecha_fin: '',
    activo: false,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);

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
    editForm.activo = periodo.activo;
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
    if (confirm('¿Está seguro de eliminar este período?')) {
        router.delete(route('admin.periodos.destroy', id), {
            onSuccess: () => {
                toast.success('Período eliminado', {
                    description: 'El período académico ha sido eliminado exitosamente',
                });
            },
            onError: () => {
                toast.error('Error', {
                    description: 'No se pudo eliminar el período académico',
                });
            },
        });
    }
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
    <AppLayout>
        <div class="container mx-auto p-4 sm:py-6">
            <!-- Header -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold sm:text-2xl">Períodos Académicos</h1>
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
                    <Select v-model="filters.year" class="w-full sm:w-[180px]">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue placeholder="Filtrar por año" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">Todos los años</SelectItem>
                            <SelectItem v-for="year in years" :key="year" :value="year">
                                {{ year }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="filters.estado" class="w-full sm:w-[180px]">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">Todos</SelectItem>
                            <SelectItem value="activo">Activos</SelectItem>
                            <SelectItem value="inactivo">Inactivos</SelectItem>
                        </SelectContent>
                    </Select>
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
                                    <Button variant="outline" size="sm" @click="editPeriodo(periodo)">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button variant="destructive" size="sm" @click="deletePeriodo(periodo.idPeriodo)">
                                        <Trash class="h-4 w-4" />
                                    </Button>
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
                                <Switch v-model="editForm.activo" />
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
    </AppLayout>
    <Toaster />
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
