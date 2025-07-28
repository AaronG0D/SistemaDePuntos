<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Basurero, Deposito, FormDeposito, TipoBasura } from '@/types/residuos';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trash2 } from 'lucide-vue-next';

// ===== PROPS =====
const props = defineProps<{
    deposito: Deposito & {
        user: {
            id: number;
            nombres: string;
            primerApellido: string;
        };
        basurero: Basurero;
        tipoBasura: TipoBasura;
    };
    basureros: Basurero[];
    tiposBasura: TipoBasura[];
}>();

// ===== COMPOSABLE =====
const { ROUTES, actualizarDeposito } = useResiduos();

// ===== FORM =====
const form = useForm<FormDeposito>({
    idUser: props.deposito.idUser,
    idBasurero: props.deposito.idBasurero,
    idTipoBasura: props.deposito.idTipoBasura,
    fechaHora: props.deposito.fechaHora,
});

// ===== MÉTODOS =====
async function handleSubmit() {
    try {
        form.clearErrors();
        form.processing = true;
        await actualizarDeposito(
            props.deposito.idDeposito,
            {
                idUser: form.idUser,
                idBasurero: form.idBasurero,
                idTipoBasura: form.idTipoBasura,
                fechaHora: form.fechaHora,
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
        console.error('Error al actualizar depósito:', error);
        form.processing = false;
    }
}

function handleCancel() {
    form.reset();
}
</script>

<template>
    <Head :title="`Editar Depósito - ${deposito.idDeposito}`" />

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
                            <h1 class="text-3xl font-bold">Editar Depósito</h1>
                            <p class="text-muted-foreground">Modificar información del depósito</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="ROUTES.depositos.show(deposito.idDeposito)"> Ver Detalles </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <!-- ===== FORMULARIO ===== -->
            <div class="max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>Información del Depósito</CardTitle>
                        <CardDescription> Modifica la información del depósito #{{ deposito.idDeposito }} </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="handleSubmit" class="space-y-6">
                            <!-- Usuario -->
                            <div class="space-y-2">
                                <Label for="idUser">Usuario *</Label>
                                <Input
                                    id="idUser"
                                    v-model.number="form.idUser"
                                    type="number"
                                    placeholder="ID del usuario"
                                    :class="{ 'border-red-500': form.errors.idUser }"
                                />
                                <p v-if="form.errors.idUser" class="text-sm text-red-500">
                                    {{ form.errors.idUser }}
                                </p>
                            </div>

                            <!-- Basurero -->
                            <div class="space-y-2">
                                <Label for="idBasurero">Basurero *</Label>
                                <Select v-model="form.idBasurero">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.idBasurero }">
                                        <SelectValue placeholder="Seleccionar basurero" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="basurero in basureros" :key="basurero.idBasurero" :value="basurero.idBasurero">
                                            {{ basurero.ubicacion }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.idBasurero" class="text-sm text-red-500">
                                    {{ form.errors.idBasurero }}
                                </p>
                            </div>

                            <!-- Tipo de Basura -->
                            <div class="space-y-2">
                                <Label for="idTipoBasura">Tipo de Basura *</Label>
                                <Select v-model="form.idTipoBasura">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.idTipoBasura }">
                                        <SelectValue placeholder="Seleccionar tipo de basura" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="tipo in tiposBasura" :key="tipo.idTipoBasura" :value="tipo.idTipoBasura">
                                            {{ tipo.nombre }} ({{ tipo.puntos }} puntos)
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.idTipoBasura" class="text-sm text-red-500">
                                    {{ form.errors.idTipoBasura }}
                                </p>
                            </div>

                            <!-- Fecha y Hora -->
                            <div class="space-y-2">
                                <Label for="fechaHora">Fecha y Hora *</Label>
                                <Input
                                    id="fechaHora"
                                    v-model="form.fechaHora"
                                    type="datetime-local"
                                    :class="{ 'border-red-500': form.errors.fechaHora }"
                                />
                                <p v-if="form.errors.fechaHora" class="text-sm text-red-500">
                                    {{ form.errors.fechaHora }}
                                </p>
                            </div>

                            <!-- Información actual -->
                            <div class="bg-muted rounded-lg p-4">
                                <h4 class="mb-2 font-medium">Información Actual</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-muted-foreground">Usuario:</span>
                                        <span class="ml-2 font-medium">{{ deposito.user?.nombres }} {{ deposito.user?.primerApellido }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Basurero:</span>
                                        <span class="ml-2 font-medium">{{ deposito.basurero?.ubicacion }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Tipo de Basura:</span>
                                        <span class="ml-2 font-medium">{{ deposito.tipoBasura?.nombre }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Puntos:</span>
                                        <span class="ml-2 font-medium">{{ deposito.tipoBasura?.puntos }}</span>
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
                                    <Link :href="ROUTES.depositos.index"> Cancelar </Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
