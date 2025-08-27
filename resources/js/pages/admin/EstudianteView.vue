<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Estudiante } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import { ArrowLeft, Award, Edit, GraduationCap, Mail, MapPin, QrCode, Trash2, User, Recycle, Calendar, TrendingUp } from 'lucide-vue-next';
import { toast, Toaster } from 'vue-sonner';
import  UserQrCode  from '@/components/UserQrCode.vue';
import 'vue-sonner/style.css';
import { ref } from 'vue';

// ===== PROPS =====
const props = defineProps<{
    estudiante: Estudiante;
    ultimosDepositos?: Array<{
        idDeposito: number;
        fechaHora: string;
        tipoBasura: {
            idTipoBasura: number;
            nombre: string;
            puntos: number;
        };
        basurero: {
            idBasurero: number;
            nombre: string;
            ubicacion: string;
        };
    }>;
    depositosPorTipo?: Array<{
        tipo: string;
        cantidad: number;
        puntos_totales: number;
        ultimo_deposito: string;
    }>;
    estadisticas?: {
        total_depositos: number;
        depositos_este_mes: number;
        kg_reciclados_estimados: number;
        dias_activo: number;
    };
}>();

// ===== MÉTODOS =====
const confirmOpen = ref(false);
function promptEliminarEstudiante() {
    confirmOpen.value = true;
}

function eliminarEstudiante() {
    confirmOpen.value = false;
    router.delete(`/admin/estudiantes/${props.estudiante.idUser}`, {
        onSuccess: () => {
            toast('Estudiante eliminado', {
                description: 'El estudiante ha sido eliminado correctamente',
                position: 'top-center',
            });
            router.visit('/admin/estudiantes');
        },
        onError: () => {
            toast('Error al eliminar', {
                description: 'No se pudo eliminar el estudiante',
                position: 'top-center',
            });
        },
    });
}

function editarEstudiante() {
    // Navegar de vuelta a la lista con el estudiante seleccionado para editar
    router.visit('/admin/estudiantes', {
        data: {
            editEstudianteId: props.estudiante.idUser,
        },
    });
}
const formatUserForQr = (user: any) => {
    if (!user) {
        return {
            id: 0,
            nombres: '',
            primerApellido: '',
            segundoApellido: '',
            email: '',
            qr_codigo: { id: '' },
        };
    }
    return {
        id: Number(user.id),
        nombres: user.nombres || '',
        primerApellido: user.primerApellido || '',
        segundoApellido: user.segundoApellido || '',
        email: user.email || '',
        qr_codigo: user.qr_codigo || '',
    };
};
</script>

<template>
    <Head :title="`Estudiante - ${estudiante.user.nombres} ${estudiante.user.primerApellido}`" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Button variant="outline" size="sm" as-child>
                            <Link href="/admin/estudiantes">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 class="text-3xl font-bold">Detalles del Estudiante</h1>
                            <p class="text-muted-foreground">Información completa del estudiante</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button @click="editarEstudiante" variant="outline">
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Button>
                        <Button @click="promptEliminarEstudiante" variant="destructive">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Eliminar
                        </Button>
                    </div>
                </div>
            </header>

            <!-- ===== INFORMACIÓN PRINCIPAL ===== -->
            <div class="grid gap-6">
                <!-- Tarjeta de información personal -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Información Personal
                        </CardTitle>
                        <CardDescription>Datos básicos del estudiante</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Nombres</label>
                                <p class="text-lg">{{ estudiante.user.nombres }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Apellidos</label>
                                <p class="text-lg">
                                    {{ estudiante.user.primerApellido }}
                                    {{ estudiante.user.segundoApellido || '' }}
                                </p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Email</label>
                                <p class="flex items-center gap-2 text-lg">
                                    <Mail class="h-4 w-4" />
                                    {{ estudiante.user.email }}
                                </p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">ID de Usuario</label>
                                <p class="font-mono text-lg">{{ estudiante.idUser }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de información académica -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <GraduationCap class="h-5 w-5" />
                            Información Académica
                        </CardTitle>
                        <CardDescription>Datos del curso y paralelo</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Curso</label>
                                <p class="flex items-center gap-2 text-lg">
                                    <MapPin class="h-4 w-4" />
                                    {{ estudiante.curso_paralelo?.curso?.nombre || 'Sin asignar' }}
                                </p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Paralelo</label>
                                <p class="text-lg">
                                    {{ estudiante.curso_paralelo?.paralelo?.nombre || 'Sin asignar' }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de puntos -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Award class="h-5 w-5" />
                            Sistema de Puntos
                        </CardTitle>
                        <CardDescription>Puntaje acumulado del estudiante</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-4">
                            <div class="text-primary text-4xl font-bold">
                                {{ estudiante.user?.puntaje?.puntajeTotal || 0 }}
                            </div>
                            <span class="bg-secondary text-secondary-foreground rounded-md px-2 py-1 text-lg">puntos</span>
                        </div>
                        <p class="text-muted-foreground mt-2 text-sm">Puntaje total acumulado por actividades de reciclaje</p>
                    </CardContent>
                </Card>

                <!-- Tarjeta de código QR -->
                <Card v-if="estudiante.user?.qr_codigo">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <QrCode class="h-5 w-5" />
                            Código QR
                        </CardTitle>
                        <CardDescription>Código QR único del estudiante</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-4">
                            <UserQrCode v-if="estudiante.user.qr_codigo" :user="formatUserForQr(estudiante.user)" class="h-32 w-32 rounded-lg border" />
                            <div>
                                <p class="text-muted-foreground text-sm">Este código QR es único para este estudiante y se utiliza para:</p>
                                <ul class="text-muted-foreground mt-2 space-y-1 text-sm">
                                    <li>• Identificación rápida</li>
                                    <li>• Registro de actividades</li>
                                    <li>• Control de acceso</li>
                                </ul>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de estadísticas -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="h-5 w-5" />
                            Estadísticas de Reciclaje
                        </CardTitle>
                        <CardDescription>Actividad reciente del estudiante</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="text-center">
                                <div class="text-primary text-2xl font-bold">{{ estadisticas?.depositos_este_mes || 0 }}</div>
                                <div class="text-muted-foreground text-sm">Depósitos este mes</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">{{ (estadisticas?.kg_reciclados_estimados || 0).toFixed(1) }}</div>
                                <div class="text-muted-foreground text-sm">Kg reciclados (est.)</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ estadisticas?.dias_activo || 0 }}</div>
                                <div class="text-muted-foreground text-sm">Días activo</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600">{{ estadisticas?.total_depositos || 0 }}</div>
                                <div class="text-muted-foreground text-sm">Total depósitos</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de depósitos por tipo de basura -->
                <Card v-if="depositosPorTipo && depositosPorTipo.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Recycle class="h-5 w-5" />
                            Depósitos por Tipo de Basura
                        </CardTitle>
                        <CardDescription>Últimos 30 días</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="deposito in depositosPorTipo" :key="deposito.tipo" class="flex items-center justify-between p-3 rounded-lg border">
                                <div class="flex items-center gap-3">
                                    <div class="bg-primary/10 text-primary rounded-full p-2">
                                        <Recycle class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ deposito.tipo }}</p>
                                        <p class="text-muted-foreground text-sm">{{ deposito.cantidad }} depósitos</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600">+{{ deposito.puntos_totales }} pts</p>
                                    <p class="text-muted-foreground text-xs">Último: {{ new Date(deposito.ultimo_deposito).toLocaleDateString() }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de últimos depósitos -->
                <Card v-if="ultimosDepositos && ultimosDepositos.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5" />
                            Últimos Depósitos
                        </CardTitle>
                        <CardDescription>Historial de actividades recientes</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div v-for="deposito in ultimosDepositos" :key="deposito.idDeposito" class="flex items-center justify-between p-3 rounded-lg bg-muted/30">
                                <div class="flex items-center gap-3">
                                    <div class="bg-green-100 text-green-700 rounded-full p-2">
                                        <Recycle class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ deposito.tipoBasura.nombre }}</p>
                                        <p class="text-muted-foreground text-sm">{{ deposito.basurero.nombre }} - {{ deposito.basurero.ubicacion }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600">+{{ deposito.tipoBasura.puntos }} pts</p>
                                    <p class="text-muted-foreground text-xs">{{ new Date(deposito.fechaHora).toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Toaster />
            <ConfirmDelete
                :open="confirmOpen"
                title="Confirmar eliminación"
                description="¿Estás seguro de que quieres eliminar este estudiante? Esta acción no se puede deshacer."
                @update:open="(v) => (confirmOpen = v)"
                @confirm="eliminarEstudiante"
                @cancel="confirmOpen = false"
            >
                <template #icon>
                    <Trash2 class="mr-2 h-4 w-4" />
                </template>
                <template #confirmLabel>Eliminar</template>
            </ConfirmDelete>
        </div>
    </AppLayout>
</template>
