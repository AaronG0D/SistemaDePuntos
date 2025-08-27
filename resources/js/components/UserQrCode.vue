<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { UserQrProps } from '@/types/user';
import { QrCode, Loader2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = withDefaults(defineProps<UserQrProps>(), {
    showButton: true,
});

// Transformar el objeto QR en string si es necesario
const qrCode = computed(() => {
    if (!props.user.qr_codigo) {
        return '';
    }
    if (typeof props.user.qr_codigo === 'string') {
        return props.user.qr_codigo;
    }
    // Si es un objeto, usar el ID como código
    return props.user.qr_codigo.id.toString();
});

const dialogOpen = ref(false);
const isLoading = ref(false);
const isError = ref(false);

const fullName = computed(() => {
    return `${props.user.nombres} ${props.user.primerApellido} ${props.user.segundoApellido || ''}`.trim();
});

const qrUrl = computed(() => {
    // Si no hay código, devolver cadena vacía
    if (!props.user.qr_codigo) return '';
    const qrData = props.user.qr_codigo;

    // Convertir a JSON y codificar para URL
    const encodedData = encodeURIComponent(JSON.stringify(qrData));

    return (
        'https://api.qrserver.com/v1/create-qr-code/?' +
        new URLSearchParams({
            size: '200x200',
            data: encodedData,
            format: 'svg',
            charset: 'utf-8',
        }).toString()
    );
});

// Cuando se abre el diálogo y hay URL, activar loading
watch(dialogOpen, (open) => {
    if (open && qrUrl.value) {
        isLoading.value = true;
        isError.value = false;
    }
});

function downloadQR() {
    if (!qrUrl.value) return;
    const link = document.createElement('a');
    link.href = qrUrl.value;
    link.download = `qr-${fullName.value.toLowerCase().replace(/\s+/g, '-')}.svg`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<template>
    <div>
        <!-- Botón para mostrar el QR -->
        <Button v-if="showButton" variant="ghost" size="sm" @click="dialogOpen = true">
            <QrCode class="h-4 w-4" />
        </Button>

        <!-- Diálogo del QR -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Código QR de {{ fullName }}</DialogTitle>
                </DialogHeader>

                <div class="flex flex-col items-center gap-4 py-4">
                    <!-- QR Code -->
                    <div class="h-48 w-48 flex items-center justify-center relative overflow-hidden rounded-md border">
                        <!-- Imagen QR (se oculta mientras carga) -->
                        <img
                            v-if="qrUrl"
                            :src="qrUrl"
                            :alt="`QR de ${fullName}`"
                            class="h-full w-full"
                            v-show="!isLoading && !isError"
                            @load="isLoading = false; isError = false"
                            @error="isLoading = false; isError = true"
                        />
                        <!-- Loading Spinner -->
                        <div v-if="isLoading" class="flex flex-col items-center justify-center text-muted-foreground">
                            <Loader2 class="h-6 w-6 animate-spin" />
                            <span class="mt-2 text-xs">Cargando QR...</span>
                        </div>
                        <!-- Error -->
                        <div v-if="isError" class="text-center text-red-600 text-xs px-2">
                            No se pudo cargar el QR.
                        </div>
                    </div>
                    <!-- Mostrar la información del QR -->
                    <div class="text-center">
                        <p class="text-sm font-medium">{{ fullName }}</p>
                        <p v-if="props.user.qr_codigo" class="text-sm text-gray-500">
                            ID: {{ typeof props.user.qr_codigo === 'string' ? props.user.qr_codigo : props.user.qr_codigo.id }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="dialogOpen = false">Cerrar</Button>
                    <Button :disabled="!qrUrl || isLoading || isError" @click="downloadQR">Descargar QR</Button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
