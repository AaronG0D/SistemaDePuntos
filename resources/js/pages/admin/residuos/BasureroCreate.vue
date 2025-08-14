<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { FormBasurero } from '@/types/residuos';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trash2 } from 'lucide-vue-next';

// ===== COMPOSABLE =====
const { ROUTES } = useResiduos();
const { crearBasurero } = useResiduos();

// ===== FORM =====
const form = useForm<FormBasurero>({
    ubicacion: '',
    descripcion: '',
    estado: true,
});

// ===== MÉTODOS =====

async function handleSubmit() {
    try {
        form.clearErrors();
        form.processing = true;
        await crearBasurero(
            {
                ubicacion: form.ubicacion,
                descripcion: form.descripcion,
                estado: form.estado,
            },
            {
                onSuccess: () => {
                    form.reset();
                },
                onError: (errors: any) => {
                    form.setError(errors);
                },
                onFinish: () => {
                    form.processing = false;
                },
            },
        );
    } catch (error) {
        console.error('Error al crear basurero:', error);
        form.processing = false;
    }
}

function handleCancel() {
    form.reset();
}
</script>

<template>
    <Head title="Crear Basurero" />

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
                            <h1 class="text-3xl font-bold">Crear Nuevo Basurero</h1>
                            <p class="text-muted-foreground">Agregar un nuevo punto de recolección de residuos</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- ===== FORMULARIO ===== -->
            <div class="max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>Información del Basurero</CardTitle>
                        <CardDescription> Completa la información para crear un nuevo punto de recolección </CardDescription>
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

                            <!-- Botones -->
                            <div class="flex items-center gap-4 pt-4">
                                <Button type="submit" :disabled="form.processing">
                                    <Save class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Creando...' : 'Crear Basurero' }}
                                </Button>
                                <Button type="button" variant="outline" @click="handleCancel">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Limpiar
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
