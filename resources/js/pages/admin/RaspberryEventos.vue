<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed, watch } from 'vue'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import { 
  CheckCircle, 
  XCircle, 
  Clock, 
  Search, 
  Trash2, 
  HelpCircle, 
  BarChart3, 
  RefreshCw, 
  Activity,
  Wifi,
  Settings,
  Calendar
} from 'lucide-vue-next'

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
  eventos: RaspberryEvent[]
  total: number
  current_page: number
  last_page: number
  per_page: number
  from?: number
  to?: number
}

const eventos = ref<RaspberryEvent[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const selectedStatus = ref<string>('all')
const autoRefresh = ref(true)
const refreshInterval = ref(5) // segundos

// Paginación
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = ref(20)
const total = ref(0)

let timer: number | undefined

// Computed properties - Ya no necesitamos filtrar aquí, se hace en backend
const eventosFiltered = computed(() => {
  return eventos.value
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
async function fetchEventos(page: number = currentPage.value) {
  if (loading.value) return
  
  loading.value = true
  error.value = null
  
  try {
    const params: any = {
      page: page,
      per_page: perPage.value
    }
    if (selectedStatus.value !== 'all') {
      params.status = selectedStatus.value
    }
    
    const response = await axios.get<ApiResponse>('/api/raspberry/eventos', { params })
    
    if (response.data.success) {
      eventos.value = response.data.eventos || response.data.data || []
      currentPage.value = response.data.current_page || 1
      lastPage.value = response.data.last_page || 1
      total.value = response.data.total || 0
    } else {
      error.value = 'Error al cargar eventos'
    }
  } catch (err) {
    console.error('Error fetching eventos:', err)
    error.value = 'Error de conexión'
  } finally {
    loading.value = false
  }
}

// Funciones de paginación
function goToPage(page: number) {
  if (page >= 1 && page <= lastPage.value && page !== currentPage.value) {
    currentPage.value = page
    fetchEventos(page)
  }
}

function nextPage() {
  if (currentPage.value < lastPage.value) {
    goToPage(currentPage.value + 1)
  }
}

function prevPage() {
  if (currentPage.value > 1) {
    goToPage(currentPage.value - 1)
  }
}

function startAutoRefresh() {
  if (timer) clearInterval(timer)
  
  if (autoRefresh.value && refreshInterval.value > 0) {
    timer = window.setInterval(() => fetchEventos(), refreshInterval.value * 1000)
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

function getStatusIcon(status: string) {
  switch (status) {
    case 'success': return CheckCircle
    case 'failed': return XCircle
    case 'pending': return Clock
    default: return HelpCircle
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

function getActionInfo(evento: RaspberryEvent): { action: string, icon: any, color: string } {
  // Detectar acción por tipo_basura_nombre o por el mensaje
  if (evento.tipo_basura_nombre) {
    return {
      action: 'Depósito',
      icon: Trash2,
      color: 'text-blue-600'
    }
  } else if (evento.message?.includes('Verificando') || evento.message?.includes('verificado')) {
    return {
      action: 'Verificación',
      icon: Search,
      color: 'text-purple-600'
    }
  } else {
    return {
      action: 'Desconocido',
      icon: HelpCircle,
      color: 'text-muted-foreground'
    }
  }
}

function getPuntosDisplay(evento: RaspberryEvent): string {
  if (evento.status === 'success' && evento.deposito) {
    return `+${evento.deposito.puntos}`
  } else if (evento.tipo_basura && evento.status === 'success') {
    return `+${evento.tipo_basura.puntos}`
  } else if (evento.tipo_basura) {
    return `${evento.tipo_basura.puntos} pts`
  }
  return '-'
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
  currentPage.value = 1 // Reiniciar a página 1 cuando cambie el filtro
  fetchEventos(1)
})

watch(() => refreshInterval.value, () => {
  if (autoRefresh.value) {
    startAutoRefresh()
  }
})
</script>

<template>
  <AppLayout>
      <div class="container mx-auto py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div class="flex justify-between items-center">
            <div>
              <div class="flex items-center space-x-3">
                <Activity class="h-8 w-8" />
                <h1 class="text-3xl font-bold">Monitoreo Raspberry Pi</h1>
              </div>
              <p class="text-muted-foreground mt-2">Sistema de basureros inteligentes en tiempo real</p>
            </div>
            <div class="flex items-center space-x-4">
              <div class="text-right">
                <div class="text-sm text-muted-foreground">Estado del Sistema</div>
                <div class="flex items-center space-x-2">
                  <Wifi class="h-4 w-4 text-green-400 animate-pulse" />
                  <span class="font-semibold">Activo</span>
                </div>
              </div>
              <button
                @click="() => fetchEventos()"
                :disabled="loading"
                class="bg-background/20 hover:bg-background/30 text-foreground font-bold py-2 px-4 rounded-lg backdrop-blur-sm transition-all disabled:opacity-50 flex items-center space-x-2"
              >
                <RefreshCw :class="loading ? 'animate-spin' : ''" class="h-4 w-4" />
                <span>{{ loading ? 'Cargando...' : 'Actualizar' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
          <div class="bg-card border border-border rounded-lg p-6 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-2xl font-bold text-foreground">{{ estadisticas.total }}</div>
                <div class="text-sm text-muted-foreground">Total Eventos</div>
              </div>
              <BarChart3 class="h-8 w-8 text-muted-foreground" />
            </div>
          </div>
          <div class="bg-card border border-border rounded-lg p-6 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-2xl font-bold text-green-600">{{ estadisticas.exitosos }}</div>
                <div class="text-sm text-muted-foreground">Exitosos</div>
              </div>
              <CheckCircle class="h-8 w-8 text-green-600" />
            </div>
          </div>
          <div class="bg-card border border-border rounded-lg p-6 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-2xl font-bold text-red-600">{{ estadisticas.fallidos }}</div>
                <div class="text-sm text-muted-foreground">Fallidos</div>
              </div>
              <XCircle class="h-8 w-8 text-red-600" />
            </div>
          </div>
          <div class="bg-card border border-border rounded-lg p-6 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-2xl font-bold text-yellow-600">{{ estadisticas.pendientes }}</div>
                <div class="text-sm text-muted-foreground">Pendientes</div>
              </div>
              <Clock class="h-8 w-8 text-yellow-600" />
            </div>
          </div>
          <div class="bg-card border border-border rounded-lg p-6 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-2xl font-bold text-primary">{{ estadisticas.tasaExito }}%</div>
                <div class="text-sm text-muted-foreground">Tasa de Éxito</div>
              </div>
              <Activity class="h-8 w-8 text-primary" />
            </div>
          </div>
        </div>

        <!-- Controles -->
        <div class="bg-card border border-border rounded-lg mb-6 shadow-sm">
          <div class="p-6">
            <div class="flex flex-wrap items-center gap-4">
              <!-- Filtro por estado -->
              <div class="flex items-center space-x-2">
                <Search class="h-4 w-4 text-muted-foreground" />
                <label class="text-sm font-medium text-foreground">Estado:</label>
                <select 
                  v-model="selectedStatus"
                  class="border border-border rounded-md px-3 py-1 text-sm bg-background text-foreground"
                >
                  <option value="all">Todos</option>
                  <option value="success">Exitosos</option>
                  <option value="failed">Fallidos</option>
                  <option value="pending">Pendientes</option>
                </select>
              </div>

              <!-- Auto-refresh -->
              <div class="flex items-center space-x-2">
                <RefreshCw class="h-4 w-4 text-muted-foreground" />
                <label class="text-sm font-medium text-foreground">Auto-actualizar:</label>
                <button
                  @click="toggleAutoRefresh"
                  :class="[
                    'px-3 py-1 rounded text-sm font-medium transition-all flex items-center space-x-1',
                    autoRefresh 
                      ? 'bg-green-100 text-green-800' 
                      : 'bg-muted text-muted-foreground'
                  ]"
                >
                  <div :class="autoRefresh ? 'w-2 h-2 bg-green-500 rounded-full' : 'w-2 h-2 bg-gray-400 rounded-full'"></div>
                  <span>{{ autoRefresh ? 'ON' : 'OFF' }}</span>
                </button>
              </div>

              <!-- Intervalo de refresh -->
              <div class="flex items-center space-x-2">
                <Clock class="h-4 w-4 text-muted-foreground" />
                <label class="text-sm font-medium text-foreground">Cada:</label>
                <select 
                  v-model="refreshInterval"
                  class="border border-border rounded-md px-3 py-1 text-sm bg-background text-foreground"
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
        <div v-if="error" class="bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded mb-6">
          ❌ {{ error }}
        </div>

        <!-- Tabla de eventos -->
        <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-border">
            <div class="flex items-center space-x-2">
              <BarChart3 class="h-5 w-5 text-primary" />
              <h4 class="text-lg font-semibold text-foreground">Registro de Eventos</h4>
              <div class="text-sm text-muted-foreground">
                ({{ eventosFiltered.length }} {{ eventosFiltered.length === 1 ? 'evento' : 'eventos' }})
              </div>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
              <thead class="bg-muted/50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    Estado
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    Acción
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    QR Código
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    Estudiante
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    Tipo Basura
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    Puntos
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    Fecha
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    IP
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    Mensaje
                  </th>
                </tr>
              </thead>
              <tbody class="bg-card divide-y divide-border">
                <tr v-if="loading && eventos.length === 0">
                  <td colspan="9" class="px-6 py-4 text-center text-muted-foreground">
                    <div class="flex items-center justify-center space-x-2">
                      <RefreshCw class="h-4 w-4 animate-spin" />
                      <span>Cargando eventos...</span>
                    </div>
                  </td>
                </tr>
                <tr v-else-if="eventosFiltered.length === 0">
                  <td colspan="9" class="px-6 py-4 text-center text-muted-foreground">
                    <div class="flex items-center justify-center space-x-2">
                      <Search class="h-4 w-4" />
                      <span>No hay eventos para mostrar</span>
                    </div>
                  </td>
                </tr>
                <tr v-else v-for="evento in eventosFiltered" :key="evento.id" class="hover:bg-muted/50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium space-x-1', getStatusColor(evento.status)]">
                      <component :is="getStatusIcon(evento.status)" class="h-3 w-3" />
                      <span>{{ evento.status }}</span>
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div :class="getActionInfo(evento).color" class="flex items-center space-x-2">
                      <component :is="getActionInfo(evento).icon" class="h-4 w-4" />
                      <span class="font-medium">{{ getActionInfo(evento).action }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-foreground">
                    {{ evento.qr_codigo }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                    <div v-if="evento.user">
                      <div class="font-medium">{{ evento.user.nombre }}</div>
                      <div class="text-muted-foreground">{{ evento.user.apellidos }}</div>
                    </div>
                    <span v-else class="text-muted-foreground">-</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                    <div v-if="evento.tipo_basura">
                      <div class="font-medium">{{ evento.tipo_basura.nombre }}</div>
                    </div>
                    <div v-else-if="evento.tipo_basura_nombre">
                      <div class="text-muted-foreground">{{ evento.tipo_basura_nombre }}</div>
                    </div>
                    <span v-else class="text-muted-foreground">-</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span v-if="evento.status === 'success'" :class="evento.deposito ? 'font-bold text-green-600' : 'text-muted-foreground'">
                      {{ getPuntosDisplay(evento) }}
                    </span>
                    <span v-else class="text-muted-foreground">{{ getPuntosDisplay(evento) }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                    <div>{{ formatDate(evento.created_at) }}</div>
                    <div v-if="evento.processed_at" class="text-xs text-muted-foreground">
                      ⏱️ {{ formatDate(evento.processed_at) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground font-mono">
                    {{ evento.ip || '-' }}
                  </td>
                  <td class="px-6 py-4 text-sm text-foreground max-w-xs truncate">
                    {{ evento.message || '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Paginación -->
          <div v-if="lastPage > 1" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-muted-foreground">
              Mostrando {{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, total) }} de {{ total }} eventos
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="prevPage"
                :disabled="currentPage <= 1"
                class="px-3 py-1 text-sm bg-background border border-border rounded-md hover:bg-muted disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Anterior
              </button>
              
              <div class="flex items-center space-x-1">
                <button
                  v-for="page in Math.min(5, lastPage)"
                  :key="page"
                  @click="goToPage(page)"
                  :class="[
                    'px-3 py-1 text-sm rounded-md',
                    page === currentPage
                      ? 'bg-primary text-primary-foreground'
                      : 'bg-background border border-border hover:bg-muted'
                  ]"
                >
                  {{ page }}
                </button>
                
                <span v-if="lastPage > 5" class="px-2 text-muted-foreground">...</span>
                
                <button
                  v-if="lastPage > 5 && currentPage < lastPage"
                  @click="goToPage(lastPage)"
                  :class="[
                    'px-3 py-1 text-sm rounded-md',
                    lastPage === currentPage
                      ? 'bg-primary text-primary-foreground'
                      : 'bg-background border border-border hover:bg-muted'
                  ]"
                >
                  {{ lastPage }}
                </button>
              </div>
              
              <button
                @click="nextPage"
                :disabled="currentPage >= lastPage"
                class="px-3 py-1 text-sm bg-background border border-border rounded-md hover:bg-muted disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Siguiente
              </button>
            </div>
          </div>
        </div>

        <!-- Información adicional -->
        <div class="mt-6 bg-primary/5 border border-primary/20 rounded-lg p-4">
          <div class="flex items-center space-x-2 mb-2">
            <Settings class="h-5 w-5 text-primary" />
            <h3 class="text-lg font-medium text-foreground">Información del Sistema</h3>
          </div>
          <div class="text-sm text-muted-foreground space-y-2">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <div class="flex items-center space-x-2">
                  <Trash2 class="h-4 w-4 text-blue-600" />
                  <span><strong>Endpoint Depósito:</strong></span>
                </div>
                <code class="bg-muted px-2 py-1 rounded text-foreground block">/api/raspberry/deposito</code>
                
                <div class="flex items-center space-x-2 mt-2">
                  <Search class="h-4 w-4 text-purple-600" />
                  <span><strong>Endpoint Verificar:</strong></span>
                </div>
                <code class="bg-muted px-2 py-1 rounded text-foreground block">/api/raspberry/verificar/{qr}</code>
              </div>
              <div class="space-y-2">
                <div class="flex items-center space-x-2">
                  <Wifi class="h-4 w-4 text-green-600" />
                  <span><strong>Autenticación:</strong> Header X-API-KEY</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Calendar class="h-4 w-4 text-muted-foreground" />
                  <span><strong>Última actualización:</strong> {{ new Date().toLocaleString('es-ES') }}</span>
                </div>
              </div>
            </div>
            <div class="mt-3 pt-3 border-t border-border">
              <div class="flex items-start space-x-2">
                <Activity class="h-4 w-4 text-primary mt-0.5 flex-shrink-0" />
                <p class="text-xs text-primary">
                  <strong>Tip:</strong> Los eventos se actualizan automáticamente. Usa los filtros para encontrar eventos específicos.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
