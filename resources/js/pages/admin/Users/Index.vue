<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { 
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
  TableCaption,
} from '@/components/ui/table';
import { Toaster, toast } from 'vue-sonner';
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import { ref } from 'vue';
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

interface Pagination<T> {
  data: T[];
  links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
  users: Pagination<User>;
}>();

// Confirm dialog state
const confirmOpen = ref(false);
const userToDelete = ref<number | null>(null);

function promptDestroyUser(id: number) {
  userToDelete.value = id;
  confirmOpen.value = true;
}

function confirmDestroy() {
  if (!userToDelete.value) return;
  const id = userToDelete.value;
  confirmOpen.value = false;
  router.delete(route('users.destroy', id), {
    onSuccess: () =>
      toast('Usuario eliminado', {
        description: 'El usuario ha sido eliminado exitosamente',
        position: 'top-center',
        duration: 3000,
      }),
    onError: () =>
      toast('Error al eliminar', {
        description: 'No se pudo eliminar el usuario',
        position: 'top-center',
        duration: 3000,
      }),
  });
  userToDelete.value = null;
}
</script>

<template>
  <AppLayout>
    <Head title="Usuarios" />
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">Usuarios</h1>
        <Button as-child>
          <Link :href="route('users.create')">Crear Usuario</Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Listado</CardTitle>
          <CardDescription>Usuarios del sistema</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <Table>
              <TableCaption v-if="!props.users.data.length">No hay usuarios</TableCaption>
              <TableHeader>
                <TableRow>
                  <TableHead>Nombres</TableHead>
                  <TableHead>Primer Apellido</TableHead>
                  <TableHead>Segundo Apellido</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Rol</TableHead>
                  <TableHead>QR</TableHead>
                  <TableHead class="text-right">Acciones</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="u in props.users.data" :key="u.id">
                  <TableCell>{{ u.nombres }}</TableCell>
                  <TableCell>{{ u.primerApellido }}</TableCell>
                  <TableCell>{{ u.segundoApellido || '-' }}</TableCell>
                  <TableCell>{{ u.email }}</TableCell>
                  <TableCell class="capitalize">{{ u.rol }}</TableCell>
                  <TableCell class="font-mono text-xs">{{ u.qr_codigo || '-' }}</TableCell>
                  <TableCell>
                    <div class="flex items-center justify-end gap-2">
                      <UserQrCode v-if="u.qr_codigo" :user="u" />
                      <Button variant="ghost" size="sm" as-child>
                        <Link :href="route('users.show', u.id)">Ver</Link>
                      </Button>
                      <Button variant="outline" size="sm" as-child>
                        <Link :href="route('users.edit', u.id)">Editar</Link>
                      </Button>
                      <Button variant="destructive" size="sm" @click="promptDestroyUser(u.id)">Eliminar</Button>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <nav v-if="props.users.links?.length" class="mt-4 flex flex-wrap gap-1">
            <Link
              v-for="(l, i) in props.users.links"
              :key="i"
              :href="l.url || '#'"
              :class="[
                'rounded border px-3 py-1 text-sm',
                l.active ? 'bg-primary text-white border-primary' : 'hover:bg-muted',
                !l.url && 'pointer-events-none opacity-50',
              ]"
              v-html="l.label"
            />
          </nav>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
  <Toaster />
  <ConfirmDelete
    :open="confirmOpen"
    title="Confirmar eliminación"
    description="¿Eliminar este usuario? Esta acción no se puede deshacer."
    @update:open="(v) => (confirmOpen = v)"
    @confirm="confirmDestroy"
    @cancel="confirmOpen = false"
  />
</template>
