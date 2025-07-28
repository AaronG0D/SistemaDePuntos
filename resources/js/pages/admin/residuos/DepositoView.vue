<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Deposito } from '@/types/residuos';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Edit, MapPin, Trash2, User } from 'lucide-vue-next';

// ===== PROPS =====
const props = defineProps<{
    deposito: Deposito & {
        user: {
            id: number;
            nombres: string;
            primerApellido: string;
        };
        basurero: {
            idBasurero: number;
            ubicacion: string;
            descripcion?: string;
        };
        tipoBasura: {
            idTipoBasura: number;
            nombre: string;
            descripcion?: string;
            puntos: number;
        };
    };
}>();

// ===== COMPOSABLE =====
const { ROUTES, formatearFecha, formatearPuntos } = useResiduos();
</script>

<template>
    <Head :title="`Depósito #${deposito.idDeposito}`" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="ROUTES.depositos.index">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 class="text-3xl font-bold">Detalles del Depósito</h1>
                            <p class="text-muted-foreground">Información completa del depósito #{{ deposito.idDeposito }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="ROUTES.depositos.edit(deposito.idDeposito)">
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
                            Información del Depósito
                        </CardTitle>
                        <CardDescription>Datos del depósito registrado</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">ID del Depósito</label>
                                <p class="font-mono text-lg">{{ deposito.idDeposito }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Puntos Generados</label>
                                <div class="mt-1">
                                    <Badge variant="secondary" class="text-lg">
                                        {{ formatearPuntos(deposito.tipoBasura?.puntos || 0) }}
                                    </Badge>
                                </div>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Fecha y Hora</label>
                                <p class="text-lg">{{ formatearFecha(deposito.fechaHora) }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Registrado</label>
                                <p class="text-lg">{{ formatearFecha(deposito.created_at) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de usuario -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Usuario
                        </CardTitle>
                        <CardDescription>Información del usuario que realizó el depósito</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">ID del Usuario</label>
                                <p class="font-mono text-lg">{{ deposito.user?.id }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Nombre Completo</label>
                                <p class="text-lg font-medium">{{ deposito.user?.nombres }} {{ deposito.user?.primerApellido }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de basurero -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <MapPin class="h-5 w-5" />
                            Basurero
                        </CardTitle>
                        <CardDescription>Información del punto de recolección</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">ID del Basurero</label>
                                <p class="font-mono text-lg">{{ deposito.basurero?.idBasurero }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Ubicación</label>
                                <p class="text-lg font-medium">{{ deposito.basurero?.ubicacion }}</p>
                            </div>
                            <div v-if="deposito.basurero?.descripcion">
                                <label class="text-muted-foreground text-sm font-medium">Descripción</label>
                                <p class="text-lg">{{ deposito.basurero.descripcion }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de tipo de basura -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Trash2 class="h-5 w-5" />
                            Tipo de Basura
                        </CardTitle>
                        <CardDescription>Información del tipo de residuo depositado</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">ID del Tipo</label>
                                <p class="font-mono text-lg">{{ deposito.tipoBasura?.idTipoBasura }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Nombre</label>
                                <p class="text-lg font-medium">{{ deposito.tipoBasura?.nombre }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Puntos por Depósito</label>
                                <div class="mt-1">
                                    <Badge variant="secondary" class="text-lg">
                                        {{ formatearPuntos(deposito.tipoBasura?.puntos || 0) }}
                                    </Badge>
                                </div>
                            </div>
                            <div v-if="deposito.tipoBasura?.descripcion">
                                <label class="text-muted-foreground text-sm font-medium">Descripción</label>
                                <p class="text-lg">{{ deposito.tipoBasura.descripcion }}</p>
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
                                <p class="text-lg">{{ formatearFecha(deposito.created_at) }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Última actualización</label>
                                <p class="text-lg">{{ formatearFecha(deposito.updated_at) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
