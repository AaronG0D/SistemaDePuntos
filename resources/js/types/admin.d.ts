// ===== TIPOS PARA ADMINISTRACIÓN =====

// Tipos básicos de entidades
export interface Materia {
    idMateria: number;
    nombre: string;
}

export interface Curso {
    idCurso: number;
    nombre: string;
}

export interface Paralelo {
    idParalelo: number;
    nombre: string;
}

// Tipos de relaciones
export interface DocenteMateriaCurso {
    idMateria: number;
    idCursoParalelo: number;
    materia: {
        idMateria: number;
        nombre: string;
    };
    curso_paralelo: {
        idCursoParalelo: number;
        curso: {
            idCurso: number;
            nombre: string;
        };
        paralelo: {
            idParalelo: number;
            nombre: string;
        };
    };
}

// Tipos principales
export interface Docente {
    idDocente: number;
    user: {
        qr_codigo: any;
        id: number;
        nombres: string;
        primerApellido: string;
        segundoApellido?: string;
        email: string;
    };
    docente_materia_cursos: DocenteMateriaCurso[];
}

// Tipos de paginación
export interface PaginacionDocentes {
    total: number;
    per_page: number;
    data: Docente[];
    last_page: number;
    current_page: number;
}

// Props para componentes de administración
export interface AdminDocentesProps {
    docentes: PaginacionDocentes;
    materias: Materia[];
    cursos: Curso[];
    paralelos: Paralelo[];
}

// Tipos para resultados de computed properties
export interface CursoParaleloUnico {
    idCursoParalelo: number;
    curso: {
        idCurso: number;
        nombre: string;
    };
    paralelo: {
        idParalelo: number;
        nombre: string;
    };
}

// Tipos para estadísticas
export interface DocenteStats {
    materiasAsignadas: number;
    cursosAsignados: number;
    asignacionesTotales: number;
}

// Tipos para formularios y acciones
export interface DocenteActions {
    eliminar: (id: number) => void;
    editar: (id: number) => void;
}
