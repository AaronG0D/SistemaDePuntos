<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import UserQrCode from '@/components/UserQrCode.vue';

type User = {
  id: number;
  nombres: string;
  primerApellido: string;
  segundoApellido?: string | null;
  email: string;
  rol: 'estudiante' | 'docente' | 'administrador';
  qr_codigo?: string | null;
};

const props = defineProps<{ user: User }>();
</script>

<template>
  <AppLayout>
    <Head :title="`Usuario: ${props.user.nombres}`" />
    <div class="container mx-auto py-6">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold">Detalle de Usuario</h1>
        <div class="flex items-center gap-2">
          <UserQrCode v-if="props.user.qr_codigo" :user="props.user" />
          <Button variant="outline" size="sm" as-child>
            <Link :href="route('users.index')"> Volver </Link>
          </Button>
          <Button variant="secondary" size="sm" as-child>
            <Link :href="route('users.edit', props.user.id)"> Editar </Link>
          </Button>
        </div>
      </div>

      <div class="max-w-2xl">
        <Card>
          <CardHeader>
            <CardTitle>{{ props.user.nombres }}</CardTitle>
            <CardDescription>Información del usuario</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <div class="text-muted-foreground">Nombres</div>
              <div class="sm:col-span-2">{{ props.user.nombres }}</div>

              <div class="text-muted-foreground">Primer Apellido</div>
              <div class="sm:col-span-2">{{ props.user.primerApellido }}</div>

              <div class="text-muted-foreground">Segundo Apellido</div>
              <div class="sm:col-span-2">{{ props.user.segundoApellido || '-' }}</div>

              <div class="text-muted-foreground">Email</div>
              <div class="sm:col-span-2">{{ props.user.email }}</div>

              <div class="text-muted-foreground">Rol</div>
              <div class="sm:col-span-2 capitalize">{{ props.user.rol }}</div>

              <div class="text-muted-foreground">QR</div>
              <div class="sm:col-span-2 font-mono text-xs">{{ props.user.qr_codigo || '-' }}</div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
