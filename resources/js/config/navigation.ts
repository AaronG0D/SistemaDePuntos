import type { NavGroup } from '@/types';
import { Book, GraduationCap, LineChart } from 'lucide-vue-next';

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
            {
                title: 'Mis Cursos',
                href: '/docente/cursos',
                icon: Book,
            },
        ],
    },
    {
        title: 'Reportes',
        icon: LineChart,
        items: [
            {
                title: 'Puntos por Curso',
                href: '/docente/reportes/curso',
                icon: LineChart,
            },
            {
                title: 'Puntos por Materia',
                href: '/docente/reportes/materia',
                icon: LineChart,
            },
        ],
    },
];
