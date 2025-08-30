<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { BookOpen, ChevronRight, GraduationCap, LineChart, Star, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Estudiante {
    id: number;
    nombres: string;
    apellidos: string;
    puntaje: number;
}

interface Materia {
    idMateria: number;
    nombre: string;
}

interface CursoInfo {
    idCursoParalelo: string;
    curso: {
        nombre: string;
        paralelo: string;
    };
    materias: Materia[];
    estudiantes: Estudiante[];
}

interface EstudiantesResponse {
    data: Estudiante[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    cursosYMaterias: CursoInfo[];
}>();

const currentCursoInfo = computed(() => {
    if (!cursoActivo.value) return null;
    return props.cursosYMaterias.find((c) => String(c.idCursoParalelo) === String(cursoActivo.value)) || null;
});

import { onMounted } from 'vue';

onMounted(() => {
    if (!cursoActivo.value && props.cursosYMaterias && props.cursosYMaterias.length > 0) {
        cursoActivo.value = String(props.cursosYMaterias[0].idCursoParalelo);
        // precargar estudiantes del primer curso
        console.log('Props cursosYMaterias:', props.cursosYMaterias);
        cargarEstudiantes(cursoActivo.value);
    }
});

const cursoActivo = ref<string | null>(null);
const materiaSeleccionada = ref<number | null>(null);
const estadisticas = ref<any>(null);
const searchQuery = ref('');
const estudiantes = ref<EstudiantesResponse>({
    data: [],
    total: 0,
    per_page: 10,
    current_page: 1,
    last_page: 1,
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function debounceSearch() {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        if (cursoActivo.value) {
            cargarEstudiantes(cursoActivo.value);
        }
    }, 300);
}
const showDetalles = ref(false);
const estudianteSeleccionado = ref<Estudiante | null>(null);
const materiasSeleccionadas = ref<number[]>([]);
const selectedMateriasMasivo = ref<number[]>([]);

async function cargarEstudiantes(idCursoParalelo: string, page: number = 1) {
    try {
        console.log('Cargando estudiantes para curso:', idCursoParalelo, 'search=', searchQuery.value, 'page=', page);
        // Usar la ruta web autenticada para mantener la sesión y middleware web
        const response = await fetch(`/docente/estudiantes/${idCursoParalelo}?page=${page}&search=${encodeURIComponent(searchQuery.value)}`, {
            credentials: 'same-origin',
        });
        const data = await response.json();
        console.log('Respuesta estudiantes:', data);
        estudiantes.value = data;
        cursoActivo.value = idCursoParalelo;
    } catch (error) {
        console.error('Error cargando estudiantes:', error);
    }
}

function cambiarPagina(page: number) {
    if (cursoActivo.value) {
        cargarEstudiantes(cursoActivo.value, page);
    }
}

function mostrarDetalles(estudiante: Estudiante) {
    estudianteSeleccionado.value = estudiante;
    materiasSeleccionadas.value = [];
    showDetalles.value = true;
}

function toggleMateria(materiaId: number) {
    const index = materiasSeleccionadas.value.indexOf(materiaId);
    if (index === -1) {
        materiasSeleccionadas.value.push(materiaId);
    } else {
        materiasSeleccionadas.value.splice(index, 1);
    }
}

function toggleMateriaMasivo(materiaId: number) {
    const index = selectedMateriasMasivo.value.indexOf(materiaId);
    if (index === -1) {
        selectedMateriasMasivo.value.push(materiaId);
    } else {
        selectedMateriasMasivo.value.splice(index, 1);
    }
}

async function generarReporte() {
    if (!estudianteSeleccionado.value || !cursoActivo.value) return;

    try {
        const response = await fetch('/api/docente/reporte-puntos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                estudiante_id: estudianteSeleccionado.value.id,
                curso_id: cursoActivo.value,
                materias: materiasSeleccionadas.value,
            }),
        });

        const result = await response.json();
        console.log('Resultado reporte:', result);
        if (response.ok) {
            // Mostrar confirmación simple al usuario
            alert(result.message || 'Reporte solicitado correctamente');
            showDetalles.value = false;
        } else {
            alert(result.error || 'Error generando el reporte');
            console.error('Error generando el reporte:', result);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function generarReporteMasivo(idCursoParalelo: string) {
    try {
        if (!selectedMateriasMasivo.value.length) {
            alert('Selecciona al menos una materia para generar el reporte masivo.');
            return;
        }

        const response = await fetch('/api/docente/reporte-masivo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                curso_id: idCursoParalelo,
                materias: selectedMateriasMasivo.value,
            }),
        });

        const result = await response.json();
        console.log('Resultado reporte masivo:', result);
        if (response.ok) {
            alert(result.message || `Reporte masivo solicitado para ${result.total} estudiantes.`);
            // limpiar selección opcional
            selectedMateriasMasivo.value = [];
        } else {
            alert(result.error || 'Error generando el reporte masivo');
            console.error('Error reporte masivo:', result);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function cargarEstadisticasCurso(idCursoParalelo: string) {
    try {
        const response = await fetch(`/docente/reportes/curso/${idCursoParalelo}`, { credentials: 'same-origin' });
        estadisticas.value = await response.json();
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

async function cargarEstadisticasMateria(idCursoParalelo: string, idMateria: number) {
    try {
        const response = await fetch(`/docente/reportes/materia/${idCursoParalelo}/${idMateria}`, { credentials: 'same-origin' });
        estadisticas.value = await response.json();
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}
</script>

<template>
    <Head title="Dashboard Docente" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <header class="mb-6">
                <h1 class="text-3xl font-bold">Dashboard Docente</h1>
                <p class="text-muted-foreground">Gestiona tus cursos y estudiantes</p>
            </header>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="info in cursosYMaterias" :key="info.idCursoParalelo">
                    <CardHeader>
                        <CardTitle
                            class="flex items-center gap-2"
                            @click="
                                cursoActivo = String(info.idCursoParalelo);
                                cargarEstudiantes(info.idCursoParalelo);
                            "
                        >
                            <GraduationCap class="h-5 w-5" />
                            {{ info.curso.nombre }} "{{ info.curso.paralelo }}"
                        </CardTitle>
                        <CardDescription> {{ info.materias.length || 0 }} materias · {{ info.estudiantes.length || 0 }} estudiantes </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Tabs @update:value="materiaSeleccionada = null">
                            <TabsList class="w-full">
                                <TabsTrigger value="estudiantes">
                                    <Users class="mr-2 h-4 w-4" />
                                    Estudiantes
                                </TabsTrigger>
                                <TabsTrigger value="materias">
                                    <BookOpen class="mr-2 h-4 w-4" />
                                    Materias
                                </TabsTrigger>
                                <TabsTrigger value="reportes">
                                    <LineChart class="mr-2 h-4 w-4" />
                                    Reportes
                                </TabsTrigger>
                            </TabsList>

                            <TabsContent value="estudiantes">
                                <div class="space-y-4">
                                    <!-- Barra de búsqueda -->
                                    <div class="flex items-center gap-2">
                                        <div class="relative flex-1">
                                            <i class="text-muted-foreground absolute top-2.5 left-2 h-4 w-4">🔍</i>
                                            <Input v-model="searchQuery" placeholder="Buscar estudiantes..." class="pl-8" @input="debounceSearch" />
                                        </div>
                                        <Button variant="outline" size="sm" @click="cargarEstudiantes(info.idCursoParalelo)">
                                            <i class="h-4 w-4">⟳</i>
                                        </Button>
                                    </div>

                                    <!-- Lista de estudiantes con ScrollArea -->
                                    <ScrollArea class="h-[400px]">
                                        <div class="space-y-2 rounded-lg border p-4">
                                            <div
                                                v-for="estudiante in estudiantes.data"
                                                :key="estudiante.id"
                                                class="hover:bg-muted/50 flex items-center justify-between rounded-lg border p-3 transition-colors"
                                            >
                                                <div>
                                                    <p class="font-medium">{{ estudiante.nombres }}</p>
                                                    <p class="text-muted-foreground text-sm">{{ estudiante.apellidos }}</p>
                                                </div>
                                                <div class="flex items-center gap-4">
                                                    <div class="flex flex-col items-end">
                                                        <div class="flex items-center gap-1">
                                                            <Star class="h-4 w-4 text-yellow-400" />
                                                            {{ estudiante.puntaje }}
                                                        </div>
                                                        <p class="text-muted-foreground text-xs">puntos totales</p>
                                                    </div>
                                                    <Button variant="ghost" size="sm" @click="mostrarDetalles(estudiante)">
                                                        <ChevronRight class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </ScrollArea>

                                    <!-- Paginación -->
                                    <div v-if="estudiantes.total > estudiantes.per_page" class="flex justify-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :disabled="estudiantes.current_page === 1"
                                            @click="cambiarPagina(estudiantes.current_page - 1)"
                                        >
                                            Anterior
                                        </Button>
                                        <Button
                                            v-for="page in estudiantes.last_page"
                                            :key="page"
                                            variant="outline"
                                            size="sm"
                                            :class="{ 'bg-primary text-white': page === estudiantes.current_page }"
                                            @click="cambiarPagina(page)"
                                        >
                                            {{ page }}
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :disabled="estudiantes.current_page === estudiantes.last_page"
                                            @click="cambiarPagina(estudiantes.current_page + 1)"
                                        >
                                            Siguiente
                                        </Button>
                                    </div>
                                </div>

                                <!-- Dialog de detalles del estudiante -->
                                <Dialog v-model:open="showDetalles">
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Detalles del Estudiante</DialogTitle>
                                            <DialogDescription> Puntos acumulados y materias seleccionadas </DialogDescription>
                                        </DialogHeader>

                                        <div v-if="estudianteSeleccionado" class="space-y-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h3 class="font-medium">{{ estudianteSeleccionado.nombres }}</h3>
                                                    <p class="text-muted-foreground text-sm">
                                                        {{ estudianteSeleccionado.apellidos }}
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <Star class="h-5 w-5 text-yellow-400" />
                                                    <span class="text-xl font-bold">
                                                        {{ estudianteSeleccionado.puntaje }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <Label>Validar puntos para las materias:</Label>
                                                <div class="space-y-2">
                                                    <div
                                                        v-for="materia in info.materias || []"
                                                        :key="materia.idMateria"
                                                        class="flex items-center space-x-2"
                                                    >
                                                        <Checkbox
                                                            :id="'materia-' + materia.idMateria"
                                                            :checked="materiasSeleccionadas.includes(materia.idMateria)"
                                                            @change="toggleMateria(materia.idMateria)"
                                                            :value="materia.idMateria"
                                                        />
                                                        <Label :for="'materia-' + materia.idMateria">
                                                            {{ materia.nombre }}
                                                        </Label>
                                                    </div>
                                                </div>
                                            </div>

                                            <Button class="w-full" @click="generarReporte"> Generar Reporte de Puntos </Button>
                                        </div>
                                    </DialogContent>
                                </Dialog>
                            </TabsContent>

                            <TabsContent value="materias">
                                <div class="space-y-2">
                                    <div class="space-y-2">
                                        <div
                                            v-for="materia in info.materias || []"
                                            :key="materia.idMateria"
                                            class="flex items-center justify-between rounded-lg border p-2"
                                        >
                                            <div>
                                                <p class="font-medium">{{ materia.nombre }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <Checkbox
                                                    :id="'masivo-' + info.idCursoParalelo + '-' + materia.idMateria"
                                                    :checked="selectedMateriasMasivo.includes(materia.idMateria)"
                                                    @change="toggleMateriaMasivo(materia.idMateria)"
                                                />
                                            </div>
                                        </div>

                                        <div class="pt-2">
                                            <Button class="w-full" @click="generarReporteMasivo(info.idCursoParalelo)"
                                                >Generar reporte masivo para materias seleccionadas</Button
                                            >
                                        </div>
                                    </div>
                                </div>
                            </TabsContent>

                            <TabsContent value="reportes">
                                <div class="space-y-4">
                                    <div v-if="!materiaSeleccionada">
                                        <h4 class="mb-2 font-medium">Selecciona una materia:</h4>
                                        <div class="space-y-2">
                                            <Button
                                                v-for="materia in info.materias || []"
                                                :key="materia.idMateria"
                                                variant="outline"
                                                class="w-full justify-start"
                                                @click="
                                                    materiaSeleccionada = materia.idMateria;
                                                    cargarEstadisticasMateria(info.idCursoParalelo, materia.idMateria);
                                                "
                                            >
                                                <BookOpen class="mr-2 h-4 w-4" />
                                                {{ materia.nombre }}
                                            </Button>
                                        </div>
                                    </div>

                                    <div v-if="materiaSeleccionada && estadisticas" class="space-y-4">
                                        <Button variant="ghost" size="sm" @click="materiaSeleccionada = null"> Volver a materias </Button>

                                        <div class="grid gap-4">
                                            <Card>
                                                <CardHeader>
                                                    <CardTitle>Estadísticas de Puntos</CardTitle>
                                                </CardHeader>
                                                <CardContent>
                                                    <dl class="grid gap-2 sm:grid-cols-2">
                                                        <div>
                                                            <dt class="text-muted-foreground text-sm">Total Estudiantes</dt>
                                                            <dd class="text-2xl font-bold">{{ estadisticas.total_estudiantes }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-muted-foreground text-sm">Puntos Totales</dt>
                                                            <dd class="text-2xl font-bold">{{ estadisticas.puntos_totales }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-muted-foreground text-sm">Promedio</dt>
                                                            <dd class="text-2xl font-bold">{{ Math.round(estadisticas.promedio_puntos) }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-muted-foreground text-sm">Máximo</dt>
                                                            <dd class="text-2xl font-bold">{{ estadisticas.maximo_puntos }}</dd>
                                                        </div>
                                                    </dl>
                                                </CardContent>
                                            </Card>
                                        </div>
                                    </div>
                                </div>
                            </TabsContent>
                        </Tabs>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
