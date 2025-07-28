<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { TipoBasura } from '@/types/residuos';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Award, Calendar, Edit, Trash2, Users } from 'lucide-vue-next';
import { computed } from 'vue';

// ===== PROPS =====
const props = defineProps<{
    tipoBasura: TipoBasura & {
        depositos: Array<{
            idDeposito: number;
            fechaHora: string;
            user: {
                id: number;
                nombres: string;
                primerApellido: string;
            };
            basurero: {
                idBasurero: number;
                ubicacion: string;
            };
        }>;
    };
}>();

// ===== COMPOSABLE =====
const { ROUTES, formatearFecha, formatearPuntos } = useResiduos();

// ===== COMPUTED =====
const estadisticas = computed(() => {
    const depositos = props.tipoBasura.depositos || [];
    const totalPuntos = depositos.length * props.tipoBasura.puntos;
    const usuariosUnicos = new Set(depositos.map((d) => d.user?.id).filter(Boolean)).size;
    const basurerosUnicos = new Set(depositos.map((d) => d.basurero?.idBasurero).filter(Boolean)).size;

    return {
        totalDepositos: depositos.length,
        totalPuntos,
        usuariosUnicos,
        basurerosUnicos,
        promedioPorUsuario: usuariosUnicos > 0 ? Math.round(depositos.length / usuariosUnicos) : 0,
    };
});

const depositosRecientes = computed(() => {
    return (props.tipoBasura.depositos || []).slice(0, 10);
});
</script>

<template>
    <Head :title="`Tipo de Basura - ${tipoBasura.nombre}`" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="ROUTES.tiposBasura.index">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 class="text-3xl font-bold">Detalles del Tipo de Basura</h1>
                            <p class="text-muted-foreground">Información completa del tipo de residuo</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="ROUTES.tiposBasura.edit(tipoBasura.idTipoBasura)">
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
                            <Trash2 class="h-5 w-5" />
                            Información del Tipo de Basura
                        </CardTitle>
                        <CardDescription>Datos del tipo de residuo</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Nombre</label>
                                <p class="text-lg font-medium">{{ tipoBasura.nombre }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Puntos por depósito</label>
                                <div class="mt-1">
                                    <Badge variant="secondary" class="text-lg">
                                        {{ formatearPuntos(tipoBasura.puntos) }}
                                    </Badge>
                                </div>
                            </div>
                            <div v-if="tipoBasura.descripcion">
                                <label class="text-muted-foreground text-sm font-medium">Descripción</label>
                                <p class="text-lg">{{ tipoBasura.descripcion }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">ID del Tipo</label>
                                <p class="font-mono text-lg">{{ tipoBasura.idTipoBasura }}</p>
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
                        <CardDescription>Actividad del tipo de basura</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
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
                                <div class="text-2xl font-bold text-orange-600">{{ estadisticas.basurerosUnicos }}</div>
                                <div class="text-muted-foreground text-sm">Basureros utilizados</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600">{{ estadisticas.promedioPorUsuario }}</div>
                                <div class="text-muted-foreground text-sm">Promedio por usuario</div>
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
                        <CardDescription>Últimos 10 depósitos de este tipo</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="depositosRecientes.length > 0" class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Usuario</TableHead>
                                        <TableHead>Basurero</TableHead>
                                        <TableHead>Puntos</TableHead>
                                        <TableHead>Fecha</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="deposito in depositosRecientes" :key="deposito.idDeposito">
                                        <TableCell class="font-medium"> {{ deposito.user.nombres }} {{ deposito.user.primerApellido }} </TableCell>
                                        <TableCell>{{ deposito.basurero?.ubicacion || 'N/A' }}</TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {{ formatearPuntos(tipoBasura.puntos) }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{{ formatearFecha(deposito.fechaHora) }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                        <div v-else class="py-8 text-center">
                            <div class="text-muted-foreground">
                                <Trash2 class="mx-auto mb-4 h-12 w-12" />
                                <p>No hay depósitos registrados de este tipo</p>
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
                                <p class="text-lg">{{ formatearFecha(tipoBasura.created_at) }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Última actualización</label>
                                <p class="text-lg">{{ formatearFecha(tipoBasura.updated_at) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
