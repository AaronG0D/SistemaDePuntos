// Interfaz base para usuarios del sistema
export interface BaseUser {
    id: number;
    nombres: string;
    primerApellido: string;
    segundoApellido?: string | null;
    email: string;
}

// Interfaz para el QR code
export interface QrCodeData extends BaseUser {
    qr_codigo?: string | { id: string } | null;
}

// Interfaz para el componente UserQrCode
export interface UserQrProps {
    user: QrCodeData;
    showButton?: boolean;
}

// Interfaz completa de usuario
export interface User extends QrCodeData {
    rol: string;
    key: string;
}
