import type { NavGroup } from '@/types';
import { BarChart3, FileText, GraduationCap, PlusCircle } from 'lucide-vue-next';

export const docenteNavigation: NavGroup[] = [
    {
        title: 'Principal',
        icon: GraduationCap,
        items: [
            {
                title: 'Dashboard',
                href: '/docente/dashboard',
                icon: GraduationCap,
            },
        ],
    },
    {
        title: 'Gestión de Puntos',
        icon: PlusCircle,
        items: [
            {
                title: 'Asignar Puntos',
                href: '/docente/asignacion-puntos',
                icon: PlusCircle,
            },
        ],
    },
    {
        title: 'Reportes y Análisis',
        icon: BarChart3,
        items: [
            {
                title: 'Reportes por Materia',
                href: '/docente/reportes-materia',
                icon: FileText,
            },
        ],
    },
];
