// ===== TIPOS PARA GESTIÓN DE RESIDUOS =====

// Tipos básicos de entidades
export interface Basurero {
    idBasurero: number;
    ubicacion: string;
    descripcion?: string;
    estado: number;
    created_at: string;
    updated_at: string;
    depositos_count?: number;
    depositos?: Deposito[];
}

export interface TipoBasura {
    idTipoBasura: number;
    nombre: string;
    descripcion?: string;
    puntos: number;
    created_at: string;
    updated_at: string;
    depositos_count?: number;
    depositos?: Deposito[];
}

export interface Deposito {
    tipobasura: any;
    tipo_Basura: any;
    idDeposito: number;
    idUser: number;
    idBasurero: number;
    idTipoBasura: number;
    fechaHora: string;
    created_at: string;
    updated_at: string;
    user?: User;
    basurero?: Basurero;
    // Laravel may serialize relations as snake_case in JSON
    tipoBasura?: TipoBasura;
    tipo_basura?: TipoBasura;
    // Appended attribute from backend for convenience
    puntos_generados?: number;
}

// Tipos de paginación
export interface PaginacionBasureros {
    total: number;
    per_page: number;
    data: Basurero[];
    last_page: number;
    current_page: number;
}

export interface PaginacionTiposBasura {
    total: number;
    per_page: number;
    data: TipoBasura[];
    last_page: number;
    current_page: number;
}

export interface PaginacionDepositos {
    total: number;
    per_page: number;
    data: Deposito[];
    last_page: number;
    current_page: number;
}

// Tipos para formularios
export interface FormBasurero {
    ubicacion: string;
    descripcion?: string;
    estado: boolean;
    [key: string]: any;
}

export interface FormTipoBasura {
    nombre: string;
    descripcion?: string;
    puntos: number;
    [key: string]: any;
}

export interface FormDeposito {
    idUser: number;
    idBasurero: number;
    idTipoBasura: number;
    fechaHora?: string;
    [key: string]: any;
}

// Tipos para filtros
export interface FiltrosDepositos {
    usuario?: string;
    basurero?: number;
    tipo_basura?: number;
    fecha?: string;
    [key: string]: any;
}

// Tipos para estadísticas
export interface EstadisticasResiduos {
    total_depositos: number;
    depositos_hoy: number;
    depositos_semana: number;
    depositos_mes: number;
    total_puntos: number;
    usuarios_activos: number;
}

export interface TopUsuario {
    idUser: number;
    total_puntos: number;
    user?: User;
}

export interface TopTipoBasura {
    idTipoBasura: number;
    total_depositos: number;
    tipoBasura?: TipoBasura;
}

// Props para componentes
export interface AdminResiduosProps {
    basureros?: PaginacionBasureros;
    tiposBasura?: PaginacionTiposBasura;
    depositos?: PaginacionDepositos;
    estadisticas?: EstadisticasResiduos;
    topUsuarios?: TopUsuario[];
    topTiposBasura?: TopTipoBasura[];
    filters?: FiltrosDepositos;
}

// Tipos para acciones
export interface ResiduosActions {
    eliminarBasurero: (id: number) => void;
    toggleEstadoBasurero: (id: number) => void;
    eliminarTipoBasura: (id: number) => void;
    eliminarDeposito: (id: number) => void;
}
