<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed, watch } from 'vue'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'

interface RaspberryEvent {
  id: number
  qr_codigo: string
  tipo_basura_nombre?: string | null
  status: 'pending' | 'success' | 'failed'
  message?: string | null
  ip?: string | null
  created_at: string
  processed_at?: string | null
  user?: {
    id: number
    nombre: string
    apellidos: string
  } | null
  tipo_basura?: {
    id: number
    nombre: string
    puntos: number
  } | null
  deposito?: {
    id: number
    puntos: number
    fecha: string
  } | null
}

interface ApiResponse {
  success: boolean
  data: RaspberryEvent[]
  total: number
}

const eventos = ref<RaspberryEvent[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const selectedStatus = ref<string>('all')
const autoRefresh = ref(true)
const refreshInterval = ref(5) // segundos

let timer: number | undefined

// Computed properties
const eventosFiltered = computed(() => {
  if (selectedStatus.value === 'all') {
    return eventos.value
  }
  return eventos.value.filter(evento => evento.status === selectedStatus.value)
})

const estadisticas = computed(() => {
  const total = eventos.value.length
  const exitosos = eventos.value.filter(e => e.status === 'success').length
  const fallidos = eventos.value.filter(e => e.status === 'failed').length
  const pendientes = eventos.value.filter(e => e.status === 'pending').length
  
  return {
    total,
    exitosos,
    fallidos,
    pendientes,
    tasaExito: total > 0 ? Math.round((exitosos / total) * 100) : 0
  }
})

// Funciones
async function fetchEventos() {
  if (loading.value) return
  
  loading.value = true
  error.value = null
  
  try {
    const params = new URLSearchParams({
      limit: '100'
    })
    
    if (selectedStatus.value !== 'all') {
      params.append('status', selectedStatus.value)
    }
    
    const { data } = await axios.get<ApiResponse>(`/api/raspberry/eventos?${params}`)
    
    if (data.success) {
      eventos.value = data.data
    } else {
      error.value = 'Error al cargar los eventos'
    }
  } catch (err) {
    console.error('Error fetching eventos:', err)
    error.value = 'Error de conexión al servidor'
  } finally {
    loading.value = false
  }
}

function startAutoRefresh() {
  if (timer) clearInterval(timer)
  
  if (autoRefresh.value && refreshInterval.value > 0) {
    timer = window.setInterval(fetchEventos, refreshInterval.value * 1000)
  }
}

function stopAutoRefresh() {
  if (timer) {
    clearInterval(timer)
    timer = undefined
  }
}

function toggleAutoRefresh() {
  autoRefresh.value = !autoRefresh.value
  if (autoRefresh.value) {
    startAutoRefresh()
  } else {
    stopAutoRefresh()
  }
}

function getStatusColor(status: string): string {
  switch (status) {
    case 'success': return 'text-green-600 bg-green-100'
    case 'failed': return 'text-red-600 bg-red-100'
    case 'pending': return 'text-yellow-600 bg-yellow-100'
    default: return 'text-gray-600 bg-gray-100'
  }
}

function getStatusIcon(status: string): string {
  switch (status) {
    case 'success': return '✅'
    case 'failed': return '❌'
    case 'pending': return '⏳'
    default: return '❓'
  }
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  fetchEventos()
  startAutoRefresh()
})

onUnmounted(() => {
  stopAutoRefresh()
})

// Watchers
watch(() => selectedStatus.value, () => {
  fetchEventos()
})

watch(() => refreshInterval.value, () => {
  if (autoRefresh.value) {
    startAutoRefresh()
  }
})
</script>

<template>
  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Eventos de Raspberry Pi
        </h2>
        <div class="flex items-center space-x-4">
          <button
            @click="fetchEventos"
            :disabled="loading"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
          >
            {{ loading ? 'Cargando...' : 'Actualizar' }}
          </button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="text-2xl font-bold text-gray-900">{{ estadisticas.total }}</div>
            <div class="text-sm text-gray-600">Total Eventos</div>
          </div>
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="text-2xl font-bold text-green-600">{{ estadisticas.exitosos }}</div>
            <div class="text-sm text-gray-600">Exitosos</div>
          </div>
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="text-2xl font-bold text-red-600">{{ estadisticas.fallidos }}</div>
            <div class="text-sm text-gray-600">Fallidos</div>
          </div>
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="text-2xl font-bold text-yellow-600">{{ estadisticas.pendientes }}</div>
            <div class="text-sm text-gray-600">Pendientes</div>
          </div>
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="text-2xl font-bold text-blue-600">{{ estadisticas.tasaExito }}%</div>
            <div class="text-sm text-gray-600">Tasa de Éxito</div>
          </div>
        </div>

        <!-- Controles -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="flex flex-wrap items-center gap-4">
              <!-- Filtro por estado -->
              <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Estado:</label>
                <select 
                  v-model="selectedStatus"
                  class="border border-gray-300 rounded-md px-3 py-1 text-sm"
                >
                  <option value="all">Todos</option>
                  <option value="success">Exitosos</option>
                  <option value="failed">Fallidos</option>
                  <option value="pending">Pendientes</option>
                </select>
              </div>

              <!-- Auto-refresh -->
              <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Auto-actualizar:</label>
                <button
                  @click="toggleAutoRefresh"
                  :class="[
                    'px-3 py-1 rounded text-sm font-medium',
                    autoRefresh 
                      ? 'bg-green-100 text-green-800' 
                      : 'bg-gray-100 text-gray-800'
                  ]"
                >
                  {{ autoRefresh ? 'ON' : 'OFF' }}
                </button>
              </div>

              <!-- Intervalo de refresh -->
              <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Cada:</label>
                <select 
                  v-model="refreshInterval"
                  class="border border-gray-300 rounded-md px-3 py-1 text-sm"
                >
                  <option :value="3">3 seg</option>
                  <option :value="5">5 seg</option>
                  <option :value="10">10 seg</option>
                  <option :value="30">30 seg</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Error -->
        <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
          {{ error }}
        </div>

        <!-- Tabla de eventos -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Estado
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    QR Código
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Estudiante
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Tipo Basura
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Puntos
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Fecha
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    IP
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Mensaje
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-if="loading && eventos.length === 0">
                  <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                    Cargando eventos...
                  </td>
                </tr>
                <tr v-else-if="eventosFiltered.length === 0">
                  <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                    No hay eventos para mostrar
                  </td>
                </tr>
                <tr v-else v-for="evento in eventosFiltered" :key="evento.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusColor(evento.status)]">
                      {{ getStatusIcon(evento.status) }} {{ evento.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                    {{ evento.qr_codigo }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div v-if="evento.user">
                      <div class="font-medium">{{ evento.user.nombre }}</div>
                      <div class="text-gray-500">{{ evento.user.apellidos }}</div>
                    </div>
                    <span v-else class="text-gray-400">-</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div v-if="evento.tipo_basura">
                      <div class="font-medium">{{ evento.tipo_basura.nombre }}</div>
                    </div>
                    <div v-else-if="evento.tipo_basura_nombre">
                      <div class="text-gray-600">{{ evento.tipo_basura_nombre }}</div>
                    </div>
                    <span v-else class="text-gray-400">-</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span v-if="evento.deposito" class="font-medium text-green-600">
                      +{{ evento.deposito.puntos }}
                    </span>
                    <span v-else-if="evento.tipo_basura" class="text-gray-500">
                      {{ evento.tipo_basura.puntos }}
                    </span>
                    <span v-else class="text-gray-400">-</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div>{{ formatDate(evento.created_at) }}</div>
                    <div v-if="evento.processed_at" class="text-xs text-gray-500">
                      Procesado: {{ formatDate(evento.processed_at) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                    {{ evento.ip || '-' }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                    {{ evento.message || '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Información adicional -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h3 class="text-lg font-medium text-blue-900 mb-2">Información del Sistema</h3>
          <div class="text-sm text-blue-800 space-y-1">
            <p><strong>Endpoint API:</strong> <code class="bg-blue-100 px-2 py-1 rounded">/api/raspberry/deposito</code></p>
            <p><strong>Método:</strong> POST</p>
            <p><strong>Autenticación:</strong> Header X-API-KEY</p>
            <p><strong>Última actualización:</strong> {{ new Date().toLocaleString('es-ES') }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
