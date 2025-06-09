<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Pagination, PaginationContent, PaginationItem, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { Estudiante } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, SquarePen, Trash2, Check, AlertCircle, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css'; //

// Define los tipos de curso y paralelo
interface Curso {
    idCurso: number;
    nombre: string;
}
interface Paralelo {
    idParalelo: number;
    nombre: string;
}

interface PaginacionEstudiantes {
    total: any;
    per_page: any;
    data: Estudiante[];
    last_page: number;
    current_page: number;
    // agrega otras propiedades si las necesitas
}

// Tipa las props correctamente 
const props = defineProps<{
    estudiantes: PaginacionEstudiantes;
    cursos: Curso[];
    paralelos: Paralelo[];
}>();

const selectedCurso = ref<'all' | number>('all');
const selectedParalelo = ref<'all' | number>('all');
const searchQuery = ref('');
const filteredEstudiantes = computed(() => {
    return props.estudiantes.data.filter((est) => {
        const cursoMatch = selectedCurso.value === 'all' || est.curso_paralelo?.curso?.idCurso === selectedCurso.value;

        const paraleloMatch = selectedParalelo.value === 'all' || est.curso_paralelo?.paralelo?.idParalelo === selectedParalelo.value;

        const nombreCompleto = `${est.user.nombres} ${est.user.primerApellido} ${est.user.segundoApellido ?? ''}`.toLowerCase();
        const email = est.user.email.toLowerCase();
        const query = searchQuery.value.toLowerCase();

        const searchMatch = nombreCompleto.includes(query) || email.includes(query);

        return cursoMatch && paraleloMatch && searchMatch;
    });
});

function goToPage(page: number) {
    router.get(
        '/admin/estudiantes',
        {
            page,
            curso: selectedCurso.value !== 'all' ? selectedCurso.value : undefined,
            paralelo: selectedParalelo.value !== 'all' ? selectedParalelo.value : undefined,
            search: searchQuery.value || undefined,
        },
        {
            preserveState: false,
            preserveScroll: true,
            replace: true,
        },
    );
}

const dialogOpen = ref(false);
const editEstudiante = ref<any>(null);

function openEdit(estudiante: any) {
    editEstudiante.value = JSON.parse(JSON.stringify(estudiante));
    // Establece los valores iniciales de curso y paralelo
    editCurso.value = estudiante.curso_paralelo?.curso?.idCurso || null;
    editParalelo.value = estudiante.curso_paralelo?.paralelo?.idParalelo || null;
    dialogOpen.value = true;
}

// Guardar cambios (ajusta la ruta si usas otro endpoint)
function guardarCambios() {
    const datos = {
        user: {
            nombres: editEstudiante.value.user.nombres,
            primerApellido: editEstudiante.value.user.primerApellido,
            segundoApellido: editEstudiante.value.user.segundoApellido,
            email: editEstudiante.value.user.email,
        },
        curso_paralelo: {
            idCurso: editCurso.value,
            idParalelo: editParalelo.value,
        },
    };

    router.put(`/admin/estudiantes/${editEstudiante.value.idUser}`, datos, {
        onSuccess: () => {
            dialogOpen.value = false;
            toast('Estudiante actualizado', {
                description: 'Los cambios han sido guardados correctamente',
                icon: Check,
                position: 'top-center',
                action: {
                    label: 'Ver detalles',
                    onClick: () => router.visit(`/admin/estudiantes/${editEstudiante.value.idUser}`),
                },
            });
        },
        onError: () => {
            toast('Error al actualizar', {
                description: 'No se pudo actualizar el estudiante',
                icon: XCircle,
                position: 'top-center' ,
                action: {
                    label: 'Intentar de nuevo',
                    onClick: () => guardarCambios(),
                },
            });
        },
    });
}
// Opcional: Sincroniza curso/paralelo seleccionados en el diálogo
const editCurso = ref<number | 'all'>();
const editParalelo = ref<number | 'all'>();

watch(dialogOpen, (open) => {
    if (open && editEstudiante.value) {
        // Si existe curso_paralelo, toma los ids, si no, pon 'all'
        editCurso.value = editEstudiante.value.curso_paralelo?.curso?.idCurso ?? editEstudiante.value.curso_paralelo?.idCurso ?? 'all';
        editParalelo.value = editEstudiante.value.curso_paralelo?.paralelo?.idParalelo ?? editEstudiante.value.curso_paralelo?.idParalelo ?? 'all';
    }
});

watch(editCurso, (val) => {
    if (editEstudiante.value && val !== undefined && val !== 'all') {
        editEstudiante.value.curso_paralelo = editEstudiante.value.curso_paralelo || {};
        // Actualiza tanto el ID como el objeto curso
        editEstudiante.value.curso_paralelo.idCurso = val;
        editEstudiante.value.curso_paralelo.curso = props.cursos.find((c) => c.idCurso === val);
    }
});

watch(editParalelo, (val) => {
    if (editEstudiante.value && val !== undefined && val !== 'all') {
        editEstudiante.value.curso_paralelo = editEstudiante.value.curso_paralelo || {};
        // Actualiza tanto el ID como el objeto paralelo
        editEstudiante.value.curso_paralelo.idParalelo = val;
        editEstudiante.value.curso_paralelo.paralelo = props.paralelos.find((p) => p.idParalelo === val);
    }
});
</script>
<template>
    <Head title="Estudiantes" />

    <AppLayout>
        <Toaster 
            position='top-center' 
            richColors
            closeButton
            expand
        />
        <div class="container mx-auto py-6">
            <header class="mb-6">
                <h1 class="text-3xl font-bold">Estudiantes</h1>
                <p class="text-muted-foreground">Gestiona la lista de estudiantes del sistema</p>
            </header>

            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Input v-model="searchQuery" placeholder="Buscar estudiantes..." class="w-[300px]">
                        <template #prefix>
                            <Search class="text-muted-foreground h-4 w-4" />
                        </template>
                    </Input>

                    <!-- Select de cursos -->
                    <Select v-model="selectedCurso">
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

                    <!-- Select de paralelos -->
                    <Select v-model="selectedParalelo">
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
                            <TableCell>{{ estudiante.user?.primerApellido }} {{ estudiante.user?.segundoApellido }}</TableCell>
                            <TableCell>{{ estudiante.user?.email }}</TableCell>
                            <TableCell>{{ estudiante.curso_paralelo?.curso?.nombre ?? '-' }}</TableCell>
                            <TableCell>{{ estudiante.curso_paralelo?.paralelo?.nombre ?? '-' }}</TableCell>
                            <TableCell>{{ estudiante.user?.puntaje?.puntajeTotal ?? '-' }}</TableCell>
                            <TableCell>
                                <img v-if="estudiante.user?.qr_codigo" :src="estudiante.user.qr_codigo" alt="QR Code" class="h-8 w-8" />
                            </TableCell>
                            <TableCell class="text-right">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="sm" @click="openEdit(estudiante)">
                                                <SquarePen />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent> Editar estudiante </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="`/admin/estudiantes/${estudiante.idUser}`"> Ver Detalles </Link>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent> Ver detalles </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="destructive" size="sm" @click="router.delete(`/admin/estudiantes/${estudiante.idUser}`)">
                                                <Trash2 />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent> Eliminar estudiante </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Paginación -->
            <Pagination
                class="bg-rgb(214, 219, 216)"
                v-if="estudiantes.last_page > 1"
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

            <!-- Diálogo de edición -->
            <Dialog v-model:open="dialogOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Editar estudiante</DialogTitle>
                        <DialogDescription> Modifica los datos del estudiante y guarda los cambios. </DialogDescription>
                    </DialogHeader>
                    <div v-if="editEstudiante" class="grid gap-4 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="nombres" class="text-right">Nombres</Label>
                            <Input id="nombres" v-model="editEstudiante.user.nombres" class="col-span-3" />
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="primerApellido" class="text-right">Primer Apellido</Label>
                            <Input id="primerApellido" v-model="editEstudiante.user.primerApellido" class="col-span-3" />
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="segundoApellido" class="text-right">Segundo Apellido</Label>
                            <Input id="segundoApellido" v-model="editEstudiante.user.segundoApellido" class="col-span-3" />
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="email" class="text-right">Email</Label>
                            <Input id="email" v-model="editEstudiante.user.email" class="col-span-3" />
                        </div>
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
        </div>
    </AppLayout>
</template>
