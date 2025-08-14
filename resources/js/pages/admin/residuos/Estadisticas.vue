<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { EstadisticasResiduos, TopTipoBasura, TopUsuario } from '@/types/residuos';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Award, BarChart3, Calendar, Plus, Trash2, TrendingUp, Users } from 'lucide-vue-next';

// ===== PROPS =====
const props = defineProps<{
    estadisticas: EstadisticasResiduos;
    topUsuarios: TopUsuario[];
    topTiposBasura: TopTipoBasura[];
}>();

// ===== COMPOSABLE =====
const { ROUTES, formatearPuntos } = useResiduos();
</script>

<template>
    <Head title="Estadísticas de Residuos" />

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
                            <h1 class="text-3xl font-bold">Dashboard de Estadísticas</h1>
                            <p class="text-muted-foreground">Métricas y análisis del sistema de residuos</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="ROUTES.depositos.index">
                                <BarChart3 class="mr-2 h-4 w-4" />
                                Ver Depósitos
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <!-- ===== ESTADÍSTICAS PRINCIPALES ===== -->
            <div class="mb-8 grid gap-6">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center gap-2">
                                <Trash2 class="text-primary h-5 w-5" />
                                <div>
                                    <div class="text-2xl font-bold">{{ estadisticas.total_depositos.toLocaleString() }}</div>
                                    <p class="text-muted-foreground text-sm">Total depósitos</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center gap-2">
                                <Award class="h-5 w-5 text-green-600" />
                                <div>
                                    <div class="text-2xl font-bold text-green-600">{{ formatearPuntos(estadisticas.total_puntos) }}</div>
                                    <p class="text-muted-foreground text-sm">Puntos generados</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center gap-2">
                                <Users class="h-5 w-5 text-blue-600" />
                                <div>
                                    <div class="text-2xl font-bold text-blue-600">{{ estadisticas.usuarios_activos }}</div>
                                    <p class="text-muted-foreground text-sm">Usuarios activos</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center gap-2">
                                <Calendar class="h-5 w-5 text-orange-600" />
                                <div>
                                    <div class="text-2xl font-bold text-orange-600">{{ estadisticas.depositos_hoy }}</div>
                                    <p class="text-muted-foreground text-sm">Depósitos hoy</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Estadísticas temporales -->
                <div class="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <TrendingUp class="h-5 w-5" />
                                Actividad Reciente
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground text-sm">Esta semana</span>
                                    <span class="font-medium">{{ estadisticas.depositos_semana }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground text-sm">Este mes</span>
                                    <span class="font-medium">{{ estadisticas.depositos_mes }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground text-sm">Promedio diario</span>
                                    <span class="font-medium">{{ Math.round(estadisticas.depositos_mes / 30) }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Award class="h-5 w-5" />
                                Rendimiento
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground text-sm">Puntos promedio</span>
                                    <span class="font-medium">{{ Math.round(estadisticas.total_puntos / estadisticas.total_depositos) }} pts</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground text-sm">Depósitos por usuario</span>
                                    <span class="font-medium">{{ Math.round(estadisticas.total_depositos / estadisticas.usuarios_activos) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground text-sm">Participación</span>
                                    <span class="font-medium"
                                        >{{ Math.round((estadisticas.usuarios_activos / estadisticas.total_depositos) * 100) }}%</span
                                    >
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <BarChart3 class="h-5 w-5" />
                                Tendencias
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground text-sm">Crecimiento semanal</span>
                                    <Badge variant="secondary">+12%</Badge>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground text-sm">Crecimiento mensual</span>
                                    <Badge variant="secondary">+8%</Badge>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground text-sm">Meta mensual</span>
                                    <span class="font-medium">85%</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- ===== RANKINGS ===== -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Top Usuarios -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Top 5 Usuarios
                        </CardTitle>
                        <CardDescription>Usuarios con más puntos acumulados</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="topUsuarios.length > 0" class="space-y-4">
                            <div
                                v-for="(usuario, index) in topUsuarios"
                                :key="usuario.idUser"
                                class="bg-muted/50 flex items-center justify-between rounded-lg p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="bg-primary text-primary-foreground flex h-8 w-8 items-center justify-center rounded-full font-bold">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ usuario.user?.nombres }} {{ usuario.user?.primerApellido }}</p>
                                        <p class="text-muted-foreground text-sm">{{ formatearPuntos(usuario.total_puntos) }}</p>
                                    </div>
                                </div>
                                <Badge variant="secondary"> {{ Math.round((usuario.total_puntos / estadisticas.total_puntos) * 100) }}% </Badge>
                            </div>
                        </div>
                        <div v-else class="py-8 text-center">
                            <div class="text-muted-foreground">
                                <Users class="mx-auto mb-4 h-12 w-12" />
                                <p>No hay datos de usuarios</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Tipos de Basura -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Trash2 class="h-5 w-5" />
                            Top 5 Tipos de Basura
                        </CardTitle>
                        <CardDescription>Tipos más depositados</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="topTiposBasura.length > 0" class="space-y-4">
                            <div
                                v-for="(tipo, index) in topTiposBasura"
                                :key="tipo.idTipoBasura"
                                class="bg-muted/50 flex items-center justify-between rounded-lg p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-600 font-bold text-white">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ tipo.tipoBasura?.nombre }}</p>
                                        <p class="text-muted-foreground text-sm">{{ tipo.total_depositos }} depósitos</p>
                                    </div>
                                </div>
                                <Badge variant="secondary"> {{ Math.round((tipo.total_depositos / estadisticas.total_depositos) * 100) }}% </Badge>
                            </div>
                        </div>
                        <div v-else class="py-8 text-center">
                            <div class="text-muted-foreground">
                                <Trash2 class="mx-auto mb-4 h-12 w-12" />
                                <p>No hay datos de tipos de basura</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ===== ACCIONES RÁPIDAS ===== -->
            <Card class="mt-6">
                <CardHeader>
                    <CardTitle>Acciones Rápidas</CardTitle>
                    <CardDescription>Acceso directo a las funciones principales</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-4">
                        <Button as-child variant="outline" class="h-auto flex-col p-4">
                            <Link :href="ROUTES.depositos.create">
                                <Plus class="mb-2 h-6 w-6" />
                                <span>Nuevo Depósito</span>
                            </Link>
                        </Button>
                        <Button as-child variant="outline" class="h-auto flex-col p-4">
                            <Link :href="ROUTES.basureros.create">
                                <Trash2 class="mb-2 h-6 w-6" />
                                <span>Nuevo Basurero</span>
                            </Link>
                        </Button>
                        <Button as-child variant="outline" class="h-auto flex-col p-4">
                            <Link :href="ROUTES.tiposBasura.create">
                                <Award class="mb-2 h-6 w-6" />
                                <span>Nuevo Tipo</span>
                            </Link>
                        </Button>
                        <Button as-child variant="outline" class="h-auto flex-col p-4">
                            <Link :href="ROUTES.depositos.index">
                                <BarChart3 class="mb-2 h-6 w-6" />
                                <span>Ver Todos</span>
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
