<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useResiduos } from '@/composables/useResiduos';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Basurero, FormDeposito, TipoBasura } from '@/types/residuos';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trash2 } from 'lucide-vue-next';

// ===== PROPS =====
const props = defineProps<{
    basureros: Basurero[];
    tiposBasura: TipoBasura[];
}>();

// ===== COMPOSABLE =====
const { ROUTES, crearDeposito } = useResiduos();

// ===== FORM =====
const form = useForm<FormDeposito>({
    idUser: 0,
    idBasurero: 0,
    idTipoBasura: 0,
    fechaHora: new Date().toISOString().slice(0, 16),
});

// ===== MÉTODOS =====
async function handleSubmit() {
    try {
        form.clearErrors();
        form.processing = true;
        await crearDeposito(
            {
                idUser: form.idUser,
                idBasurero: form.idBasurero,
                idTipoBasura: form.idTipoBasura,
                fechaHora: form.fechaHora,
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
        console.error('Error al crear depósito:', error);
        form.processing = false;
    }
}

function handleCancel() {
    form.reset();
}
</script>

<template>
    <Head title="Crear Depósito" />

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
                            <h1 class="text-3xl font-bold">Crear Nuevo Depósito</h1>
                            <p class="text-muted-foreground">Registrar un nuevo depósito de residuos</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- ===== FORMULARIO ===== -->
            <div class="max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>Información del Depósito</CardTitle>
                        <CardDescription> Completa la información para registrar un nuevo depósito </CardDescription>
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
                                <p class="text-muted-foreground text-sm">Ingresa el ID del usuario que realizó el depósito</p>
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
                                <Label for="fechaHora">Fecha y Hora</Label>
                                <Input
                                    id="fechaHora"
                                    v-model="form.fechaHora"
                                    type="datetime-local"
                                    :class="{ 'border-red-500': form.errors.fechaHora }"
                                />
                                <p class="text-muted-foreground text-sm">Si no se especifica, se usará la fecha y hora actual</p>
                                <p v-if="form.errors.fechaHora" class="text-sm text-red-500">
                                    {{ form.errors.fechaHora }}
                                </p>
                            </div>

                            <!-- Información adicional -->
                            <div class="bg-muted rounded-lg p-4">
                                <h4 class="mb-2 font-medium">Información sobre los Depósitos</h4>
                                <ul class="text-muted-foreground space-y-1 text-sm">
                                    <li>• Los depósitos generan puntos según el tipo de residuo</li>
                                    <li>• Los puntos se acumulan en el perfil del usuario</li>
                                    <li>• Solo se pueden registrar depósitos en basureros activos</li>
                                    <li>• Cada depósito debe tener un usuario, basurero y tipo de basura válidos</li>
                                </ul>
                            </div>

                            <!-- Botones -->
                            <div class="flex items-center gap-4 pt-4">
                                <Button type="submit" :disabled="form.processing">
                                    <Save class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Creando...' : 'Crear Depósito' }}
                                </Button>
                                <Button type="button" variant="outline" @click="handleCancel">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Limpiar
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
