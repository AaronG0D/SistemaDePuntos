import type { FormBasurero, FormDeposito, FormTipoBasura } from '@/types/residuos';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

// ===== CONSTANTES =====
const TOAST_CONFIG = {
    position: 'top-center' as const,
    duration: 3000,
};

const ROUTES = {
    basureros: {
        index: route('admin.basureros.index'),
        create: route('admin.basureros.create'),
        show: (id: number) => route('admin.basureros.show', { basurero: id }),
        edit: (id: number) => route('admin.basureros.edit', { basurero: id }),
        store: route('admin.basureros.store'),
        update: (id: number) => route('admin.basureros.update', { basurero: id }),
        destroy: (id: number) => route('admin.basureros.destroy', { basurero: id }),
        toggleEstado: (id: number) => route('admin.basureros.toggle-estado', { basurero: id }),
    },
    tiposBasura: {
        index: route('admin.tipos-basura.index'),
        create: route('admin.tipos-basura.create'),
        show: (id: number) => route('admin.tipos-basura.show', { tipoBasura: id }),
        edit: (id: number) => route('admin.tipos-basura.edit', { tipoBasura: id }),
        store: route('admin.tipos-basura.store'),
        update: (id: number) => route('admin.tipos-basura.update', { tipoBasura: id }),
        destroy: (id: number) => route('admin.tipos-basura.destroy', { tipoBasura: id }),
    },
    depositos: {
        index: route('admin.depositos.index'),
        create: route('admin.depositos.create'),
        show: (id: number) => route('admin.depositos.show', { deposito: id }),
        edit: (id: number) => route('admin.depositos.edit', { deposito: id }),
        store: route('admin.depositos.store'),
        update: (id: number) => route('admin.depositos.update', { deposito: id }),
        destroy: (id: number) => route('admin.depositos.destroy', { deposito: id }),
        estadisticas: route('admin.depositos.estadisticas'),
    },
} as const;

// ===== COMPOSABLE =====
export function useResiduos() {
    // ===== MÉTODOS PARA BASUREROS =====
    function crearBasurero(data: FormBasurero, options: any = {}) {
        router.post(ROUTES.basureros.store, data, {
            onSuccess: () => {
                toast('Basurero creado', {
                    description: 'El basurero ha sido creado exitosamente',
                    ...TOAST_CONFIG,
                });
                options.onSuccess && options.onSuccess();
            },
            onError: (errors: any) => {
                toast('Error al crear', {
                    description: 'No se pudo crear el basurero',
                    ...TOAST_CONFIG,
                });
                options.onError && options.onError(errors);
            },
            onFinish: () => {
                options.onFinish && options.onFinish();
            },
        });
    }

    function actualizarBasurero(id: number, data: FormBasurero, options: any = {}) {
        router.put(ROUTES.basureros.update(id), data, {
            onSuccess: () => {
                toast('Basurero actualizado', {
                    description: 'El basurero ha sido actualizado exitosamente',
                    ...TOAST_CONFIG,
                });
                options.onSuccess && options.onSuccess();
            },
            onError: (errors: any) => {
                toast('Error al actualizar', {
                    description: 'No se pudo actualizar el basurero',
                    ...TOAST_CONFIG,
                });
                options.onError && options.onError(errors);
            },
            onFinish: () => {
                options.onFinish && options.onFinish();
            },
        });
    }

    function eliminarBasurero(id: number) {
        router.delete(ROUTES.basureros.destroy(id), {
            onSuccess: () => {
                toast('Basurero eliminado', {
                    description: 'El basurero ha sido eliminado exitosamente',
                    ...TOAST_CONFIG,
                });
            },
            onError: () => {
                toast('Error al eliminar', {
                    description: 'No se pudo eliminar el basurero',
                    ...TOAST_CONFIG,
                });
            },
        });
    }

    function toggleEstadoBasurero(id: number) {
        router.patch(
            ROUTES.basureros.toggleEstado(id),
            {},
            {
                onSuccess: () => {
                    toast('Estado actualizado', {
                        description: 'El estado del basurero ha sido actualizado',
                        ...TOAST_CONFIG,
                    });
                },
                onError: () => {
                    toast('Error al actualizar estado', {
                        description: 'No se pudo actualizar el estado del basurero',
                        ...TOAST_CONFIG,
                    });
                },
            },
        );
    }

    // ===== MÉTODOS PARA TIPOS DE BASURA =====
    async function crearTipoBasura(data: FormTipoBasura, options: any = {}) {
        try {
            const result = await router.post(ROUTES.tiposBasura.store, data, {
                preserveScroll: true,
                onSuccess: () => {
                    toast('Tipo de basura creado', {
                        description: 'El tipo de basura ha sido creado exitosamente',
                        ...TOAST_CONFIG,
                    });
                    options.onSuccess && options.onSuccess();
                },
                onError: (errors: any) => {
                    toast('Error al crear', {
                        description: 'No se pudo crear el tipo de basura',
                        ...TOAST_CONFIG,
                    });
                    options.onError && options.onError(errors);
                },
                onFinish: () => {
                    options.onFinish && options.onFinish();
                },
            });
            return result;
        } catch (error) {
            console.error('Error en crearTipoBasura:', error);
            toast('Error inesperado', {
                description: 'Ocurrió un error inesperado al crear',
                ...TOAST_CONFIG,
            });
            throw error;
        }
    }

    async function actualizarTipoBasura(id: number, data: FormTipoBasura, options: any = {}) {
        try {
            const result = await router.put(ROUTES.tiposBasura.update(id), data, {
                preserveScroll: true,
                onSuccess: () => {
                    toast('Tipo de basura actualizado', {
                        description: 'El tipo de basura ha sido actualizado exitosamente',
                        ...TOAST_CONFIG,
                    });
                    options.onSuccess && options.onSuccess();
                },
                onError: (errors: any) => {
                    toast('Error al actualizar', {
                        description: 'No se pudo actualizar el tipo de basura',
                        ...TOAST_CONFIG,
                    });
                    options.onError && options.onError(errors);
                },
                onFinish: () => {
                    options.onFinish && options.onFinish();
                },
            });
            return result;
        } catch (error) {
            console.error('Error en actualizarTipoBasura:', error);
            toast('Error inesperado', {
                description: 'Ocurrió un error inesperado al actualizar',
                ...TOAST_CONFIG,
            });
            throw error;
        }
    }

    function eliminarTipoBasura(id: number) {
        router.delete(ROUTES.tiposBasura.destroy(id), {
            onSuccess: () => {
                toast('Tipo de basura eliminado', {
                    description: 'El tipo de basura ha sido eliminado exitosamente',
                    ...TOAST_CONFIG,
                });
            },
            onError: () => {
                toast('Error al eliminar', {
                    description: 'No se pudo eliminar el tipo de basura',
                    ...TOAST_CONFIG,
                });
            },
        });
    }

    // ===== MÉTODOS PARA DEPÓSITOS =====
    async function crearDeposito(data: FormDeposito, options: any = {}) {
        try {
            const result = await router.post(ROUTES.depositos.store, data, {
                preserveScroll: true,
                onSuccess: () => {
                    toast('Depósito registrado', {
                        description: 'El depósito ha sido registrado exitosamente',
                        ...TOAST_CONFIG,
                    });
                    options.onSuccess && options.onSuccess();
                },
                onError: (errors: any) => {
                    toast('Error al registrar', {
                        description: 'No se pudo registrar el depósito',
                        ...TOAST_CONFIG,
                    });
                    options.onError && options.onError(errors);
                },
                onFinish: () => {
                    options.onFinish && options.onFinish();
                },
            });
            return result;
        } catch (error) {
            console.error('Error en crearDeposito:', error);
            toast('Error inesperado', {
                description: 'Ocurrió un error inesperado al registrar el depósito',
                ...TOAST_CONFIG,
            });
            throw error;
        }
    }

    async function actualizarDeposito(id: number, data: FormDeposito, options: any = {}) {
        try {
            const result = await router.put(ROUTES.depositos.update(id), data, {
                preserveScroll: true,
                onSuccess: () => {
                    toast('Depósito actualizado', {
                        description: 'El depósito ha sido actualizado exitosamente',
                        ...TOAST_CONFIG,
                    });
                    options.onSuccess && options.onSuccess();
                },
                onError: (errors: any) => {
                    toast('Error al actualizar', {
                        description: 'No se pudo actualizar el depósito',
                        ...TOAST_CONFIG,
                    });
                    options.onError && options.onError(errors);
                },
                onFinish: () => {
                    options.onFinish && options.onFinish();
                },
            });
            return result;
        } catch (error) {
            console.error('Error en actualizarDeposito:', error);
            toast('Error inesperado', {
                description: 'Ocurrió un error inesperado al actualizar el depósito',
                ...TOAST_CONFIG,
            });
            throw error;
        }
    }

    function eliminarDeposito(id: number) {
        router.delete(ROUTES.depositos.destroy(id), {
            onSuccess: () => {
                toast('Depósito eliminado', {
                    description: 'El depósito ha sido eliminado exitosamente',
                    ...TOAST_CONFIG,
                });
            },
            onError: () => {
                toast('Error al eliminar', {
                    description: 'No se pudo eliminar el depósito',
                    ...TOAST_CONFIG,
                });
            },
        });
    }

    // ===== UTILIDADES =====
    function navegarA(route: string) {
        router.visit(route);
    }

    function formatearFecha(fecha: string): string {
        return new Date(fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function formatearPuntos(puntos: number): string {
        return `${puntos.toLocaleString()} pts`;
    }

    return {
        // Constantes
        ROUTES,
        TOAST_CONFIG,

        // Métodos de basureros
        crearBasurero,
        actualizarBasurero,
        eliminarBasurero,
        toggleEstadoBasurero,

        // Métodos de tipos de basura
        crearTipoBasura,
        actualizarTipoBasura,
        eliminarTipoBasura,

        // Métodos de depósitos
        crearDeposito,
        actualizarDeposito,
        eliminarDeposito,

        // Utilidades
        navegarA,
        formatearFecha,
        formatearPuntos,
    };
}
