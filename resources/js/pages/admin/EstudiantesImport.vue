<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle,
    CheckCircle2,
    Edit,
    FileCheck,
    FileSpreadsheet,
    GraduationCap,
    History,
    Info,
    List,
    Loader2,
    Upload,
    UserCheck,
    UserMinus,
    UserPlus,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

// Props
const { historialImportaciones } = defineProps<{
    historialImportaciones: any[];
}>();

// Estados reactivos
const importFile = ref<File | null>(null);
const isImporting = ref(false);
const descargandoPlantilla = ref(false);
const importResults = ref<any>(null);
const historialReactivo = ref<any[]>([...historialImportaciones]);

// CSRF token
const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

// Manejar selección de archivo (solo Excel)
function handleFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;
    const file = target.files[0];

    // Validar por extensión
    const extOk = /\.(xlsx|xls)$/i.test(file.name);
    // Validar por MIME conocido (algunos navegadores pueden no proveerlo correctamente)
    const knownMimes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
    const mimeOk = !file.type || knownMimes.includes(file.type);

    if (!extOk || !mimeOk) {
        toast('Archivo no válido', {
            description: 'Solo se permite Excel (.xlsx, .xls).',
            icon: XCircle,
        });
        // Limpiar selección
        target.value = '';
        importFile.value = null;
        return;
    }

    // Límite 10MB
    if (file.size > 10 * 1024 * 1024) {
        toast('Archivo demasiado grande', {
            description: 'El archivo debe pesar menos de 10MB.',
            icon: XCircle,
        });
        target.value = '';
        importFile.value = null;
        return;
    }

    importFile.value = file;
    toast('Archivo seleccionado', {
        description: `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`,
    });
}

// Importar estudiantes
async function importarEstudiantes() {
    if (!importFile.value) {
        toast('Error', {
            description: 'Por favor selecciona un archivo Excel',
            icon: XCircle,
        });
        return;
    }

    isImporting.value = true;
    // Toast de carga hasta terminar
    const loadingId = toast('Importando...', {
        description: 'Procesando el archivo, por favor espera...',
        duration: 999999,
    });

    try {
        const formData = new FormData();
        formData.append('archivo', importFile.value);

        const response = await fetch(route('admin.estudiantes.importar'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            body: formData,
        });

        const data = await response.json();

        if (response.ok && data.success) {
            importResults.value = data.data;

            // Actualizar historial en tiempo real
            if (data.data?.historial && Array.isArray(data.data.historial)) {
                historialReactivo.value = [...data.data.historial];
            }

            const insertados = data.data?.insertados ?? 0;
            const actualizados = data.data?.actualizados ?? 0;
            const erroresCount = data.data?.errores?.length ?? 0;
            const affected = insertados + actualizados;

            if (erroresCount > 0) {
                toast('Importación con observaciones', {
                    description: `Se procesaron ${affected} estudiantes, pero hay ${erroresCount} errores que revisar.`,
                    icon: XCircle,
                });
            } else {
                toast('Importación exitosa', {
                    description: `Se procesaron ${affected} estudiantes correctamente.`,
                });

                // Limpiar archivo después de éxito
                importFile.value = null;
                const fileInput = document.getElementById('file-upload') as HTMLInputElement;
                if (fileInput) fileInput.value = '';
            }
        } else {
            importResults.value = data.data || null;

            // Actualizar historial incluso en errores
            if (data.data?.historial && Array.isArray(data.data.historial)) {
                historialReactivo.value = [...data.data.historial];
            }

            toast('Error en importación', {
                description: data.message || 'No se pudo procesar el archivo',
                icon: XCircle,
            });
        }
    } catch (error: any) {
        toast('Error de conexión', {
            description: 'No se pudo conectar con el servidor. Intenta nuevamente.',
            icon: XCircle,
        });
    } finally {
        isImporting.value = false;
        // Cerrar toast de carga
        try {
            (toast as any).dismiss && (toast as any).dismiss(loadingId);
        } catch {}
    }
}

// Descargar plantilla
async function descargarPlantilla() {
    descargandoPlantilla.value = true;

    try {
        window.location.href = route('admin.estudiantes.plantilla');
        toast('Descarga iniciada', {
            description: 'La plantilla se está descargando...',
        });
    } catch (error) {
        toast('Error', {
            description: 'No se pudo descargar la plantilla',
            icon: XCircle,
        });
    } finally {
        setTimeout(() => {
            descargandoPlantilla.value = false;
        }, 2000);
    }
}

// Formatear fecha y hora
function formatDateTime(dateString: string) {
    return new Date(dateString).toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

// Obtener resultados de importación desde session si existen
onMounted(() => {
    // Si hay resultados en la sesión (después de redirect), mostrarlos
    const sessionResults = (window as any).importResults;
    if (sessionResults) {
        importResults.value = sessionResults;
        // Limpiar de la sesión
        delete (window as any).importResults;
    }
});
</script>

<template>
    <AppLayout>
        <Head title="Importar Estudiantes" />
        <div class="container mx-auto py-6">
            <!-- Header -->
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <Link
                                :href="route('admin.estudiantes')"
                                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            >
                                <ArrowLeft class="h-5 w-5" />
                            </Link>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Importar Estudiantes</h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Carga masiva desde archivo Excel</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <Button variant="outline" @click="descargarPlantilla" :disabled="descargandoPlantilla">
                                <FileSpreadsheet class="mr-2 h-4 w-4" />
                                {{ descargandoPlantilla ? 'Descargando...' : 'Descargar plantilla' }}
                            </Button>
                            <Link :href="route('admin.estudiantes')" class="btn btn-secondary">
                                <List class="mr-2 h-4 w-4" />
                                Ver lista
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                    <!-- Instrucciones compactas - Sidebar izquierdo -->
                    <div class="lg:col-span-1">
                        <Card class="border-blue-200 dark:border-gray-700">
                            <CardHeader class="pb-3">
                                <CardTitle class="flex items-center gap-2 text-lg">
                                    <Info class="h-5 w-5 text-blue-500" />
                                    Guía rápida
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3 text-sm">
                                <div class="flex items-start gap-2">
                                    <FileSpreadsheet class="mt-0.5 h-4 w-4 flex-shrink-0 text-blue-500" />
                                    <span>Descarga la plantilla Excel</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <Edit class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-500" />
                                    <span
                                        >Curso en <code class="rounded bg-gray-100 px-1 text-xs dark:bg-gray-700">B2</code>, Paralelo en
                                        <code class="rounded bg-gray-100 px-1 text-xs dark:bg-gray-700">E2</code></span
                                    >
                                </div>
                                <div class="flex items-start gap-2">
                                    <Users class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-500" />
                                    <span>Datos desde fila 13</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <Upload class="mt-0.5 h-4 w-4 flex-shrink-0 text-orange-500" />
                                    <span>Sube y procesa</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Contenido principal y historial -->
                    <div class="lg:col-span-3">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            <!-- Área principal: Subir archivo y resultados -->
                            <div class="space-y-6 lg:col-span-2">
                                <!-- Resultados de importación ARRIBA -->
                                <Card v-if="importResults" class="border-green-200 dark:border-gray-700">
                                    <CardHeader class="pb-4">
                                        <CardTitle class="flex items-center gap-3 text-2xl">
                                            <CheckCircle class="h-8 w-8 text-green-500" />
                                            Resultados de la importación
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent class="space-y-6">
                                        <!-- Métricas principales -->
                                        <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                                            <div
                                                class="rounded-xl border border-green-200 bg-green-50 p-4 text-center dark:border-green-800 dark:bg-green-900/20"
                                            >
                                                <UserPlus class="mx-auto mb-2 h-6 w-6 text-green-600 dark:text-green-400" />
                                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                                    {{ importResults.insertados || 0 }}
                                                </div>
                                                <div class="text-sm font-medium text-green-700 dark:text-green-300">Insertados</div>
                                            </div>
                                            <div
                                                class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-center dark:border-blue-800 dark:bg-blue-900/20"
                                            >
                                                <UserCheck class="mx-auto mb-2 h-6 w-6 text-blue-600 dark:text-blue-400" />
                                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                                    {{ importResults.actualizados || 0 }}
                                                </div>
                                                <div class="text-sm font-medium text-blue-700 dark:text-blue-300">Actualizados</div>
                                            </div>
                                            <div
                                                class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-center dark:border-yellow-800 dark:bg-yellow-900/20"
                                            >
                                                <UserMinus class="mx-auto mb-2 h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                                                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                                    {{ importResults.omitidos || 0 }}
                                                </div>
                                                <div class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Omitidos</div>
                                            </div>
                                        </div>

                                        <!-- Información del curso -->
                                        <div
                                            v-if="importResults.curso_nombre || importResults.paralelo_nombre"
                                            class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20"
                                        >
                                            <div class="flex items-center gap-2 text-sm font-medium text-blue-800 dark:text-blue-200">
                                                <GraduationCap class="h-4 w-4" />
                                                {{ importResults.curso_nombre || '-' }} - {{ importResults.paralelo_nombre || '-' }}
                                            </div>
                                        </div>

                                        <!-- Errores si los hay -->
                                        <div v-if="importResults.errores && importResults.errores.length > 0" class="space-y-3">
                                            <h4 class="flex items-center gap-2 text-lg font-semibold text-red-800 dark:text-red-200">
                                                <AlertTriangle class="h-5 w-5" />
                                                {{ importResults.errores.length }} errores encontrados
                                            </h4>
                                            <div
                                                class="max-h-40 space-y-2 overflow-y-auto rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20"
                                            >
                                                <div
                                                    v-for="(error, index) in importResults.errores.slice(0, 5)"
                                                    :key="index"
                                                    class="text-sm text-red-700 dark:text-red-300"
                                                >
                                                    <span class="font-medium">Fila {{ error.fila }}:</span> {{ error.mensaje }}
                                                </div>
                                                <div v-if="importResults.errores.length > 5" class="text-xs text-red-600 italic">
                                                    ... y {{ importResults.errores.length - 5 }} errores más
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Subida de archivo - EN EL CENTRO MÁS ANCHO -->
                                <Card
                                    class="border-2 border-dashed border-gray-300 transition-colors hover:border-blue-400 dark:border-gray-600 dark:hover:border-blue-500"
                                >
                                    <CardHeader class="pb-4">
                                        <CardTitle class="flex items-center gap-3 text-2xl">
                                            <Upload class="h-8 w-8 text-green-500" />
                                            Subir archivo Excel
                                        </CardTitle>
                                        <p class="text-gray-600 dark:text-gray-400">Selecciona tu archivo Excel con los datos de estudiantes</p>
                                    </CardHeader>
                                    <CardContent class="space-y-6">
                                        <div class="space-y-4">
                                            <Label for="file-upload" class="text-lg font-medium">Archivo Excel (.xlsx, .xls)</Label>
                                            <div class="flex items-center gap-4">
                                                <Input
                                                    id="file-upload"
                                                    type="file"
                                                    accept=".xlsx,.xls"
                                                    @change="handleFileSelect"
                                                    class="w-full max-w-xs border border-gray-300 text-base transition-colors hover:border-blue-400 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none dark:border-gray-600 dark:hover:border-blue-500 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-900"
                                                />
                                                <Button
                                                    @click="importarEstudiantes"
                                                    :disabled="!importFile || isImporting"
                                                    size="lg"
                                                    class="min-w-[160px] text-base"
                                                >
                                                    <Upload v-if="!isImporting" class="mr-2 h-5 w-5" />
                                                    <Loader2 v-else class="mr-2 h-5 w-5 animate-spin" />
                                                    {{ isImporting ? 'Importando...' : 'Importar archivo' }}
                                                </Button>
                                            </div>
                                            <div
                                                v-if="importFile"
                                                class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20"
                                            >
                                                <FileCheck class="h-6 w-6 text-blue-600" />
                                                <div>
                                                    <p class="font-medium text-blue-900 dark:text-blue-100">{{ importFile.name }}</p>
                                                    <p class="text-sm text-blue-700 dark:text-blue-300">
                                                        {{ (importFile.size / 1024 / 1024).toFixed(2) }} MB
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Historial - Esquina superior derecha SIN SCROLL -->
                            <div class="lg:col-span-1">
                                <Card class="h-fit">
                                    <CardHeader class="pb-3">
                                        <CardTitle class="flex items-center justify-between text-lg">
                                            <span class="flex items-center gap-2">
                                                <History class="h-5 w-5 text-gray-500" />
                                                Historial
                                            </span>
                                            <span class="text-sm font-normal text-gray-500">({{ historialReactivo.length }})</span>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="space-y-3">
                                            <div
                                                v-for="item in historialReactivo.slice(0, 8)"
                                                :key="item.id"
                                                class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20"
                                            >
                                                <div class="mb-2 flex items-start justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <CheckCircle2 v-if="item.errores === 0" class="h-4 w-4 flex-shrink-0 text-green-500" />
                                                        <XCircle v-else class="h-4 w-4 flex-shrink-0 text-red-500" />
                                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {{ item.curso_nombre }} - {{ item.paralelo_nombre }}
                                                        </span>
                                                    </div>
                                                    <span class="text-xs text-gray-500">
                                                        {{ formatDateTime(item.created_at) }}
                                                    </span>
                                                </div>
                                                <div class="flex flex-wrap gap-1">
                                                    <span
                                                        class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300"
                                                    >
                                                        +{{ item.insertados }}
                                                    </span>
                                                    <span
                                                        class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                                                    >
                                                        ~{{ item.actualizados }}
                                                    </span>
                                                    <span
                                                        v-if="item.omitidos > 0"
                                                        class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300"
                                                    >
                                                        ={{ item.omitidos }}
                                                    </span>
                                                    <span
                                                        v-if="item.errores > 0"
                                                        class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300"
                                                    >
                                                        !{{ item.errores }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div v-if="historialReactivo.length === 0" class="py-8 text-center text-gray-500">
                                                <History class="mx-auto mb-3 h-12 w-12 opacity-50" />
                                                <p>No hay importaciones recientes</p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
