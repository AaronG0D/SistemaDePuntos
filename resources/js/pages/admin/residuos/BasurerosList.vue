<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Basurero, PaginacionBasureros } from '@/types/residuos';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Edit, Eye, Plus, Search, ToggleLeft, ToggleRight, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';

// ===== PROPS =====
const props = defineProps<{
    basureros: PaginacionBasureros;
}>();

// ===== COMPOSABLE =====
const { eliminarBasurero, toggleEstadoBasurero, ROUTES } = useResiduos();

// ===== REACTIVE =====
const searchTerm = ref('');

// ===== COMPUTED =====
const basurerosFiltrados = computed(() => {
    if (!searchTerm.value) return props.basureros.data;

    return props.basureros.data.filter(
        (basurero) =>
            basurero.ubicacion.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            (basurero.descripcion && basurero.descripcion.toLowerCase().includes(searchTerm.value.toLowerCase())),
    );
});

// ===== MÉTODOS =====
function handleEliminar(basurero: Basurero) {
    eliminarBasurero(basurero.idBasurero);
}

function handleToggleEstado(basurero: Basurero) {
    toggleEstadoBasurero(basurero.idBasurero);
}

function handleSearch() {
    // La búsqueda se hace en el cliente por simplicidad
    // En un caso real, se enviaría al servidor
}
</script>

<template>
    <Head title="Gestión de Basureros" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Button variant="outline" size="sm" as-child>
                            <Link href="/dashboard">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 class="text-3xl font-bold">Gestión de Basureros</h1>
                            <p class="text-muted-foreground">Administra los puntos de recolección de residuos</p>
                        </div>
                    </div>
                    <Button as-child>
                        <Link :href="ROUTES.basureros.create">
                            <Plus class="mr-2 h-4 w-4" />
                            Nuevo Basurero
                        </Link>
                    </Button>
                </div>
            </header>

            <!-- ===== BÚSQUEDA ===== -->
            <Card class="mb-6">
                <CardContent class="pt-6">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="search">Buscar basureros</Label>
                            <div class="relative">
                                <Search class="text-muted-foreground absolute top-3 left-3 h-4 w-4" />
                                <Input
                                    id="search"
                                    v-model="searchTerm"
                                    placeholder="Buscar por ubicación o descripción..."
                                    class="pl-10"
                                    @input="handleSearch"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- ===== ESTADÍSTICAS ===== -->
            <div class="mb-6 grid gap-4 md:grid-cols-4">
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-2xl font-bold">{{ basureros.total }}</div>
                        <p class="text-muted-foreground text-sm">Total de basureros</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-2xl font-bold text-green-600">
                            {{ basureros.data.filter((b) => b.estado).length }}
                        </div>
                        <p class="text-muted-foreground text-sm">Basureros activos</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-2xl font-bold text-red-600">
                            {{ basureros.data.filter((b) => !b.estado).length }}
                        </div>
                        <p class="text-muted-foreground text-sm">Basureros inactivos</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-2xl font-bold text-blue-600">
                            {{ basureros.data.reduce((sum, b) => sum + (b.depositos_count || 0), 0) }}
                        </div>
                        <p class="text-muted-foreground text-sm">Total depósitos</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ===== TABLA ===== -->
            <Card>
                <CardHeader>
                    <CardTitle>Lista de Basureros</CardTitle>
                    <CardDescription> {{ basurerosFiltrados.length }} de {{ basureros.total }} basureros </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="basurerosFiltrados.length > 0" class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Ubicación</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Depósitos</TableHead>
                                    <TableHead>Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="basurero in basurerosFiltrados" :key="basurero.idBasurero">
                                    <TableCell class="font-medium">{{ basurero.ubicacion }}</TableCell>
                                    <TableCell>
                                        <span v-if="basurero.descripcion" class="text-muted-foreground">
                                            {{ basurero.descripcion }}
                                        </span>
                                        <span v-else class="text-muted-foreground italic">Sin descripción</span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="basurero.estado ? 'default' : 'secondary'">
                                            {{ basurero.estado ? 'Activo' : 'Inactivo' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span class="font-medium">{{ basurero.depositos_count || 0 }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="ROUTES.basureros.show(basurero.idBasurero)">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="ROUTES.basureros.edit(basurero.idBasurero)">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="outline" size="sm" @click="handleToggleEstado(basurero)">
                                                <ToggleRight v-if="basurero.estado" class="h-4 w-4" />
                                                <ToggleLeft v-else class="h-4 w-4" />
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="handleEliminar(basurero)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div v-else class="py-8 text-center">
                        <div class="text-muted-foreground">
                            <Search class="mx-auto mb-4 h-12 w-12" />
                            <p>No se encontraron basureros</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Toaster />
        </div>
    </AppLayout>
</template>
