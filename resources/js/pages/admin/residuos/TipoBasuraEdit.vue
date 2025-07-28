<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { FormTipoBasura, TipoBasura } from '@/types/residuos';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trash2 } from 'lucide-vue-next';

// ===== PROPS =====
const props = defineProps<{
    tipoBasura: TipoBasura;
}>();

// ===== COMPOSABLE =====
const { ROUTES, actualizarTipoBasura } = useResiduos();

// ===== FORM =====
const form = useForm<FormTipoBasura>({
    nombre: props.tipoBasura.nombre,
    descripcion: props.tipoBasura.descripcion || '',
    puntos: props.tipoBasura.puntos,
});

// ===== MÉTODOS =====
async function handleSubmit() {
    try {
        form.clearErrors();
        form.processing = true;
        await actualizarTipoBasura(
            props.tipoBasura.idTipoBasura,
            {
                nombre: form.nombre,
                descripcion: form.descripcion,
                puntos: form.puntos,
            },
            {
                onSuccess: () => {
                    // El toast ya se muestra en el composable
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
        console.error('Error al enviar el formulario:', error);
        form.processing = false;
    }
}

function handleCancel() {
    form.reset();
}
</script>

<template>
    <Head :title="`Editar Tipo de Basura - ${tipoBasura.nombre}`" />

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
                            <h1 class="text-3xl font-bold">Editar Tipo de Basura</h1>
                            <p class="text-muted-foreground">Modificar información del tipo de residuo</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="ROUTES.tiposBasura.show(tipoBasura.idTipoBasura)"> Ver Detalles </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <!-- ===== FORMULARIO ===== -->
            <div class="max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>Información del Tipo de Basura</CardTitle>
                        <CardDescription> Modifica la información del tipo: {{ tipoBasura.nombre }} </CardDescription>
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
                                <h4 class="mb-2 font-medium">Información del Tipo de Basura</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-muted-foreground">ID:</span>
                                        <span class="ml-2 font-mono">{{ tipoBasura.idTipoBasura }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Puntos actuales:</span>
                                        <span class="ml-2 font-medium">{{ tipoBasura.puntos }} pts</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Depósitos registrados:</span>
                                        <span class="ml-2 font-medium">{{ tipoBasura.depositos_count || 0 }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Creado:</span>
                                        <span class="ml-2">{{ new Date(tipoBasura.created_at).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Advertencia sobre cambios -->
                            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                                <h4 class="mb-2 font-medium text-yellow-800">⚠️ Advertencia</h4>
                                <p class="text-sm text-yellow-700">
                                    Cambiar los puntos de un tipo de basura afectará a todos los depósitos futuros. Los depósitos existentes
                                    mantendrán los puntos originales.
                                </p>
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
