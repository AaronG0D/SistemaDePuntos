<script setup lang="ts">
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import UserQrCode from '@/components/UserQrCode.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Toaster, toast } from 'vue-sonner';
import { route } from 'ziggy-js';

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
    filters: {
        search: string;
        role: string;
    };
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

// Filters
const filters = ref({
    search: props.filters?.search || '',
    role: props.filters?.role || '',
});

watch(
    filters,
    (value) => {
        router.get(route('users.index'), value, {
            preserveState: true,
            preserveScroll: true,
        });
    },
    { deep: true },
);

const roles = [
    { value: '', label: 'Todos los roles' },
    { value: 'administrador', label: 'Administrador' },
    { value: 'docente', label: 'Docente' },
    { value: 'estudiante', label: 'Estudiante' },
];
</script>

<template>
    <AppLayout>
        <Head title="Usuarios" />

        <div class="container mx-auto py-6">
            <!-- Header modificado -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Usuarios</h1>
                    <p class="text-muted-foreground">Gestiona los usuarios del sistema</p>
                </div>
                <Button asChild>
                    <Link :href="route('users.create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Usuario
                    </Link>
                </Button>
            </div>

            <!-- Filtros -->
            <div class="mb-6 flex items-center gap-4">
                <div class="flex-1">
                    <Input v-model="filters.search" placeholder="Buscar por nombre o email..." class="max-w-sm" />
                </div>
                <Select v-model="filters.role">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue :placeholder="filters.role || 'Filtrar por rol'" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="role in roles" :key="role.value" :value="role.value">
                            {{ role.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
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
                                l.active ? 'bg-primary border-primary text-white' : 'hover:bg-muted',
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
