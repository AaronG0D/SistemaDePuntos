<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Estudiante } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Award, Edit, GraduationCap, Mail, MapPin, QrCode, Trash2, User } from 'lucide-vue-next';
import { toast, Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';

// ===== PROPS =====
const props = defineProps<{
    estudiante: Estudiante;
}>();

// ===== MÉTODOS =====
function eliminarEstudiante() {
    if (confirm('¿Estás seguro de que quieres eliminar este estudiante?')) {
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
}

function editarEstudiante() {
    // Navegar de vuelta a la lista con el estudiante seleccionado para editar
    router.visit('/admin/estudiantes', {
        data: {
            editEstudianteId: props.estudiante.idUser,
        },
    });
}
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
                        <Button @click="eliminarEstudiante" variant="destructive">
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
                            <img :src="estudiante.user.qr_codigo" alt="QR Code" class="h-32 w-32 rounded-lg border" />
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

                <!-- Tarjeta de estadísticas (placeholder para futuras funcionalidades) -->
                <Card>
                    <CardHeader>
                        <CardTitle>Estadísticas</CardTitle>
                        <CardDescription>Actividad reciente del estudiante</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="text-center">
                                <div class="text-primary text-2xl font-bold">0</div>
                                <div class="text-muted-foreground text-sm">Actividades este mes</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">0</div>
                                <div class="text-muted-foreground text-sm">Kg reciclados</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">0</div>
                                <div class="text-muted-foreground text-sm">Días activo</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Toaster />
        </div>
    </AppLayout>
</template>
