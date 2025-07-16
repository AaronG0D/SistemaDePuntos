import type { PageProps } from '@inertiajs/core';
import type { Config } from 'ziggy-js';

// Tipos básicos
export type UserRole = 'administrador' | 'estudiante' | 'docente';

// Ítem básico de navegación
export interface NavItem {
    title: string;
    href: string;
    icon?: any;
}

// Grupo de navegación
export interface NavGroup {
    title: string;
    icon: any;
    items: NavItem[];
}

// Navegación por rol
export interface RoleNavigation {
    admin: NavGroup[];
    student?: NavGroup[];
    teacher?: NavGroup[];
}

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface AdminNavigation {
    academico: MenuSection;
    residuos: MenuSection;
}

export interface RoleBasedNavigation {
    common: NavItem[];
    admin: AdminNavigation;
    teacher: NavItem[];
    student: NavItem[];
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
}

export interface User {
    id: number;
    nombres: string;
    primerApellido: string;
    segundoApellido: string;
    rol: string;
    email: string;
    avatar?: string;
    src: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}
export interface Estudiante {
    idUser: number;
    user: {
        id: number;
        nombres: string;
        primerApellido: string;
        segundoApellido?: string;
        email: string;
        qr_codigo?: string;
        puntaje?: {
            puntajeTotal: number;
        };
    };
    curso_paralelo?: {
        idCursoParalelo: number;
        curso?: {
            idCurso: number;
            nombre: string;
        };
        paralelo?: {
            idParalelo: number;
            nombre: string;
        };
    };
}

export type BreadcrumbItemType = BreadcrumbItem;

// ===== EXPORTACIONES DE TIPOS DE ADMINISTRACIÓN =====
export type { AdminDocentesProps, Curso, Docente, DocenteMateriaCurso, Materia, PaginacionDocentes, Paralelo } from './admin';
