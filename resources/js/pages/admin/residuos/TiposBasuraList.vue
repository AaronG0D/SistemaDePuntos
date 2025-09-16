<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PaginacionTiposBasura, TipoBasura } from '@/types/residuos';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Award, Edit, Eye, Plus, Recycle, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Switch } from '@/components/ui/switch';

// ===== PROPS =====
const props = defineProps<{
    tiposBasura: PaginacionTiposBasura;
}>();

// ===== COMPOSABLE =====
const { eliminarTipoBasura, ROUTES, formatearPuntos } = useResiduos();

// ===== REACTIVE =====
const searchTerm = ref('');

// ===== COMPUTED =====
const tiposFiltrados = computed(() => {
    if (!searchTerm.value) return props.tiposBasura.data;

    return props.tiposBasura.data.filter(
        (tipo) =>
            tipo.nombre.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            (tipo.descripcion && tipo.descripcion.toLowerCase().includes(searchTerm.value.toLowerCase())),
    );
});

// ===== MÉTODOS =====
function handleEliminar(tipo: TipoBasura) {
    eliminarTipoBasura(tipo.idTipoBasura);
}

function handleSearch() {
    // La búsqueda se hace en el cliente por simplicidad
}

function toggleEstado(tipo: TipoBasura) {
    router.patch(`/admin/tipos-basura/${tipo.idTipoBasura}/toggle-estado`);
}
</script>

<template>
    <Head title="Gestión de Tipos de Basura" />

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
                            <h1 class="text-3xl font-bold flex items-center gap-3">
                                <Recycle class="h-8 w-8 text-green-600" />
                                Gestión de Tipos de Basura
                            </h1>
                            <p class="text-muted-foreground">Administra los tipos de residuos y sus puntos</p>
                        </div>
                    </div>
                    <Button as-child>
                        <Link :href="ROUTES.tiposBasura.create">
                            <Plus class="mr-2 h-4 w-4" />
                            Nuevo Tipo
                        </Link>
                    </Button>
                </div>
            </header>

            <!-- ===== BÚSQUEDA ===== -->
            <Card class="mb-6">
                <CardContent class="pt-6">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="search">Buscar tipos de basura</Label>
                            <div class="relative">
                                <Search class="text-muted-foreground absolute top-3 left-3 h-4 w-4" />
                                <Input
                                    id="search"
                                    v-model="searchTerm"
                                    placeholder="Buscar por nombre o descripción..."
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
                        <div class="text-2xl font-bold">{{ tiposBasura.total }}</div>
                        <p class="text-muted-foreground text-sm">Total de tipos</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-2xl font-bold text-green-600">
                            {{ formatearPuntos(tiposBasura.data.reduce((sum, t) => sum + t.puntos, 0)) }}
                        </div>
                        <p class="text-muted-foreground text-sm">Total puntos disponibles</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-2xl font-bold text-blue-600">
                            {{ Math.round(tiposBasura.data.reduce((sum, t) => sum + t.puntos, 0) / tiposBasura.data.length) }}
                        </div>
                        <p class="text-muted-foreground text-sm">Promedio de puntos</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-2xl font-bold text-orange-600">
                            {{ tiposBasura.data.reduce((sum, t) => sum + (t.depositos_count || 0), 0) }}
                        </div>
                        <p class="text-muted-foreground text-sm">Total depósitos</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ===== TABLA ===== -->
            <Card>
                <CardHeader>
                    <CardTitle>Lista de Tipos de Basura</CardTitle>
                    <CardDescription> {{ tiposFiltrados.length }} de {{ tiposBasura.total }} tipos </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="tiposFiltrados.length > 0" class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead>Puntos</TableHead>
                                    <TableHead>Depósitos</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="tipo in tiposFiltrados" :key="tipo.idTipoBasura">
                                    <TableCell class="font-medium">{{ tipo.nombre }}</TableCell>
                                    <TableCell>
                                        <span v-if="tipo.descripcion" class="text-muted-foreground">
                                            {{ tipo.descripcion }}
                                        </span>
                                        <span v-else class="text-muted-foreground italic">Sin descripción</span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary" class="font-medium">
                                            {{ formatearPuntos(tipo.puntos) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span class="font-medium">{{ tipo.depositos_count || 0 }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Switch :checked="Boolean((tipo as any).estado)" @update:checked="() => toggleEstado(tipo)" />
                                            <span class="text-sm" :class="{ 'text-green-600': (tipo as any).estado, 'text-muted-foreground': !(tipo as any).estado }">
                                                {{ (tipo as any).estado ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="ROUTES.tiposBasura.show(tipo.idTipoBasura)">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="ROUTES.tiposBasura.edit(tipo.idTipoBasura)">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="handleEliminar(tipo)">
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
                            <Award class="mx-auto mb-4 h-12 w-12" />
                            <p>No se encontraron tipos de basura</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
