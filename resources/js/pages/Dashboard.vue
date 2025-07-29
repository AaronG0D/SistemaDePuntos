<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

// Props que debe enviar el backend:
// estadisticas: {
//   depositos_hoy, depositos_semana, depositos_mes,
//   puntos_hoy, puntos_semana, puntos_mes,
//   variacion_mes, top_estudiantes, ranking_curso, ranking_paralelo
// }

interface Estadisticas {
    depositos_hoy: number;
    depositos_semana: number;
    depositos_mes: number;
    puntos_hoy: number;
    puntos_semana: number;
    puntos_mes: number;
    variacion_mes: number;
    top_estudiantes: Array<{ nombre: string; puntos: number }>;
    ranking_curso: Array<{ curso: string; puntos: number }>;
    ranking_paralelo: Array<{ paralelo: string; puntos: number }>;
}

const props = defineProps<{
    estadisticas: Estadisticas;
}>();

const porcentajeDepositos = computed(() => ({
    hoy: props.estadisticas.depositos_semana > 0 ? (props.estadisticas.depositos_hoy / props.estadisticas.depositos_semana) * 100 : 0,
    semana: props.estadisticas.depositos_mes > 0 ? (props.estadisticas.depositos_semana / props.estadisticas.depositos_mes) * 100 : 0,
}));
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardTitle>Depósitos Hoy</CardTitle>
                    <CardDescription>Total de depósitos realizados hoy</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div class="text-3xl font-bold">{{ props.estadisticas.depositos_hoy }}</div>
                        <Badge variant="outline">Hoy</Badge>
                    </div>
                    <Progress :value="porcentajeDepositos.hoy" class="mt-2" />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Depósitos Semana</CardTitle>
                    <CardDescription>Acumulado esta semana</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div class="text-3xl font-bold">{{ props.estadisticas.depositos_semana }}</div>
                        <Badge variant="outline">Semana</Badge>
                    </div>
                    <Progress :value="porcentajeDepositos.semana" class="mt-2" />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Depósitos Mes</CardTitle>
                    <CardDescription>Total mensual y variación</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div class="text-3xl font-bold">{{ props.estadisticas.depositos_mes }}</div>
                        <Badge :variant="props.estadisticas.variacion_mes > 0 ? 'secondary' : 'destructive'">
                            {{ props.estadisticas.variacion_mes > 0 ? '+' : '' }}{{ props.estadisticas.variacion_mes }}%
                        </Badge>
                    </div>
                    <div class="text-muted-foreground mt-2 text-sm">Comparado con el mes anterior</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Puntos del Mes</CardTitle>
                    <CardDescription>Total de puntos acumulados</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div class="text-3xl font-bold">{{ props.estadisticas.puntos_mes }}</div>
                        <Badge variant="secondary">Puntos</Badge>
                    </div>
                    <div class="text-muted-foreground mt-2 flex justify-between text-sm">
                        <span>Hoy: {{ props.estadisticas.puntos_hoy }}</span>
                        <span>Semana: {{ props.estadisticas.puntos_semana }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Separator class="my-8" />

        <div class="grid gap-6 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Top 10 Estudiantes</CardTitle>
                    <CardDescription>Mejores estudiantes por puntos acumulados</CardDescription>
                </CardHeader>
                <CardContent>
                    <ScrollArea class="h-[300px]">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12">#</TableHead>
                                    <TableHead>Estudiante</TableHead>
                                    <TableHead class="text-right">Puntos</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(est, i) in props.estadisticas.top_estudiantes" :key="est.nombre">
                                    <TableCell class="font-medium">{{ i + 1 }}</TableCell>
                                    <TableCell>{{ est.nombre }}</TableCell>
                                    <TableCell class="text-right">
                                        <Badge variant="secondary">{{ est.puntos }}</Badge>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </ScrollArea>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Ranking por Curso</CardTitle>
                    <CardDescription>Cursos con más puntos acumulados</CardDescription>
                </CardHeader>
                <CardContent>
                    <ScrollArea class="h-[300px]">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12">#</TableHead>
                                    <TableHead>Curso</TableHead>
                                    <TableHead class="text-right">Puntos</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(curso, i) in props.estadisticas.ranking_curso" :key="curso.curso">
                                    <TableCell class="font-medium">{{ i + 1 }}</TableCell>
                                    <TableCell>{{ curso.curso }}</TableCell>
                                    <TableCell class="text-right">
                                        <Badge variant="secondary">{{ curso.puntos }}</Badge>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </ScrollArea>
                </CardContent>
            </Card>

            <Card class="md:col-span-2">
                <CardHeader>
                    <CardTitle>Ranking por Paralelo</CardTitle>
                    <CardDescription>Paralelos ordenados por puntos totales</CardDescription>
                </CardHeader>
                <CardContent>
                    <ScrollArea class="h-[300px]">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12">#</TableHead>
                                    <TableHead>Paralelo</TableHead>
                                    <TableHead class="text-right">Puntos</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(paralelo, i) in props.estadisticas.ranking_paralelo" :key="paralelo.paralelo">
                                    <TableCell class="font-medium">{{ i + 1 }}</TableCell>
                                    <TableCell>{{ paralelo.paralelo }}</TableCell>
                                    <TableCell class="text-right">
                                        <Badge variant="secondary">{{ paralelo.puntos }}</Badge>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </ScrollArea>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
