<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pagination, PaginationContent, PaginationItem, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Estudiante } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, SquarePen, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
</script>
<template>
    <Head title="Estudiantes" />

    <AppLayout>
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
                                <Button as-child size="sm" class="bg-primary">
                                    <Link :href="`/admin/estudiantes/${estudiante.idUser}/edit`">Editar <SquarePen /> </Link>
                                </Button>
                                <Button variant="ghost" size="sm" as-child>
                                    <Link :href="`/admin/estudiantes/${estudiante.idUser}`">Ver Detalles</Link>
                                </Button>
                                <Button variant="destructive" size="sm" @click="router.delete(`/admin/estudiantes/${estudiante.idUser}`)">
                                    <Trash2 />
                                </Button>
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
        </div>
    </AppLayout>
</template>
