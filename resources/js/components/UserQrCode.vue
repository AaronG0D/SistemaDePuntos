<template>
    <div>
        <!-- Botón para mostrar el QR -->
        <Button v-if="showButton" variant="ghost" size="sm" @click="dialogOpen = true">
            <QrCode class="h-4 w-4" />
        </Button>

        <!-- Dialog para mostrar QR -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>QR Code - {{ fullName }}</DialogTitle>
                </DialogHeader>
                <div class="flex flex-col items-center gap-4 py-4">
                    <!-- QR Code -->
                    <div class="relative flex h-48 w-48 items-center justify-center overflow-hidden rounded-md border bg-white dark:bg-white">
                        <img
                            v-if="qrUrl"
                            :src="qrUrl"
                            :alt="`QR Code para ${fullName}`"
                            class="h-full w-full object-contain"
                            v-show="!isLoading && !isError"
                            @load="
                                isLoading = false;
                                isError = false;
                            "
                            @error="
                                isLoading = false;
                                isError = true;
                            "
                        />
                        <div v-if="isLoading" class="text-muted-foreground flex flex-col items-center justify-center">
                            <Loader2 class="h-6 w-6 animate-spin" />
                            <span class="mt-2 text-xs">Cargando QR...</span>
                        </div>
                        <div v-if="isError" class="px-2 text-center text-xs text-red-600">No se pudo cargar el QR.</div>
                    </div>
                    <!-- Información del QR -->
                    <div class="text-center">
                        <p class="text-sm text-gray-600">{{ fullName }}</p>
                        <p class="text-xs text-gray-500">{{ props.user.email }}</p>
                    </div>
                    <!-- Botones -->
                    <div class="flex gap-2">
                        <Button @click="downloadQR" variant="outline">
                            <Download class="mr-2 h-4 w-4" />
                            Descargar
                        </Button>
                        <Button @click="dialogOpen = false" variant="outline"> Cerrar </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { UserQrProps } from '@/types/user';
import { Download, Loader2, QrCode } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = withDefaults(defineProps<UserQrProps>(), {
    showButton: true,
});

const fullName = computed(() => {
    return `${props.user.nombres} ${props.user.primerApellido} ${props.user.segundoApellido || ''}`.trim();
});

const qrUrl = ref('');

// Generar QR cuando se abre el diálogo
const generateQr = async () => {
    if (!props.user.qr_codigo) return;

    isLoading.value = true;
    isError.value = false;

    try {
        const response = await fetch(route('qr.generate.user', props.user.id));
        const data = await response.json();

        if (data.success) {
            qrUrl.value = data.qr_url;
        } else {
            isError.value = true;
        }
    } catch (error) {
        console.error('Error generando QR:', error);
        isError.value = true;
    } finally {
        isLoading.value = false;
    }
};

// Estado para manejar la carga del QR
const isLoading = ref(false);
const isError = ref(false);
const dialogOpen = ref(false);

// Cuando se abre el diálogo, generar QR
watch(dialogOpen, (open) => {
    if (open) {
        generateQr();
    }
});

function downloadQR() {
    if (!qrUrl.value) return;
    const link = document.createElement('a');
    link.href = qrUrl.value;
    link.download = `qr_${props.user.nombres}_${props.user.primerApellido}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
