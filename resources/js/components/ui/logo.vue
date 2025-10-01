<template>
    <div class="flex items-center">
        <div :class="containerClass">
            <img 
                :src="logoSrc" 
                :alt="altText"
                :class="imageClass"
                @error="handleImageError"
            >
        </div>
        <div v-if="showText" class="ml-3">
            <h1 :class="titleClass">
                {{ title }}
            </h1>
            <p v-if="subtitle" :class="subtitleClass">
                {{ subtitle }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

interface Props {
    size?: 'sm' | 'md' | 'lg' | 'xl';
    variant?: 'default' | 'circular' | 'square' | 'rounded';
    showText?: boolean;
    title?: string;
    subtitle?: string;
    theme?: 'light' | 'dark' | 'green';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    variant: 'circular',
    showText: true,
    title: 'Colegio Darío Montaño',
    subtitle: 'Educación Ambiental',
    theme: 'green'
});

const imageError = ref(false);

const logoSrc = computed(() => {
    return imageError.value ? '/img/logo-fallback.svg' : '/img/LogoDario.png';
});

const altText = computed(() => {
    return `Logo ${props.title}`;
});

const sizeClasses = {
    sm: 'h-8 w-8',
    md: 'h-12 w-12',
    lg: 'h-16 w-16',
    xl: 'h-20 w-20'
};

const containerClasses = {
    default: 'bg-white/20 backdrop-blur-sm',
    circular: 'rounded-full bg-white/20 backdrop-blur-sm',
    square: 'bg-white/20 backdrop-blur-sm',
    rounded: 'rounded-lg bg-white/20 backdrop-blur-sm'
};

const containerClass = computed(() => {
    const base = 'flex items-center justify-center p-2';
    const size = sizeClasses[props.size];
    const variant = containerClasses[props.variant];
    
    return `${base} ${variant}`;
});

const imageClass = computed(() => {
    const base = sizeClasses[props.size];
    const shape = props.variant === 'circular' ? 'rounded-full' : 
                  props.variant === 'rounded' ? 'rounded' : '';
    
    return `${base} ${shape} object-cover`;
});

const titleClass = computed(() => {
    const sizeClass = props.size === 'xl' ? 'text-2xl' :
                      props.size === 'lg' ? 'text-xl' :
                      props.size === 'md' ? 'text-lg' : 'text-base';
    
    const themeClass = props.theme === 'dark' ? 'text-white' :
                       props.theme === 'light' ? 'text-gray-900' :
                       'text-white';
    
    return `font-bold ${sizeClass} ${themeClass}`;
});

const subtitleClass = computed(() => {
    const sizeClass = props.size === 'xl' ? 'text-base' :
                      props.size === 'lg' ? 'text-sm' :
                      'text-xs';
    
    const themeClass = props.theme === 'dark' ? 'text-gray-300' :
                       props.theme === 'light' ? 'text-gray-600' :
                       'text-green-100';
    
    return `${sizeClass} ${themeClass}`;
});

const handleImageError = () => {
    imageError.value = true;
};
</script>