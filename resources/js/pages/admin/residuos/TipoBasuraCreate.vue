<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { FormTipoBasura } from '@/types/residuos';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trash2 } from 'lucide-vue-next';

// ===== COMPOSABLE =====
const { ROUTES, crearTipoBasura } = useResiduos();

// ===== FORM =====
const form = useForm<FormTipoBasura>({
    nombre: '',
    descripcion: '',
    puntos: 10,
});

// ===== MÉTODOS =====
async function handleSubmit() {
    try {
        form.clearErrors();
        form.processing = true;
        await crearTipoBasura(
            {
                nombre: form.nombre,
                descripcion: form.descripcion,
                puntos: form.puntos,
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
        console.error('Error al crear tipo de basura:', error);
        form.processing = false;
    }
}

function handleCancel() {
    form.reset();
}
</script>

<template>
    <Head title="Crear Tipo de Basura" />

    <AppLayout>
        <div class="container mx-auto py-6">
            <!-- ===== HEADER ===== -->
            <header class="mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="ROUTES.tiposBasura.index">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 class="text-3xl font-bold">Crear Nuevo Tipo de Basura</h1>
                            <p class="text-muted-foreground">Agregar un nuevo tipo de residuo con puntos</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- ===== FORMULARIO ===== -->
            <div class="max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>Información del Tipo de Basura</CardTitle>
                        <CardDescription> Completa la información para crear un nuevo tipo de residuo </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="handleSubmit" class="space-y-6">
                            <!-- Nombre -->
                            <div class="space-y-2">
                                <Label for="nombre">Nombre *</Label>
                                <Input
                                    id="nombre"
                                    v-model="form.nombre"
                                    placeholder="Ej: Plástico, Papel, Vidrio, etc."
                                    :class="{ 'border-red-500': form.errors.nombre }"
                                />
                                <p v-if="form.errors.nombre" class="text-sm text-red-500">
                                    {{ form.errors.nombre }}
                                </p>
                            </div>

                            <!-- Descripción -->
                            <div class="space-y-2">
                                <Label for="descripcion">Descripción</Label>
                                <Textarea
                                    id="descripcion"
                                    v-model="form.descripcion"
                                    placeholder="Descripción del tipo de residuo..."
                                    rows="3"
                                    :class="{ 'border-red-500': form.errors.descripcion }"
                                />
                                <p v-if="form.errors.descripcion" class="text-sm text-red-500">
                                    {{ form.errors.descripcion }}
                                </p>
                            </div>

                            <!-- Puntos -->
                            <div class="space-y-2">
                                <Label for="puntos">Puntos *</Label>
                                <Input
                                    id="puntos"
                                    v-model.number="form.puntos"
                                    type="number"
                                    min="1"
                                    max="1000"
                                    placeholder="10"
                                    :class="{ 'border-red-500': form.errors.puntos }"
                                />
                                <p class="text-muted-foreground text-sm">Los puntos que recibirá el usuario por depositar este tipo de residuo</p>
                                <p v-if="form.errors.puntos" class="text-sm text-red-500">
                                    {{ form.errors.puntos }}
                                </p>
                            </div>

                            <!-- Información adicional -->
                            <div class="bg-muted rounded-lg p-4">
                                <h4 class="mb-2 font-medium">Información sobre los Puntos</h4>
                                <ul class="text-muted-foreground space-y-1 text-sm">
                                    <li>• Los puntos se acumulan en el perfil del usuario</li>
                                    <li>• Pueden ser canjeados por beneficios o reconocimientos</li>
                                    <li>• Diferentes tipos de residuo pueden tener diferentes valores</li>
                                    <li>• Se recomienda asignar más puntos a residuos más difíciles de reciclar</li>
                                </ul>
                            </div>

                            <!-- Botones -->
                            <div class="flex items-center gap-4 pt-4">
                                <Button type="submit" :disabled="form.processing">
                                    <Save class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Creando...' : 'Crear Tipo de Basura' }}
                                </Button>
                                <Button type="button" variant="outline" @click="handleCancel">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Limpiar
                                </Button>
                                <Button type="button" variant="ghost" as-child>
                                    <Link :href="ROUTES.tiposBasura.index"> Cancelar </Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
