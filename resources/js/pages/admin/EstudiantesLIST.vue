<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search } from 'lucide-vue-next';

interface Estudiante {
    id: number;
    nombres: string;
    primerApellido: string;
    segundoApellido?: string;
    email: string;
    curso: string;
    paralelo: string;
    qr_codigo?: string;
    puntos_totales: number;
}

const estudiantes = ref<Estudiante[]>([]);
const searchQuery = ref('');
const isLoading = ref(true);

const filteredEstudiantes = computed(() => {
    return estudiantes.value.filter(estudiante => 
        estudiante.nombres.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        estudiante.primerApellido.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        estudiante.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        estudiante.curso.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

onMounted(async () => {
    try {
        const response = await axios.get('/api/estudiantes');
        estudiantes.value = response.data;
    } catch (error) {
        console.error('Error al cargar estudiantes:', error);
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <Head title="Estudiantes" />

    <div class="container mx-auto py-6">
        <header class="mb-6">
            <h1 class="text-3xl font-bold">Estudiantes</h1>
            <p class="text-muted-foreground">Gestiona la lista de estudiantes del sistema</p>
        </header>

        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2">
                <Input
                    v-model="searchQuery"
                    placeholder="Buscar estudiantes..."
                    class="w-[300px]"
                >
                    <template #prefix>
                        <Search class="h-4 w-4 text-muted-foreground" />
                    </template>
                </Input>
            </div>
            <Button>Agregar Estudiante</Button>
        </div>

        <div class="border rounded-lg">
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
                    <TableRow
                        v-for="estudiante in filteredEstudiantes"
                        :key="estudiante.id"
                        class="hover:bg-muted/50"
                    >
                        <TableCell>{{ estudiante.nombres }}</TableCell>
                        <TableCell>
                            {{ estudiante.primerApellido }}
                            {{ estudiante.segundoApellido }}
                        </TableCell>
                        <TableCell>{{ estudiante.email }}</TableCell>
                        <TableCell>{{ estudiante.curso }}</TableCell>
                        <TableCell>{{ estudiante.paralelo }}</TableCell>
                        <TableCell>{{ estudiante.puntos_totales }}</TableCell>
                        <TableCell>
                            <img 
                                v-if="estudiante.qr_codigo"
                                :src="estudiante.qr_codigo"
                                alt="QR Code"
                                class="h-8 w-8"
                            />
                        </TableCell>
                        <TableCell class="text-right">
                            <Button variant="ghost" size="sm">
                                Editar
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>