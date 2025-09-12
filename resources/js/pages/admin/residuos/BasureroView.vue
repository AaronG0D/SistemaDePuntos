<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Basurero } from '@/types/residuos';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Award, Calendar, Edit, MapPin, Users } from 'lucide-vue-next';
import { computed } from 'vue';

// ===== PROPS =====
const props = defineProps<{
    basurero: Basurero & {
        depositos: Array<{
            idDeposito: number;
            fechaHora: string;
            user: {
                id: number;
                nombres: string;
                primerApellido: string;
            };
            tipoBasura: {
                idTipoBasura: number;
                nombre: string;
                puntos: number;
            };
        }>;
    };
}>();

// ===== COMPOSABLE =====
const { ROUTES, formatearFecha, formatearPuntos } = useResiduos();

// ===== COMPUTED =====
const estadisticas = computed(() => {
    const depositos = props.basurero.depositos || [];
    const totalPuntos = depositos.reduce((sum, d) => sum + (d.tipo_basura?.puntos || 0), 0);
    const usuariosUnicos = new Set(depositos.map((d) => d.user?.id).filter(Boolean)).size;

    return {
        totalDepositos: depositos.length,
        totalPuntos,
        usuariosUnicos,
        promedioPuntos: depositos.length > 0 ? Math.round(totalPuntos / depositos.length) : 0,
    };
});

const depositosRecientes = computed(() => {
    return props.basurero.depositos.map((deposito) => ({
        ...deposito,
        nombreCompleto: `${deposito.user.nombres} ${deposito.user.primerApellido}`,
        tipoBasura: deposito.tipo_basura?.nombre,
        puntosGenerados: deposito.tipo_basura?.puntos,
    }));
});

const actividadResumen = computed(() => {
    const depositos = props.basurero.depositos || [];
    return {
        tiposResiduo: [...new Set(depositos.map((d) => d.tipo_basura?.nombre))],
        ultimoDeposito: depositos[0]?.fechaHora ? formatearFecha(depositos[0].fechaHora) : 'Sin depósitos',
        totalPuntos: depositos.reduce((sum, d) => sum + (d.tipo_basura?.puntos || 0), 0),
    };
});
</script>

<template>
    <Head :title="`Basurero - ${basurero.ubicacion}`" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="ROUTES.basureros.index">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 class="text-3xl font-bold">Detalles del Basurero</h1>
                            <p class="text-muted-foreground">Información completa del punto de recolección</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="ROUTES.basureros.edit(basurero.idBasurero)">
                                <Edit class="mr-2 h-4 w-4" />
                                Editar
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <!-- ===== INFORMACIÓN PRINCIPAL ===== -->
            <div class="grid gap-6">
                <!-- Tarjeta de información básica -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <MapPin class="h-5 w-5" />
                            Información del Basurero
                        </CardTitle>
                        <CardDescription>Datos del punto de recolección</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Ubicación</label>
                                <p class="text-lg font-medium">{{ basurero.ubicacion }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Estado</label>
                                <div class="mt-1">
                                    <Badge :variant="basurero.estado ? 'default' : 'secondary'">
                                        {{ basurero.estado ? 'Activo' : 'Inactivo' }}
                                    </Badge>
                                </div>
                            </div>
                            <div v-if="basurero.descripcion">
                                <label class="text-muted-foreground text-sm font-medium">Descripción</label>
                                <p class="text-lg">{{ basurero.descripcion }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">ID del Basurero</label>
                                <p class="font-mono text-lg">{{ basurero.idBasurero }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de estadísticas -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Award class="h-5 w-5" />
                            Estadísticas
                        </CardTitle>
                        <CardDescription>Actividad del basurero</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="text-center">
                                <div class="text-primary text-2xl font-bold">{{ estadisticas.totalDepositos }}</div>
                                <div class="text-muted-foreground text-sm">Total depósitos</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">{{ formatearPuntos(estadisticas.totalPuntos) }}</div>
                                <div class="text-muted-foreground text-sm">Puntos generados</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ estadisticas.usuariosUnicos }}</div>
                                <div class="text-muted-foreground text-sm">Usuarios únicos</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600">{{ formatearPuntos(estadisticas.promedioPuntos) }}</div>
                                <div class="text-muted-foreground text-sm">Promedio por depósito</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de depósitos recientes -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Depósitos Recientes
                        </CardTitle>
                        <CardDescription>Últimos 10 depósitos registrados</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="depositosRecientes.length > 0" class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Usuario</TableHead>
                                        <TableHead>Tipo de Residuo</TableHead>
                                        <TableHead>Puntos</TableHead>
                                        <TableHead>Fecha</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="deposito in depositosRecientes" :key="deposito.idDeposito">
                                        <TableCell>
                                            <div class="space-y-1">
                                                <p class="font-medium">{{ deposito.nombreCompleto }}</p>
                                                <p class="text-muted-foreground text-sm">ID: {{ deposito.idDeposito }}</p>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="space-y-1">
                                                <Badge variant="outline">
                                                    {{ deposito.tipoBasura }}
                                                </Badge>
                                                <p class="text-muted-foreground text-xs">Peso: No especificado</p>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="space-y-1">
                                                <Badge variant="secondary" class="text-lg"> {{ deposito.puntosGenerados }} pts </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="space-y-1">
                                                <p>{{ formatearFecha(deposito.fechaHora) }}</p>
                                                <p class="text-muted-foreground text-xs">
                                                    {{ new Date(deposito.fechaHora).toLocaleTimeString() }}
                                                </p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                        <div v-else class="py-8 text-center">
                            <div class="text-muted-foreground">
                                <Users class="mx-auto mb-4 h-12 w-12" />
                                <p>No hay depósitos registrados en este basurero</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de información adicional -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5" />
                            Información Adicional
                        </CardTitle>
                        <CardDescription>Detalles del registro</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Fecha de creación</label>
                                <p class="text-lg">{{ formatearFecha(basurero.created_at) }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Última actualización</label>
                                <p class="text-lg">{{ formatearFecha(basurero.updated_at) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
