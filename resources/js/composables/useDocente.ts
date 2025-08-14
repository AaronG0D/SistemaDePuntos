import type { CursoParaleloUnico, Docente, DocenteStats } from '@/types/admin';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { toast } from 'vue-sonner';

// ===== CONSTANTES =====
const TOAST_CONFIG = {
    position: 'top-center' as const,
    duration: 3000,
};

const ROUTES = {
    docentes: '/admin/docentes',
    editar: (id: number) => `/admin/docentes/${id}/edit`,
    eliminar: (id: number) => `/admin/docentes/${id}`,
} as const;

// ===== COMPOSABLE =====
export function useDocente(docente: Docente) {
    // ===== COMPUTED =====
    const materiasUnicas = computed(() => {
        const materias = new Set<number>();
        const materiasUnicas: Array<{ idMateria: number; nombre: string }> = [];

        docente.docente_materia_cursos.forEach((asignacion) => {
            if (!materias.has(asignacion.materia.idMateria)) {
                materias.add(asignacion.materia.idMateria);
                materiasUnicas.push(asignacion.materia);
            }
        });

        return materiasUnicas;
    });

    const cursosUnicos = computed((): CursoParaleloUnico[] => {
        const cursos = new Set<string>();
        const cursosUnicos: CursoParaleloUnico[] = [];

        docente.docente_materia_cursos.forEach((asignacion) => {
            const key = `${asignacion.curso_paralelo.curso.idCurso}-${asignacion.curso_paralelo.paralelo.idParalelo}`;
            if (!cursos.has(key)) {
                cursos.add(key);
                cursosUnicos.push(asignacion.curso_paralelo);
            }
        });

        return cursosUnicos;
    });

    const estadisticas = computed(
        (): DocenteStats => ({
            materiasAsignadas: materiasUnicas.value.length,
            cursosAsignados: cursosUnicos.value.length,
            asignacionesTotales: docente.docente_materia_cursos.length,
        }),
    );

    const nombreCompleto = computed(() => {
        const { nombres, primerApellido, segundoApellido } = docente.user;
        return `${nombres} ${primerApellido}${segundoApellido ? ` ${segundoApellido}` : ''}`;
    });

    // ===== MÉTODOS =====
    function eliminarDocente() {
        if (confirm('¿Estás seguro de que quieres eliminar este docente?')) {
            router.delete(ROUTES.eliminar(docente.idDocente), {
                onSuccess: () => {
                    toast('Docente eliminado', {
                        description: 'El docente ha sido eliminado correctamente',
                        ...TOAST_CONFIG,
                    });
                    router.visit(ROUTES.docentes);
                },
                onError: () => {
                    toast('Error al eliminar', {
                        description: 'No se pudo eliminar el docente',
                        ...TOAST_CONFIG,
                    });
                },
            });
        }
    }

    function editarDocente() {
        router.visit(ROUTES.editar(docente.idDocente));
    }

    // ===== UTILIDADES =====
    function generarKeyAsignacion(asignacion: any): string {
        return `${asignacion.idMateria}-${asignacion.idCursoParalelo}`;
    }

    function generarKeyCursoParalelo(cursoParalelo: CursoParaleloUnico): string {
        return `${cursoParalelo.curso.idCurso}-${cursoParalelo.paralelo.idParalelo}`;
    }

    return {
        // Computed
        materiasUnicas,
        cursosUnicos,
        estadisticas,
        nombreCompleto,

        // Métodos
        eliminarDocente,
        editarDocente,

        // Utilidades
        generarKeyAsignacion,
        generarKeyCursoParalelo,

        // Constantes
        ROUTES,
    };
}
