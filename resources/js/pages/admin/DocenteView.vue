<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useDocente } from '@/composables/useDocente';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Docente } from '@/types/admin';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen, Edit, GraduationCap, Mail, MapPin, Trash2, User, Users } from 'lucide-vue-next';
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';

// ===== PROPS =====
const props = defineProps<{
    docente: Docente;
}>();

// ===== COMPOSABLE =====
const {
    materiasUnicas,
    cursosUnicos,
    estadisticas,
    nombreCompleto,
    eliminarDocente,
    editarDocente,
    generarKeyAsignacion,
    generarKeyCursoParalelo,
    ROUTES,
} = useDocente(props.docente);
</script>

<template>
    <Head :title="`Docente - ${nombreCompleto}`" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Button variant="outline" size="sm" as-child>
                            <Link href="/admin/docentes">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 class="text-3xl font-bold">Detalles del Docente</h1>
                            <p class="text-muted-foreground">Información completa del docente y sus asignaciones</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button @click="editarDocente" variant="outline" as-child>
                            <Link :href="ROUTES.editar(docente.idDocente)">
                                <Edit class="mr-2 h-4 w-4" />
                                Editar
                            </Link>
                        </Button>
                        <Button @click="eliminarDocente" variant="destructive">
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
                        <CardDescription>Datos básicos del docente</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Nombres</label>
                                <p class="text-lg">{{ docente.user.nombres }}</p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Apellidos</label>
                                <p class="text-lg">
                                    {{ docente.user.primerApellido }}
                                    {{ docente.user.segundoApellido || '' }}
                                </p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">Email</label>
                                <p class="flex items-center gap-2 text-lg">
                                    <Mail class="h-4 w-4" />
                                    {{ docente.user.email }}
                                </p>
                            </div>
                            <div>
                                <label class="text-muted-foreground text-sm font-medium">ID de Docente</label>
                                <p class="font-mono text-lg">{{ docente.idDocente }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de materias asignadas -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BookOpen class="h-5 w-5" />
                            Materias Asignadas
                        </CardTitle>
                        <CardDescription>Materias que imparte el docente</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="materiasUnicas.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div v-for="materia in materiasUnicas" :key="materia.idMateria" class="rounded-lg border bg-blue-50 p-4">
                                <div class="flex items-center gap-2 text-gray-900">
                                    <BookOpen class="h-4 w-4 text-blue-600" />
                                    <span class="font-medium">{{ materia.nombre }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-muted-foreground py-8 text-center">
                            <BookOpen class="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                            <p>No hay materias asignadas</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de cursos asignados -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <GraduationCap class="h-5 w-5" />
                            Cursos Asignados
                        </CardTitle>
                        <CardDescription>Cursos y paralelos donde imparte clases</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="cursosUnicos.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="cursoParalelo in cursosUnicos"
                                :key="generarKeyCursoParalelo(cursoParalelo)"
                                class="rounded-lg border bg-green-50 p-4"
                            >
                                <div class="flex items-center gap-2 text-gray-900">
                                    <GraduationCap class="h-4 w-4 text-green-600" />
                                    <div class="text-gray-900">
                                        <span class="font-medium">{{ cursoParalelo.curso.nombre }}</span>
                                        <span class="text-muted-foreground text-sm"> - {{ cursoParalelo.paralelo.nombre }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-muted-foreground py-8 text-center">
                            <GraduationCap class="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                            <p>No hay cursos asignados</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de asignaciones detalladas -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <MapPin class="h-5 w-5" />
                            Asignaciones Detalladas
                        </CardTitle>
                        <CardDescription>Relación completa de materias y cursos</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="docente.docente_materia_cursos.length > 0" class="space-y-4">
                            <div
                                v-for="asignacion in docente.docente_materia_cursos"
                                :key="generarKeyAsignacion(asignacion)"
                                class="flex items-center justify-between rounded-lg border bg-gray-50 p-4"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-2 text-gray-900">
                                        <BookOpen class="h-4 w-4 text-blue-600" />
                                        <span class="font-medium">{{ asignacion.materia.nombre }}</span>
                                    </div>
                                    <span class="text-muted-foreground">→</span>
                                    <div class="flex items-center gap-2 text-gray-900">
                                        <GraduationCap class="h-4 w-4 text-green-600" />
                                        <span>{{ asignacion.curso_paralelo.curso.nombre }} - {{ asignacion.curso_paralelo.paralelo.nombre }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-muted-foreground py-8 text-center">
                            <Users class="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                            <p>No hay asignaciones registradas</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tarjeta de estadísticas (placeholder para futuras funcionalidades) -->
                <Card>
                    <CardHeader>
                        <CardTitle>Estadísticas</CardTitle>
                        <CardDescription>Actividad del docente</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="text-center">
                                <div class="text-primary text-2xl font-bold">{{ estadisticas.materiasAsignadas }}</div>
                                <div class="text-muted-foreground text-sm">Materias asignadas</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">{{ estadisticas.cursosAsignados }}</div>
                                <div class="text-muted-foreground text-sm">Cursos asignados</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ estadisticas.asignacionesTotales }}</div>
                                <div class="text-muted-foreground text-sm">Asignaciones totales</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Toaster />
        </div>
    </AppLayout>
</template>
