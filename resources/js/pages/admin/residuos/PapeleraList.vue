<template>
  <AppLayout>
    <Head title="Papelera" /> 
    <div class="container mx-auto py-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">Papelera</h1>
          <p class="text-sm text-muted-foreground">Elementos eliminados (soft delete)</p>
        </div>
        <div class="flex items-center gap-2">
          <Select v-model="recurso">
            <SelectTrigger class="w-48">
              <SelectValue placeholder="Selecciona recurso" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="basureros">Basureros</SelectItem>
              <SelectItem value="tipos">Tipos de Basura</SelectItem>
              <SelectItem value="depositos">Depósitos</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
      <div class="mt-6">
        <Table v-if="recurso === 'basureros'">
          <TableHeader>
            <TableRow>
              <TableHead>ID Basurero</TableHead>
              <TableHead>Ubicación</TableHead>
              <TableHead>Descripción</TableHead>
              <TableHead>Fecha Eliminación</TableHead>
              <TableHead>Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="props.basureros.length === 0">
              <TableCell colspan="5" class="text-center">No hay basureros eliminados</TableCell>
            </TableRow>
            <TableRow v-for="item in props.basureros" :key="item.idBasurero">
              <TableCell>{{ item.idBasurero }}</TableCell>
              <TableCell>{{ item.ubicacion }}</TableCell>
              <TableCell>{{ item.descripcion || 'N/A' }}</TableCell>
              <TableCell>{{ formatDate(item.deleted_at || '') }}</TableCell>  
              <TableCell>
                <Button @click="restaurarBasurero(item.idBasurero)">Restaurar</Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <Table v-else-if="recurso === 'tipos'">
          <TableHeader>
            <TableRow>
              <TableHead>ID Tipo Basura</TableHead>
              <TableHead>Nombre</TableHead>
              <TableHead>Descripción</TableHead>
              <TableHead>Fecha Eliminación</TableHead>
              <TableHead>Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="props.tiposBasura.length === 0">
              <TableCell colspan="5" class="text-center">No hay tipos de basura eliminados</TableCell>
            </TableRow>
            <TableRow v-for="item in props.tiposBasura" :key="item.idTipoBasura">
              <TableCell>{{ item.idTipoBasura }}</TableCell>
              <TableCell>{{ item.nombre }}</TableCell>
              <TableCell>{{ item.descripcion || 'N/A' }}</TableCell>
              <TableCell>{{ formatDate(item.deleted_at || '') }}</TableCell>  
              <TableCell>
                <Button @click="restaurarTipoBasura(item.idTipoBasura)">Restaurar</Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <Table v-else-if="recurso === 'depositos'">
          <TableHeader>
            <TableRow>
              <TableHead>ID Depósito</TableHead>
              <TableHead>Usuario</TableHead>
              <TableHead>Tipo Residuo</TableHead>
              <TableHead>Puntos</TableHead>
              <TableHead>Fecha Eliminación</TableHead>
              <TableHead>Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="props.depositos.length === 0">
              <TableCell colspan="6" class="text-center">No hay depósitos eliminados</TableCell>
            </TableRow>
            <TableRow v-for="item in props.depositos" :key="item.idDeposito">
              <TableCell>{{ item.idDeposito }}</TableCell>
              <TableCell>{{ item.user?.nombres + ' ' + item.user?.primerApellido + ' ' + item.user?.segundoApellido || 'N/A' }}</TableCell>
              <TableCell>{{ item.tipo_basura?.nombre || 'N/A' }}</TableCell>
              <TableCell>{{ item.puntos ?? item.tipo_basura?.puntos }}</TableCell> 
              <TableCell>{{ formatDate(item.deleted_at || '') }}</TableCell>  
              <TableCell>
                <Button @click="restaurarDeposito(item.idDeposito)">Restaurar</Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      
    </div>
  </AppLayout>
  
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue' 
import Button from '@/components/ui/button/Button.vue'
import { Basurero, Deposito, TipoBasura} from '@/types/residuos'
import {Table, TableBody, TableCell, TableHead, TableHeader, TableRow} from '@/components/ui/table'
import { Link ,router} from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { toast, Toaster } from 'vue-sonner';


// ===== PROPS =====
const props = defineProps<{
  basureros: Basurero[]
  depositos: Deposito[]
  tiposBasura: TipoBasura[]
}>()
const recurso = ref('basureros')

// ===== COMPUTED =====
const formatDate = (date: string) => {  
  return new Date(date).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
} 

// ===== METHODS =====      
async function restaurarBasurero(id: number) {
  router.post(`/admin/basureros/${id}/restore`,{},{
    onSuccess: () => {  
                toast.success('Basurero restaurado', {
                    description: 'El basurero ha sido restaurado correctamente',
                });
            },
            onError: () => {
                toast.error('Error al reactivar', {
                    description: 'No se pudo reactivar el basurero',
                });
            },
  })
}




 function restaurarTipoBasura(id: number) {
   router.post(`/admin/tipos-basura/${id}/restore`,{},{
    onSuccess: () => {  
                toast.success('Tipo de basura restaurado', {
                    description: 'El tipo de basura ha sido restaurado correctamente',
                });
            },
            onError: () => {
                toast.error('Error al reactivar', {
                    description: 'No se pudo reactivar el docente',
                });
            },
  },
          );
}
 function restaurarDeposito(id: number) {
    router.post(`/admin/depositos/${id}/restore`,{},{
    onSuccess: () => {  
                toast.success('Depósito restaurado', {
                    description: 'El depósito ha sido restaurado correctamente',
                });
            },
            onError: () => {
                toast.error('Error al reactivar', {
                    description: 'No se pudo reactivar el depósito',
                });
            },
  })
}
</script>