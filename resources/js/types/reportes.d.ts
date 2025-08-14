export interface DatosGraficos {
    porTipo: {
        labels: string[];
        datasets: Array<{
            label: string;
            data: number[];
            backgroundColor?: string | string[];
            borderColor?: string;
            borderWidth?: number;
        }>;
    };
    porMes: {
        labels: string[];
        datasets: Array<{
            label: string;
            data: number[];
            borderColor?: string;
            backgroundColor?: string;
            borderWidth?: number;
            fill?: boolean;
        }>;
    };
    topUsuarios: {
        labels: string[];
        datasets: Array<{
            label: string;
            data: number[];
            backgroundColor?: string | string[];
            borderColor?: string;
            borderWidth?: number;
        }>;
    };
}

export interface Estadisticas {
    total_depositos: number;
    total_puntos: number;
    total_tipos_residuos: number;
    total_basureros: number;
}

export interface FiltrosReporte {
    tipo_residuo_id?: number | string;
    fecha_inicio?: string;
    fecha_fin?: string;
    periodo?: string;
    basurero_id?: number;
}

export interface TipoResiduo {
    id: number;
    nombre: string;
}

export interface Basurero {
    id: number;
    ubicacion: string;
}
 