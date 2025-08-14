<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Basurero, Deposito, FiltrosDepositos, PaginacionDepositos, TipoBasura } from '@/types/residuos';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Award, Calendar, Edit, Eye, FileText, Filter, Plus, Search, Table2, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// ===== PROPS =====
const props = defineProps<{
    depositos: PaginacionDepositos;
    basureros: Basurero[];
    tiposBasura: TipoBasura[];
    filters: FiltrosDepositos;
}>();

// ===== COMPOSABLE =====
const { eliminarDeposito, ROUTES, formatearFecha, formatearPuntos } = useResiduos();

// ===== REACTIVE =====
const searchTerm = ref(props.filters?.usuario || '');
const selectedBasurero = ref(props.filters?.basurero?.toString() || '0');
const selectedTipoBasura = ref(props.filters?.tipo_basura?.toString() || '0');
const selectedFecha = ref(props.filters?.fecha || '');

// ===== COMPUTED =====
const depositosFiltrados = computed(() => {
    let filtered = props.depositos.data;

    // Debug para verificar los datos
    console.log(
        'Datos de depósitos:',
        filtered.map((d) => ({
            id: d.idDeposito,
            tipoBasura: d.tipoBasura?.created_at,
            puntos: d.tipoBasura?.puntos,
        })),
    );

    if (searchTerm.value) {
        filtered = filtered.filter((deposito) =>
            `${deposito.user?.nombres} ${deposito.user?.primerApellido}`.toLowerCase().includes(searchTerm.value.toLowerCase()),
        );
    }

    if (selectedBasurero.value && selectedBasurero.value !== '0') {
        filtered = filtered.filter((deposito) => deposito.idBasurero.toString() === selectedBasurero.value);
    }

    if (selectedTipoBasura.value && selectedTipoBasura.value !== '0') {
        filtered = filtered.filter((deposito) => deposito.idTipoBasura.toString() === selectedTipoBasura.value);
    }

    if (selectedFecha.value) {
        filtered = filtered.filter((deposito) => new Date(deposito.fechaHora).toISOString().split('T')[0] === selectedFecha.value);
    }

    return filtered;
});

// ===== MÉTODOS =====
function handleEliminar(deposito: Deposito) {
    eliminarDeposito(deposito.idDeposito);
}

function handleFiltrar() {
    const filters: FiltrosDepositos = {};

    if (searchTerm.value) filters.usuario = searchTerm.value;
    if (selectedBasurero.value && selectedBasurero.value !== '0') filters.basurero = parseInt(selectedBasurero.value);
    if (selectedTipoBasura.value && selectedTipoBasura.value !== '0') filters.tipo_basura = parseInt(selectedTipoBasura.value);
    if (selectedFecha.value) filters.fecha = selectedFecha.value;

    router.get(ROUTES.depositos.index, filters, {
        preserveState: true,
        preserveScroll: true,
    });
}

function limpiarFiltros() {
    searchTerm.value = '';
    selectedBasurero.value = '0';
    selectedTipoBasura.value = '0';
    selectedFecha.value = '';

    router.get(
        ROUTES.depositos.index,
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head title="Gestión de Depósitos" />

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
                            <h1 class="text-3xl font-bold">Gestión de Depósitos</h1>
                            <p class="text-muted-foreground">Administra los registros de depósitos de residuos</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                          <FileText class="mr-2 h-4 w-4" />
                                Exportar PDF
                        <Button as-child>
                            <Link :href="ROUTES.depositos.create">
                                <Plus class="mr-2 h-4 w-4" />
                                Nuevo Depósito
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <!-- ===== FILTROS ===== -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Filter class="h-5 w-5" />
                        Filtros
                    </CardTitle>
                    <CardDescription>Filtra los depósitos según tus criterios</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <Label for="search">Buscar usuario</Label>
                            <div class="relative">
                                <Search class="text-muted-foreground absolute top-3 left-3 h-4 w-4" />
                                <Input id="search" v-model="searchTerm" placeholder="Nombre del usuario..." class="pl-10" />
                            </div>
                        </div>
                        <div>
                            <Label for="basurero">Basurero</Label>
                            <Select v-model="selectedBasurero">
                                <SelectTrigger class="w-full">
                                    <SelectValue :placeholder="selectedBasurero ? 'Selecciona un basurero' : 'Todos los basureros'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="0">Todos los basureros</SelectItem>
                                    <SelectItem
                                        v-for="basurero in props.basureros"
                                        :key="basurero.idBasurero"
                                        :value="basurero.idBasurero.toString()"
                                    >
                                        {{ basurero.ubicacion }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label for="tipo">Tipo de Basura</Label>
                            <Select v-model="selectedTipoBasura">
                                <SelectTrigger class="w-full">
                                    <SelectValue :placeholder="selectedTipoBasura ? 'Selecciona un tipo' : 'Todos los tipos'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="0">Todos los tipos</SelectItem>
                                    <SelectItem v-for="tipo in props.tiposBasura" :key="tipo.idTipoBasura" :value="tipo.idTipoBasura.toString()">
                                        {{ tipo.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label for="fecha">Fecha</Label>
                            <Input id="fecha" v-model="selectedFecha" type="date" placeholder="Seleccionar fecha" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <Button @click="handleFiltrar" variant="outline">
                            <Filter class="mr-2 h-4 w-4" />
                            Aplicar Filtros
                        </Button>
                        <Button @click="limpiarFiltros" variant="ghost"> Limpiar Filtros </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- ===== ESTADÍSTICAS ===== -->
            <div class="mb-6 grid gap-4 md:grid-cols-4">
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-2xl font-bold">{{ depositos.total }}</div>
                        <p class="text-muted-foreground text-sm">Total depósitos</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-2xl font-bold text-green-600">
                            {{ formatearPuntos(depositos.data.reduce((sum, d) => sum + (d.tipoBasura?.puntos || 0), 0)) }}
                        </div>
                        <p class="text-muted-foreground text-sm">Total puntos generados</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-2xl font-bold text-blue-600">
                            {{ new Set(depositos.data.map((d) => d.idUser)).size }}
                        </div>
                        <p class="text-muted-foreground text-sm">Usuarios únicos</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-2xl font-bold text-orange-600">
                            {{ new Set(depositos.data.map((d) => d.idBasurero)).size }}
                        </div>
                        <p class="text-muted-foreground text-sm">Basureros utilizados</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ===== TABLA ===== -->
            <Card>
                <CardHeader>
                    <CardTitle>Lista de Depósitos</CardTitle>
                    <CardDescription> {{ depositosFiltrados.length }} de {{ depositos.total }} depósitos </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="depositosFiltrados.length > 0" class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Usuario</TableHead>
                                    <TableHead>Basurero</TableHead>
                                    <TableHead>Tipo de Residuo</TableHead>
                                    <TableHead>Puntos</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="deposito in depositosFiltrados" :key="deposito.idDeposito">
                                    <TableCell class="font-medium"> {{ deposito.user?.nombres }} {{ deposito.user?.primerApellido }} </TableCell>
                                    <TableCell>{{ deposito.basurero?.ubicacion }}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline" class="bg-green-50 text-green-700 dark:bg-green-900 dark:text-green-100">
                                            {{ deposito.tipoBasura?.nombre || 'Sin especificar' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary" class="bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-100">
                                            {{ formatearPuntos(deposito.tipoBasura?.puntos || 0) }} pts
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ formatearFecha(deposito.fechaHora) }}</TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="ROUTES.depositos.show(deposito.idDeposito)">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="ROUTES.depositos.edit(deposito.idDeposito)">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="handleEliminar(deposito)">
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
                            <Calendar class="mx-auto mb-4 h-12 w-12" />
                            <p>No se encontraron depósitos</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
