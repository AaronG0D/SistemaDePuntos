<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

// Props entregadas por EstudianteController@create
const props = defineProps<{
  usuariosDisponibles: { id:number; nombres:string; primerApellido:string; segundoApellido?:string|null; email:string }[],
  cursos: { idCurso:number; nombre:string }[],
  paralelos: { idParalelo:number; nombre:string }[],
  cursoParalelos: { idCursoParalelo:number; curso:{idCurso:number; nombre:string}; paralelo:{idParalelo:number; nombre:string} }[]
}>()

const form = ref({
  idUser: null as number | null,
  idCursoParalelo: null as number | null,
})

const errors = ref<Record<string, string>>({})

function submit() {
  errors.value = {}
  router.post('/admin/estudiantes', {
    idUser: form.value.idUser,
    idCursoParalelo: form.value.idCursoParalelo,
  }, {
    onError: (e: Record<string, string>) => {
      errors.value = e || {}
    },
  })
}

function cancel() {
  router.get(route('admin.estudiantes'))
}
</script>

<template>
  <Head title="Crear Estudiante" />
  <AppLayout :breadcrumbs="[
    { title: 'Gestión Académica', href: route('admin.estudiantes') },
    { title: 'Estudiantes', href: route('admin.estudiantes') },
    { title: 'Crear', href: route('admin.estudiantes.create') },
  ]">
    <div class="container mx-auto py-6">
      <div class="mx-auto max-w-2xl">
        <div class="mb-6">
          <h1 class="text-2xl font-bold">Crear Estudiante</h1>
          <p class="text-sm text-muted-foreground">Asigna un usuario con rol estudiante a un Curso/Paralelo.</p>
        </div>

        <!-- Formulario simple sin dependencias de UI -->
        <form class="space-y-6" @submit.prevent="submit">
          <div class="space-y-2">
            <label class="block text-sm font-medium">Usuario (rol estudiante)</label>
            <select v-model.number="form.idUser" class="w-full border rounded-md h-10 px-3">
              <option :value="null" disabled>Selecciona un usuario</option>
              <option v-for="u in props.usuariosDisponibles" :key="u.id" :value="u.id">
                {{ u.nombres }} {{ u.primerApellido }} {{ u.segundoApellido || '' }} ({{ u.email }})
              </option>
            </select>
            <p v-if="errors.idUser" class="text-sm text-red-500">{{ errors.idUser }}</p>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium">Curso / Paralelo</label>
            <select v-model.number="form.idCursoParalelo" class="w-full border rounded-md h-10 px-3">
              <option :value="null" disabled>Selecciona curso y paralelo</option>
              <option v-for="cp in props.cursoParalelos" :key="cp.idCursoParalelo" :value="cp.idCursoParalelo">
                {{ cp.curso?.nombre }} - {{ cp.paralelo?.nombre }}
              </option>
            </select>
            <p v-if="errors.idCursoParalelo" class="text-sm text-red-500">{{ errors.idCursoParalelo }}</p>
          </div>

          <div class="flex justify-end gap-3">
            <button type="button" class="border rounded-md px-4 h-10" @click="cancel">Cancelar</button>
            <button type="submit" class="bg-primary text-primary-foreground rounded-md px-4 h-10">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
