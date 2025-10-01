import '../css/app.css';
import '../css/student-theme.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { ChevronDown, ChevronUp } from 'lucide-vue-next'

const appName = import.meta.env.VITE_APP_NAME || 'Sistema de Puntos Darío Montaño';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Manejador global de errores
        app.config.errorHandler = (err, instance, info) => {
            // Ignorar errores específicos de desmontaje
            if (
                err instanceof TypeError &&
                (err.message.includes('Cannot read properties of null') ||
                    err.message.includes("reading 'type'") ||
                    err.message.includes("reading 'el'"))
            ) {
                console.debug('Ignorando error de desmontaje:', err.message);
                return;
            }

            // Registrar otros errores
            console.error('Error en la aplicación:', err);
            console.error('Componente:', instance);
            console.error('Info:', info);
        };

        app.use(plugin).use(ZiggyVue).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
