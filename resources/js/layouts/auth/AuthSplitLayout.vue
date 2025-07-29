<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const name = page.props.name;

const quotes = [
    {
        message: 'Reciclar no es una opción, es una responsabilidad con nuestro planeta y las futuras generaciones.',
        author: 'Comunidad Darío Montaño',
    },
    {
        message: 'Cada pequeña acción cuenta. Tu compromiso con el reciclaje marca la diferencia en nuestro medio ambiente.',
        author: 'Comunidad Darío Montaño',
    },
    {
        message: 'El mejor residuo es el que no se genera. Reduce, Reutiliza, Recicla.',
        author: 'Comunidad Darío Montaño',
    },
    {
        message: 'Ser parte del cambio está en tus manos. Cada residuo correctamente clasificado es un paso hacia un futuro más limpio.',
        author: 'Equipo Ambiental',
    },
];

// Seleccionar un mensaje aleatorio
const randomQuote = computed(() => {
    const randomIndex = Math.floor(Math.random() * quotes.length);
    return quotes[randomIndex];
});

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <div class="bg-muted relative hidden h-full flex-col p-10 text-white lg:flex dark:border-r">
            <div class="absolute inset-0 bg-zinc-900" />
            <Link :href="route('home')" class="relative z-20 flex items-center text-lg font-medium">
                <div class="mb-1 flex h-45 w-45 items-center justify-center rounded-md">
                    <AppLogoIcon class="size-45 fill-current text-[var(--foreground)] dark:text-white" />
                </div>
                <span class="sr-only">{{ title }}</span>
            </Link>
            
            <div class="relative z-20 mt-auto">
                <blockquote class="space-y-2">
                    <p class="text-lg">&ldquo;{{ randomQuote.message }}&rdquo;</p>
                    <footer class="text-sm text-neutral-300">{{ randomQuote.author }}</footer>
                </blockquote>
            </div>
        </div>
        <div class="lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">{{ title }}</h1>
                    <p class="text-muted-foreground text-sm" v-if="description">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
