<template>
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 p-8 text-white shadow-2xl">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg class="h-full w-full" fill="currentColor" viewBox="0 0 100 100">
                <defs>
                    <pattern id="leaf-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M10 2C6 2 2 6 2 10s4 8 8 8 8-4 8-8-4-8-8-8zm0 2c3.3 0 6 2.7 6 6s-2.7 6-6 6-6-2.7-6-6 2.7-6 6-6z"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#leaf-pattern)"/>
            </svg>
        </div>

        <!-- Content -->
        <div class="relative">
            <div class="flex flex-col items-center text-center lg:flex-row lg:text-left">
                <!-- Logo and Welcome -->
                <div class="flex-1 space-y-4">
                    <div class="flex items-center justify-center lg:justify-start">
                        <div class="rounded-full bg-white/20 p-3 backdrop-blur-sm">
                            <img 
                                src="/img/LogoDario.png" 
                                alt="Logo Darío Montaño" 
                                class="h-12 w-12 rounded-full"
                            >
                        </div>
                        <div class="ml-4">
                            <h1 class="text-2xl font-bold sm:text-3xl">
                                ¡Hola, {{ firstName }}!
                            </h1>
                            <p class="text-green-100">
                                {{ course || 'Sin curso' }} - {{ parallel || 'Sin paralelo' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <p class="text-lg text-green-100">
                            {{ welcomeMessage }}
                        </p>
                        <div class="flex flex-wrap justify-center gap-2 lg:justify-start">
                            <span class="rounded-full px-3 py-1 text-sm backdrop-blur-sm flex items-center gap-1 bg-green-500/20 text-green-100">
                                <Leaf class="h-4 w-4 text-green-200" />
                                Eco-Estudiante
                            </span>
                            <span class="rounded-full px-3 py-1 text-sm backdrop-blur-sm flex items-center gap-1 bg-emerald-500/20 text-emerald-100">
                                <Recycle class="h-4 w-4 text-emerald-200" />
                                Reciclador
                            </span>
                            <span class="rounded-full px-3 py-1 text-sm backdrop-blur-sm flex items-center gap-1 bg-blue-500/20 text-blue-100">
                                <Globe class="h-4 w-4 text-blue-200" />
                                Guardián del Planeta
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stats Highlight -->
                <div class="mt-8 lg:mt-0 lg:ml-8">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-xl bg-white/20 p-4 text-center backdrop-blur-sm">
                            <div class="text-2xl font-bold">{{ totalPoints }}</div>
                            <div class="text-sm text-green-100">Puntos</div>
                        </div>
                        <div class="rounded-xl bg-white/20 p-4 text-center backdrop-blur-sm">
                            <div class="text-2xl font-bold">#{{ ranking }}</div>
                            <div class="text-sm text-green-100">Ranking</div>
                        </div>
                    </div>
                    
                    <div class="mt-4 rounded-xl bg-white/20 p-4 text-center backdrop-blur-sm">
                        <div class="text-lg font-bold">{{ currentPeriod || 'Sin período' }}</div>
                        <div class="text-sm text-green-100">Período Actual</div>
                    </div>
                </div>
            </div>

            <!-- Motivational Quote -->
            <div class="mt-8 text-center">
                <div class="mx-auto max-w-2xl rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                    <p class="text-lg font-medium italic">
                        "{{ motivationalQuote }}"
                    </p>
                    <div class="mt-2 flex justify-center space-x-2 text-2xl">
                        <Leaf class="h-5 w-5 text-green-200" />
                        <Recycle class="h-5 w-5 text-emerald-200" />
                        <Globe class="h-5 w-5 text-blue-200" />
                        <Heart class="h-5 w-5 text-pink-200" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Decorative Elements -->
        <div class="absolute -bottom-2 -right-2 h-32 w-32 rounded-full bg-white/5"></div>
        <div class="absolute -top-2 -left-2 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="absolute bottom-4 left-4 h-16 w-16 rounded-full bg-white/5"></div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Leaf, Recycle, Globe, Heart } from 'lucide-vue-next';

interface Props {
    studentName: string;
    course?: string;
    parallel?: string;
    totalPoints: number;
    ranking: number;
    currentPeriod?: string;
}

const props = defineProps<Props>();

const firstName = computed(() => {
    return props.studentName.split(' ')[0];
});

const welcomeMessage = computed(() => {
    const messages = [
        "¡Bienvenido a tu panel ecológico! Cada punto cuenta para salvar nuestro planeta.",
        "¡Excelente trabajo cuidando el medio ambiente! Sigue así, héroe ecológico.",
        "Tu compromiso con el reciclaje está marcando la diferencia. ¡Continúa!",
        "¡Eres un verdadero guardián del planeta! Cada depósito suma para un futuro mejor.",
        "¡Increíble dedicación al cuidado ambiental! Tu ejemplo inspira a otros."
    ];
    
    // Seleccionar mensaje basado en los puntos
    if (props.totalPoints >= 500) return messages[4];
    if (props.totalPoints >= 300) return messages[3];
    if (props.totalPoints >= 150) return messages[2];
    if (props.totalPoints >= 50) return messages[1];
    return messages[0];
});

const motivationalQuote = computed(() => {
    const quotes = [
        "Pequeñas acciones pueden generar grandes cambios en nuestro planeta",
        "El futuro de la Tierra está en nuestras manos, ¡y tú estás haciendo la diferencia!",
        "Cada material reciclado es un paso hacia un mundo más verde y sostenible",
        "Tu compromiso de hoy es el regalo que le das al planeta del mañana",
        "Ser eco-responsable no es solo una elección, es un superpoder"
    ];
    
    return quotes[Math.floor(Math.random() * quotes.length)];
});
</script>