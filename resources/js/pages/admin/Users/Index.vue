<script setup lang="ts">
import ConfirmDelete from '@/components/ConfirmDelete.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import UserQrCode from '@/components/UserQrCode.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, Users } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
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
    usuariosInactivos?: User[];
    filters: {
        search: string;
        role: string;
    };
}>();

const page = usePage();

// Confirm dialog state
const confirmOpen = ref(false);
const userToDelete = ref<number | null>(null);
const tabActivo = ref<'activos' | 'inactivos'>('activos');

function promptDestroyUser(id: number) {
    userToDelete.value = id;
    confirmOpen.value = true;
}

function confirmDestroy() {
    if (userToDelete.value) {
        router.delete(route('users.destroy', userToDelete.value), {
            onSuccess: () => {
                toast.success('Usuario desactivado correctamente');
                confirmOpen.value = false;
                userToDelete.value = null;
            },
            onError: (errors) => {
                toast.error('Error al desactivar el usuario');
                console.error('Delete errors:', errors);
            },
        });
    }
}

function restoreUser(id: number) {
    router.post(
        route('users.restore', id),
        {},
        {
            onSuccess: () => {
                toast.success('Usuario reactivado correctamente');
            },
            onError: (errors) => {
                toast.error('Error al reactivar el usuario');
                console.error('Restore errors:', errors);
            },
        },
    );
}

// Handle flash messages from server
onMounted(() => {
    const flashSuccess = page.props.flash?.success;
    const flashError = page.props.flash?.error;

    if (flashSuccess) {
        toast.success(flashSuccess);
    }

    if (flashError) {
        toast.error(flashError);
    }
});

// Filters
const filters = ref({
    search: props.filters?.search || '',
    role: props.filters?.role || '',
});

const filteredUsers = computed(() => {
    if (tabActivo.value === 'inactivos') {
        return props.usuariosInactivos || [];
    }
    return props.users.data;
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
                    <h1 class="flex items-center gap-3 text-2xl font-semibold">
                        <Users class="h-7 w-7 text-purple-600" />
                        Usuarios
                    </h1>
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
                    <Input
                        v-model="filters.search"
                        placeholder="Buscar por nombre o email..."
                        class="bg-background border-input text-foreground placeholder:text-muted-foreground focus:border-ring focus:ring-ring max-w-sm"
                    />
                </div>

                <Select v-model="tabActivo">
                    <SelectTrigger class="w-[150px]">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="activos">Activos</SelectItem>
                        <SelectItem value="inactivos">Inactivos</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="filters.role">
                    <SelectTrigger class="bg-background border-input text-foreground w-[180px]">
                        <SelectValue :placeholder="filters.role || 'Filtrar por rol'" />
                    </SelectTrigger>
                    <SelectContent class="bg-popover border-border">
                        <SelectItem
                            v-for="role in roles"
                            :key="role.value"
                            :value="role.value"
                            class="text-popover-foreground hover:bg-accent hover:text-accent-foreground"
                        >
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
                            <TableCaption v-if="!filteredUsers.length">No hay usuarios</TableCaption>
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
                                <TableRow v-for="u in filteredUsers" :key="u.id">
                                    <TableCell>{{ u.nombres }}</TableCell>
                                    <TableCell>{{ u.primerApellido }}</TableCell>
                                    <TableCell>{{ u.segundoApellido || '-' }}</TableCell>
                                    <TableCell>{{ u.email }}</TableCell>
                                    <TableCell class="capitalize">{{ u.rol }}</TableCell>
                                    <TableCell class="font-mono text-xs">{{ u.qr_codigo || '-' }}</TableCell>
                                    <TableCell>
                                        <div v-if="tabActivo === 'inactivos'" class="flex items-center justify-end gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="border-green-200 text-green-600 hover:bg-green-50 dark:border-green-800 dark:text-green-400 dark:hover:bg-green-950"
                                                @click="restoreUser(u.id)"
                                            >
                                                Reactivar
                                            </Button>
                                        </div>
                                        <div v-else class="flex items-center justify-end gap-2">
                                            <UserQrCode v-if="u.qr_codigo" :user="u" />
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="route('users.show', u.id)">Ver</Link>
                                            </Button>
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="route('users.edit', u.id)">Editar</Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="promptDestroyUser(u.id)">Desactivar</Button>
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
        title="Confirmar desactivación"
        description="¿Desactivar este usuario? Podrá reactivarlo más tarde."
        @update:open="(v) => (confirmOpen = v)"
        @confirm="confirmDestroy"
        @cancel="confirmOpen = false"
    />
</template>
