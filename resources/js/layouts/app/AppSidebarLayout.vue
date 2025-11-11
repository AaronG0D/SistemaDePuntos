<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';
import { ref } from 'vue';

const isDarkMode = ref(false);

function toggleDarkMode() {
    isDarkMode.value = !isDarkMode.value;
    const theme = isDarkMode.value ? 'dark' : 'light';
    document.documentElement.classList.toggle('dark', isDarkMode.value);
    localStorage.setItem('theme', theme);
}

// Cargar el tema desde localStorage al iniciar
if (localStorage.getItem('theme') === 'dark') {
    isDarkMode.value = true;
    document.documentElement.classList.add('dark');
}

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <header class="flex items-center justify-between p-4">
                <AppSidebarHeader :breadcrumbs="breadcrumbs" />
                <button @click="toggleDarkMode" class="rounded-full p-2 transition hover:bg-green-100 dark:hover:bg-green-900" title="Cambiar tema">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 3v1.5M12 19.5V21m8.485-8.485H21M3 12h1.5m15.364-6.364-.707.707M5.343 18.657l-.707.707m0-13.414.707.707m13.414 13.414.707.707M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"
                        />
                    </svg>
                </button>
            </header>
            <slot />
        </AppContent>
    </AppShell>
</template>
