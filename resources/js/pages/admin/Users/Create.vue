<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Toaster, toast } from 'vue-sonner';

const form = useForm({
  nombres: '',
  primerApellido: '',
  segundoApellido: '',
  email: '',
  rol: 'estudiante' as 'estudiante' | 'docente' | 'administrador',
  password: '',
});

function submit() {
  form.post(route('users.store'), {
    onSuccess: () => {
      toast('Usuario creado', {
        description: 'El usuario ha sido creado exitosamente',
        position: 'top-center',
        duration: 3000,
      });
      form.reset('nombres', 'primerApellido', 'segundoApellido', 'email', 'password');
    },
  });
}
</script>

<template>
  <AppLayout>
    <Head title="Crear Usuario" />
    <div class="container mx-auto py-6">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold">Crear Usuario</h1>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('users.index')"> Volver </Link>
        </Button>
      </div>

      <div class="max-w-2xl">
        <Card>
          <CardHeader>
            <CardTitle>Datos del Usuario</CardTitle>
            <CardDescription>Completa los campos para registrar un nuevo usuario</CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit" class="space-y-6">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                  <Label for="nombres">Nombres</Label>
                  <Input id="nombres" v-model="form.nombres" :class="{ 'border-red-500': form.errors.nombres }" />
                  <p v-if="form.errors.nombres" class="text-sm text-red-600">{{ form.errors.nombres }}</p>
                </div>
                <div class="space-y-2">
                  <Label for="primerApellido">Primer Apellido</Label>
                  <Input id="primerApellido" v-model="form.primerApellido" :class="{ 'border-red-500': form.errors.primerApellido }" />
                  <p v-if="form.errors.primerApellido" class="text-sm text-red-600">{{ form.errors.primerApellido }}</p>
                </div>
                <div class="space-y-2">
                  <Label for="segundoApellido">Segundo Apellido</Label>
                  <Input id="segundoApellido" v-model="form.segundoApellido" :class="{ 'border-red-500': form.errors.segundoApellido }" />
                  <p v-if="form.errors.segundoApellido" class="text-sm text-red-600">{{ form.errors.segundoApellido }}</p>
                </div>
                <div class="space-y-2">
                  <Label for="email">Email</Label>
                  <Input id="email" type="email" v-model="form.email" :class="{ 'border-red-500': form.errors.email }" />
                  <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                  <Label for="rol">Rol</Label>
                  <Select v-model="form.rol">
                    <SelectTrigger id="rol">
                      <SelectValue placeholder="Seleccionar rol" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="estudiante">Estudiante</SelectItem>
                      <SelectItem value="docente">Docente</SelectItem>
                      <SelectItem value="administrador">Administrador</SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="form.errors.rol" class="text-sm text-red-600">{{ form.errors.rol }}</p>
                </div>
                <div class="space-y-2">
                  <Label for="password">Contraseña</Label>
                  <Input id="password" type="password" v-model="form.password" :class="{ 'border-red-500': form.errors.password }" />
                  <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
                </div>
              </div>

              <div class="pt-2">
                <Button type="submit" :disabled="form.processing"> {{ form.processing ? 'Guardando...' : 'Guardar' }} </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
  <Toaster />
</template>
