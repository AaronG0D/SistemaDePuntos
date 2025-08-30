import { BookOpen, GraduationCap, LineChart } from 'lucide-vue-next';

export const docenteNavigation = [
    {
        title: 'Dashboard',
        href: '/docente/dashboard',
        icon: GraduationCap,
    },
    {
        title: 'Mis Cursos',
        href: '/docente/cursos',
        icon: BookOpen,
    },
    {
        title: 'Reportes',
        href: '/docente/reportes',
        icon: LineChart,
    },
];
