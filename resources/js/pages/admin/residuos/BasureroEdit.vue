<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Basurero, FormBasurero } from '@/types/residuos';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trash2 } from 'lucide-vue-next';

// ===== PROPS =====
const props = defineProps<{
    basurero: Basurero;
}>();

// ===== COMPOSABLE =====
const { ROUTES, actualizarBasurero } = useResiduos();

// ===== FORM =====
const form = useForm<FormBasurero>({
    ubicacion: props.basurero.ubicacion,
    descripcion: props.basurero.descripcion || '',
    estado: Boolean(props.basurero.estado),
});

// ===== MÉTODOS =====
async function handleSubmit() {
    form.clearErrors();
    form.processing = true;
    actualizarBasurero(
        props.basurero.idBasurero,
        {
            ubicacion: form.ubicacion,
            descripcion: form.descripcion,
            estado: form.estado,
        },
        {
            onSuccess: () => {
            },
            onError: (errors: any) => {
                form.setError(errors);
            },
            onFinish: () => {
                form.processing = false;
            },
        }
    );
}
function handleCancel() {
    form.reset();
}
</script>
;

<template>
    <Head :title="`Editar Basurero - ${basurero.ubicacion}`" />

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
                            <h1 class="text-3xl font-bold">Editar Basurero</h1>
                            <p class="text-muted-foreground">Modificar información del punto de recolección</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="ROUTES.basureros.show(basurero.idBasurero)"> Ver Detalles </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <!-- ===== FORMULARIO ===== -->
            <div class="max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>Información del Basurero</CardTitle>
                        <CardDescription> Modifica la información del basurero ubicado en: {{ basurero.ubicacion }} </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="handleSubmit" class="space-y-6">
                            <!-- Ubicación -->
                            <div class="space-y-2">
                                <Label for="ubicacion">Ubicación *</Label>
                                <Input
                                    id="ubicacion"
                                    v-model="form.ubicacion"
                                    placeholder="Ej: Planta baja, Patio trasero, etc."
                                    :class="{ 'border-red-500': form.errors.ubicacion }"
                                />
                                <p v-if="form.errors.ubicacion" class="text-sm text-red-500">
                                    {{ form.errors.ubicacion }}
                                </p>
                            </div>

                            <!-- Descripción -->
                            <div class="space-y-2">
                                <Label for="descripcion">Descripción</Label>
                                <Textarea
                                    id="descripcion"
                                    v-model="form.descripcion"
                                    placeholder="Descripción adicional del basurero..."
                                    rows="3"
                                    :class="{ 'border-red-500': form.errors.descripcion }"
                                />
                                <p v-if="form.errors.descripcion" class="text-sm text-red-500">
                                    {{ form.errors.descripcion }}
                                </p>
                            </div>

                            <!-- Estado -->
                            <div class="flex items-center space-x-2">
                                <Checkbox id="estado" v-model:checked="form.estado" :class="{ 'border-red-500': form.errors.estado }" />
                                <Label for="estado" class="text-sm font-normal"> Basurero activo (disponible para depósitos) </Label>
                            </div>
                            <p v-if="form.errors.estado" class="text-sm text-red-500">
                                {{ form.errors.estado }}
                            </p>

                            <!-- Información adicional -->
                            <div class="bg-muted rounded-lg p-4">
                                <h4 class="mb-2 font-medium">Información del Basurero</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-muted-foreground">ID:</span>
                                        <span class="ml-2 font-mono">{{ basurero.idBasurero }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Estado actual:</span>
                                        <span class="ml-2" :class="basurero.estado ? 'text-green-600' : 'text-red-600'">
                                            {{ basurero.estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Depósitos registrados:</span>
                                        <span class="ml-2 font-medium">{{ basurero.depositos_count || 0 }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Creado:</span>
                                        <span class="ml-2">{{ new Date(basurero.created_at).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="flex items-center gap-4 pt-4">
                                <Button type="submit" :disabled="form.processing">
                                    <Save class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </Button>
                                <Button type="button" variant="outline" @click="handleCancel">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Restaurar
                                </Button>
                                <Button type="button" variant="ghost" as-child>
                                    <Link :href="ROUTES.basureros.index"> Cancelar </Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
