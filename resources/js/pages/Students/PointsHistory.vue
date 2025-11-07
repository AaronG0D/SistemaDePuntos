<template>
    <StudentLayout :student="student">
        <!-- Hero Section -->
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-800 dark:via-indigo-800 dark:to-purple-800 px-6 py-16 sm:px-8 lg:px-12">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25% 25%, white 2px, transparent 2px), radial-gradient(circle at 75% 75%, white 2px, transparent 2px); background-size: 50px 50px;"></div>
            </div>
            
            <div class="relative mx-auto max-w-7xl">
                <div class="flex items-center justify-between">
                    <div class="text-center lg:text-left">
                        <div class="flex items-center gap-4">
                            <div class="rounded-full bg-white/20 p-4 backdrop-blur-sm">
                                <Coins class="h-12 w-12 text-white" />
                            </div>
                            <h1 class="text-4xl font-bold text-white sm:text-5xl lg:text-6xl">
                                Mis Puntos
                            </h1>
                        </div>
                        <p class="mt-4 text-xl text-blue-100">{{ student.nombres }} {{ student.apellidos }}</p>
                        <div class="mt-8 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                                <div class="text-3xl font-bold text-white">{{ totalPoints }}</div>
                                <div class="text-sm text-blue-100">Puntos Totales</div>
                            </div>
                            <div class="rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                                <div class="text-3xl font-bold text-white">{{ totalDeposits }}</div>
                                <div class="text-sm text-blue-100">Depósitos</div>
                            </div>
                            <div class="rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                                <div class="text-3xl font-bold text-white">{{ averagePointsPerDeposit }}</div>
                                <div class="text-sm text-blue-100">Promedio/Depósito</div>
                            </div>
                        </div>
                    </div>
                    <div class="hidden lg:block">
                        <div class="relative">
                            <div class="absolute inset-0 animate-pulse rounded-full bg-white/20"></div>
                            <div class="relative rounded-full bg-white/10 p-8 backdrop-blur-sm">
                                <BarChart3 class="h-20 w-20 text-white" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="px-6 py-8 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-7xl space-y-6">
                <!-- Estado vacío general -->
                <div v-if="(!deposits || deposits.length === 0) && (!periods || periods.length === 0)" class="text-center py-16">
                    <div class="mx-auto max-w-md">
                        <Leaf class="mx-auto h-16 w-16 text-gray-400 mb-4" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No hay datos disponibles</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            Aún no tienes depósitos registrados o no hay períodos académicos configurados.
                        </p>
                        <Button @click="router.visit(route('students.dashboard'))" class="bg-green-600 hover:bg-green-700">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver al Dashboard
                        </Button>
                    </div>
                </div>

                <!-- Contenido normal -->
                <div v-else>

                <!-- Points Summary by Bimester -->
                <div class="grid gap-6 md:grid-cols-2" :class="[
                    bimesters.length === 1 ? 'lg:grid-cols-1 max-w-md mx-auto' : '',
                    bimesters.length === 2 ? 'lg:grid-cols-2' : '',
                    bimesters.length === 3 ? 'lg:grid-cols-3' : '',
                    bimesters.length >= 4 ? 'lg:grid-cols-4' : ''
                ]">
                    <Card
                        v-for="bimester in bimesters"
                        :key="bimester.number"
                        class="group cursor-pointer transition-all duration-300 hover:shadow-xl hover:-translate-y-1"
                        :class="[
                            selectedBimester === bimester.number 
                                ? 'ring-2 ring-blue-500 shadow-lg border-blue-300 dark:border-blue-600' 
                                : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600'
                        ]"
                        @click="selectedBimester = bimester.number"
                    >
                        <CardContent class="p-6">
                            <div class="text-center">
                                <div class="mb-4 relative">
                                    <div 
                                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-full transition-all duration-300 group-hover:scale-110"
                                        :class="[
                                            selectedBimester === bimester.number
                                                ? 'bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-lg'
                                                : 'bg-gradient-to-br from-green-100 to-green-200 dark:from-green-800 dark:to-green-700 text-green-600 dark:text-green-300'
                                        ]"
                                    >
                                        <span class="text-xl font-bold">{{ bimester.number }}</span>
                                    </div>
                                    <!-- Progress Ring -->
                                    <div class="absolute inset-0 -m-1">
                                        <svg class="h-18 w-18 transform -rotate-90" viewBox="0 0 36 36">
                                            <path
                                                class="text-gray-200 dark:text-gray-700"
                                                d="M18 2.0845
                                                a 15.9155 15.9155 0 0 1 0 31.831
                                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            />
                                            <path
                                                :class="[
                                                    selectedBimester === bimester.number
                                                        ? 'text-blue-500'
                                                        : 'text-green-500'
                                                ]"
                                                :stroke-dasharray="`${getBimesterProgress(bimester.number)}, 100`"
                                                d="M18 2.0845
                                                a 15.9155 15.9155 0 0 1 0 31.831
                                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                class="transition-all duration-500"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">{{ bimester.name }}</h3>
                                <div class="space-y-2">
                                    <div>
                                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                            {{ getBimesterPoints(bimester.number) }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">puntos</div>
                                    </div>
                                    <div class="flex items-center justify-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="flex items-center">
                                            <Trash2 class="mr-1 h-3 w-3" />
                                            {{ getBimesterDeposits(bimester.number) }}
                                        </span>
                                        
                                    </div>
                                </div>
                                <!-- Selection Indicator -->
                                <div 
                                    v-if="selectedBimester === bimester.number"
                                    class="mt-3 flex items-center justify-center"
                                >
                                    <div class="rounded-full bg-blue-100 dark:bg-blue-900 px-3 py-1">
                                        <span class="text-xs font-medium text-blue-600 dark:text-blue-300">Seleccionado</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Detailed View -->
                <Card class="border-green-200 dark:border-green-700" v-if="selectedBimester">
                    <CardHeader>
                        <CardTitle class="flex items-center text-green-800 dark:text-green-300">
                            <BarChart3 class="mr-2 h-5 w-5" />
                            Detalle del {{ getBimesterName(selectedBimester) }}
                        </CardTitle>
                        <CardDescription> Depósitos realizados en este bimestre </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <!-- Summary Stats -->
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="rounded-lg bg-green-50 dark:bg-green-900/50 p-4 text-center">
                                    <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                                        {{ getBimesterPoints(selectedBimester) }}
                                    </div>
                                    <div class="text-sm text-green-600 dark:text-green-400">Puntos Totales</div>
                                </div>
                                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/50 p-4 text-center">
                                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                        {{ getBimesterDeposits(selectedBimester) }}
                                    </div>
                                    <div class="text-sm text-blue-600 dark:text-blue-400">Depósitos</div>
                                </div>
                                <div class="rounded-lg bg-purple-50 dark:bg-purple-900/50 p-4 text-center">
                                    <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ getBimesterWeight(selectedBimester) }}kg</div>
                                    <div class="text-sm text-purple-600 dark:text-purple-400">Peso Total</div>
                                </div>
                            </div>

                            <!-- Deposits List -->
                            <div class="space-y-3">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">Depósitos Realizados</h4>
                                <div class="space-y-2">
                                    <div
                                        v-for="deposit in getFilteredDeposits(selectedBimester)"
                                        :key="deposit.id"
                                        class="flex items-center justify-between rounded-lg border border-green-100 dark:border-green-700 bg-green-50/50 dark:bg-green-900/30 p-4 transition-all cursor-pointer hover:bg-green-100 dark:hover:bg-green-800/50 hover:shadow-md hover:scale-[1.02]"
                                        @click="showDepositDetails(deposit)"
                                    >
                                        <div class="flex items-center space-x-4">
                                            <div class="rounded-full bg-green-100 p-2">
                                                <Trash2 class="h-4 w-4 text-green-600" />
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ deposit.tipo_basura?.nombre }}
                                                </p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ formatDate(deposit.fecha_deposito) }}
                                                </p>
                                                <p v-if="deposit.basurero?.nombre" class="text-xs text-blue-600 dark:text-blue-400 flex items-center gap-1">
                                                    <MapPin class="h-3 w-3" />
                                                    {{ deposit.basurero.nombre }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-green-600">+{{ deposit.puntaje_obtenido }} pts</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ deposit.cantidad }}kg</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500">Ver detalles →</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="getAllFilteredDeposits(selectedBimester).length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    <Leaf class="mx-auto mb-4 h-12 w-12 opacity-50" />
                                    <p class="dark:text-gray-400">No hay depósitos registrados en este bimestre</p>
                                </div>

                                <!-- Paginación -->
                                <div v-if="getTotalPages(selectedBimester) > 1" class="mt-6 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <span>
                                            Mostrando {{ ((currentPage - 1) * itemsPerPage) + 1 }} - 
                                            {{ Math.min(currentPage * itemsPerPage, getAllFilteredDeposits(selectedBimester).length) }} 
                                            de {{ getAllFilteredDeposits(selectedBimester).length }} depósitos
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="prevPage"
                                            :disabled="currentPage === 1"
                                            class="h-8 w-8 p-0"
                                        >
                                            <ChevronLeft class="h-4 w-4" />
                                        </Button>
                                        
                                        <div class="flex items-center space-x-1">
                                            <Button
                                                v-for="page in getTotalPages(selectedBimester)"
                                                :key="page"
                                                variant="outline"
                                                size="sm"
                                                @click="goToPage(page)"
                                                :class="[
                                                    'h-8 w-8 p-0',
                                                    currentPage === page 
                                                        ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700' 
                                                        : 'hover:bg-gray-100 dark:hover:bg-gray-700'
                                                ]"
                                            >
                                                {{ page }}
                                            </Button>
                                        </div>
                                        
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="nextPage(selectedBimester)"
                                            :disabled="currentPage === getTotalPages(selectedBimester)"
                                            class="h-8 w-8 p-0"
                                        >
                                            <ChevronRight class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Progress Chart -->
                <Card class="border-indigo-200 dark:border-indigo-700">
                    <CardHeader>
                        <CardTitle class="flex items-center text-indigo-800 dark:text-indigo-300">
                            <TrendingUp class="mr-2 h-5 w-5" />
                            Evolución por Bimestre
                        </CardTitle>
                        <CardDescription class="dark:text-gray-400">Tu progreso a lo largo del año académico</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-6">
                            <div v-for="bimester in bimesters" :key="bimester.number" class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div 
                                            class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
                                            :class="[
                                                getBimesterPoints(bimester.number) > 0
                                                    ? 'bg-gradient-to-br from-indigo-400 to-indigo-600 text-white'
                                                    : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400'
                                            ]"
                                        >
                                            {{ bimester.number }}
                                        </div>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ bimester.name }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ getBimesterPoints(bimester.number) }}
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">pts</span>
                                    </div>
                                </div>
                                
                                <!-- Custom Progress Bar -->
                                <div class="relative h-4 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div 
                                        class="h-full rounded-full transition-all duration-700 ease-out"
                                        :class="[
                                            getBimesterPoints(bimester.number) > 0
                                                ? 'bg-gradient-to-r from-indigo-500 to-purple-600'
                                                : 'bg-gray-300 dark:bg-gray-600'
                                        ]"
                                        :style="`width: ${getProgressPercentage(bimester.number)}%`"
                                    ></div>
                                    <div v-if="getBimesterPoints(bimester.number) > 0" class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-xs font-medium text-white">{{ getProgressPercentage(bimester.number) }}%</span>
                                    </div>
                                </div>
                                
                                <!-- Additional Stats -->
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center">
                                        <Trash2 class="mr-1 h-3 w-3" />
                                        {{ getBimesterDeposits(bimester.number) }} depósitos
                                    </span>
                                    <span class="flex items-center">
                                        <Weight class="mr-1 h-3 w-3" />
                                        {{ getBimesterWeight(bimester.number) }}kg reciclados
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Achievements -->
                <Card class="border-yellow-200 dark:border-yellow-700 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/50 dark:to-orange-900/50">
                    <CardHeader>
                        <CardTitle class="flex items-center text-yellow-800 dark:text-yellow-300">
                            <Award class="mr-2 h-5 w-5" />
                            Logros Ecológicos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="achievement in achievements"
                                :key="achievement.id"
                                class="flex items-center space-x-3 rounded-lg border border-yellow-200 dark:border-yellow-600 bg-white dark:bg-gray-800 p-4"
                                :class="achievement.earned ? 'opacity-100' : 'opacity-50'"
                            >
                                <div class="rounded-full p-2" :class="achievement.earned ? 'bg-yellow-100 dark:bg-yellow-800' : 'bg-gray-100 dark:bg-gray-700'">
                                    <component 
                                        :is="achievement.icon" 
                                        class="h-6 w-6" 
                                        :class="achievement.earned ? 'text-yellow-600 dark:text-yellow-300' : 'text-gray-400 dark:text-gray-500'"
                                    />
                                </div>
                                <div>
                                    <p class="font-medium" :class="achievement.earned ? 'text-yellow-800 dark:text-yellow-300' : 'text-gray-500 dark:text-gray-400'">
                                        {{ achievement.name }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ achievement.description }}</p>
                                </div>
                                <div v-if="achievement.earned" class="ml-auto">
                                    <CheckCircle class="h-5 w-5 text-green-500" />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                </div> <!-- Cierre del div v-else -->
            </div>
        </div>

        <!-- Modal Flotante SIN Fondo Oscuro -->
        <div v-if="selectedDeposit" class="fixed top-20 right-8 z-50 w-96 max-h-[80vh] overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-green-200 dark:border-green-700 transform transition-all duration-300">
                <div class="p-6">
                    <!-- Header del Modal -->
                    <div class="flex items-center justify-between mb-6 border-b border-green-200 pb-4">
                        <h3 class="text-xl font-bold text-green-800 dark:text-green-300">Detalles del Depósito</h3>
                        <button @click="selectedDeposit = null" class="text-green-400 dark:text-green-500 hover:text-green-600 dark:hover:text-green-400 transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Información Principal -->
                        <Card class="border-green-200 dark:border-green-700">
                            <CardHeader class="pb-3">
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4 text-green-600 dark:text-green-400" />
                                    <CardTitle class="text-green-800 dark:text-green-300 text-sm">Información del Depósito</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Fecha:</span>
                                    <span class="font-medium dark:text-gray-200">{{ formatDate(selectedDeposit.fecha_deposito) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Hora:</span>
                                    <span class="font-medium dark:text-gray-200">{{ formatTime(selectedDeposit.fecha_deposito) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Cantidad:</span>
                                    <span class="font-medium dark:text-gray-200">{{ selectedDeposit.cantidad }} kg</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Bimestre:</span>
                                    <span class="font-medium dark:text-gray-200">{{ selectedDeposit.bimestre }}°</span>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Tipo de Residuo -->
                        <Card class="border-green-200 dark:border-green-700">
                            <CardHeader class="pb-3">
                                <div class="flex items-center gap-2">
                                    <Trash2 class="h-4 w-4 text-green-600 dark:text-green-400" />
                                    <CardTitle class="text-green-800 dark:text-green-300 text-sm">Tipo de Residuo</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Tipo:</span>
                                    <span class="font-medium dark:text-gray-200">{{ selectedDeposit.tipo_basura?.nombre }}</span>
                                </div>
                                <div v-if="selectedDeposit.tipo_basura?.descripcion">
                                    <span class="text-gray-600 dark:text-gray-400">Descripción:</span>
                                    <p class="font-medium dark:text-gray-200 text-xs mt-1">{{ selectedDeposit.tipo_basura?.descripcion }}</p>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Puntos Base:</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">{{ selectedDeposit.tipo_basura?.puntos_base }}</span>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Información del Basurero -->
                        <Card class="border-green-200 dark:border-green-700">
                            <CardHeader class="pb-3">
                                <div class="flex items-center gap-2">
                                    <MapPin class="h-4 w-4 text-green-600 dark:text-green-400" />
                                    <CardTitle class="text-green-800 dark:text-green-300 text-sm">Basurero</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Nombre:</span>
                                    <span class="font-medium dark:text-gray-200">{{ selectedDeposit.basurero?.nombre }}</span>
                                </div>
                                <div v-if="selectedDeposit.basurero?.ubicacion">
                                    <span class="text-gray-600 dark:text-gray-400">Ubicación:</span>
                                    <p class="font-medium dark:text-gray-200 text-xs mt-1">{{ selectedDeposit.basurero?.ubicacion }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Resumen de Puntos -->
                        <Card class="border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/30">
                            <CardHeader class="pb-3">
                                <div class="flex items-center gap-2">
                                    <Trophy class="h-4 w-4 text-green-600 dark:text-green-400" />
                                    <CardTitle class="text-green-800 dark:text-green-300 text-sm">Resumen</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ selectedDeposit.puntaje_obtenido }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Puntos Obtenidos</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-2">{{ selectedDeposit.periodo?.nombre || 'Sin período' }}</div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>

    </StudentLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BarChart3,
    Calendar,
    CheckCircle,
    ChevronLeft,
    ChevronRight,
    Clock,
    Coins,
    Filter,
    Leaf,
    MapPin,
    Recycle,
    Shield,
    Star,
    Trophy,
    TrendingUp,
    Trash2,
    Weight,
    X,
    Zap,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    student: {
        id: number;
        nombres: string;
        apellidos: string;
        codigo_estudiante?: string;
        curso?: {
            id?: number;
            nombre: string;
        };
        paralelo?: {
            id?: number;
            nombre: string;
        };
    };
    deposits: Array<{
        id: number;
        fecha_deposito: string;
        cantidad: number;
        puntaje_obtenido: number;
        bimestre: number;
        periodo_id?: number;
        tipo_basura?: {
            id?: number;
            nombre: string;
            descripcion?: string;
            puntos_base?: number;
        };
        basurero?: {
            id?: number;
            nombre: string;
            ubicacion?: string;
            descripcion?: string;
        };
        periodo?: {
            nombre: string;
        };
    }>;
    periods: Array<{
        id: number;
        nombre: string;
    }>;
    totalPoints: number;
    pointsByPeriod?: Record<number, {
        periodo_id: number;
        periodo_nombre: string;
        bimestre?: number;
        puntos: number;
        fecha_asignacion?: string;
        comentario?: string;
    }>;
}

const props = defineProps<Props>();

const selectedBimester = ref(1);
const selectedDeposit = ref<any>(null);
const currentPage = ref(1);
const itemsPerPage = 5; // Mostrar 5 depósitos por página

// Generar bimestres dinámicamente basados en los períodos disponibles
const bimesters = computed(() => {
    if (!props.pointsByPeriod || Object.keys(props.pointsByPeriod).length === 0) {
        // Fallback: mostrar solo los bimestres que tienen depósitos
        const bimestresConDepositos = new Set<number>();
        if (props.deposits) {
            props.deposits.forEach(d => {
                if (d.bimestre) bimestresConDepositos.add(d.bimestre);
            });
        }
        
        const bimesterNames = ['Primer Bimestre', 'Segundo Bimestre', 'Tercer Bimestre', 'Cuarto Bimestre'];
        return Array.from(bimestresConDepositos)
            .sort((a, b) => a - b)
            .map(num => ({ number: num, name: bimesterNames[num - 1] || `Bimestre ${num}` }));
    }
    
    // Crear bimestres basados en los períodos que tienen puntos
    const bimesterNames = ['Primer Bimestre', 'Segundo Bimestre', 'Tercer Bimestre', 'Cuarto Bimestre'];
    const bimestresUnicos = new Set<number>();
    
    Object.values(props.pointsByPeriod).forEach(period => {
        if (period.bimestre) {
            bimestresUnicos.add(period.bimestre);
        }
    });
    
    return Array.from(bimestresUnicos)
        .sort((a, b) => a - b)
        .map(num => ({ number: num, name: bimesterNames[num - 1] || `Bimestre ${num}` }));
});

// Computed properties for hero section
const totalDeposits = computed(() => {
    return props.deposits ? props.deposits.length : 0;
});

const averagePointsPerDeposit = computed(() => {
    if (!props.deposits || props.deposits.length === 0) return 0;
    return Math.round(props.totalPoints / props.deposits.length);
});

// Resetear página cuando cambie el bimestre
watch(selectedBimester, () => {
    currentPage.value = 1;
});

const achievements = ref([
    {
        id: 1,
        name: 'Primer Reciclador',
        description: 'Realizaste tu primer depósito',
        icon: Leaf,
        earned: computed(() => props.deposits && props.deposits.length > 0),
    },
    {
        id: 2,
        name: 'Eco Guerrero',
        description: 'Alcanzaste 100 puntos',
        icon: Zap,
        earned: computed(() => props.totalPoints >= 100),
    },
    {
        id: 3,
        name: 'Guardián Verde',
        description: 'Realizaste 10 depósitos',
        icon: Shield,
        earned: computed(() => props.deposits && props.deposits.length >= 10),
    },
    {
        id: 4,
        name: 'Campeón Ecológico',
        description: 'Alcanzaste 300 puntos',
        icon: Trophy,
        earned: computed(() => props.totalPoints >= 300),
    },
    {
        id: 5,
        name: 'Constancia Verde',
        description: 'Realizaste depósitos en todos los bimestres',
        icon: Calendar,
        earned: computed(() => {
            if (!props.deposits || props.deposits.length === 0) return false;
            
            // Verificar que tenga depósitos en los 4 bimestres
            const bimestresConDepositos = new Set();
            
            // Revisar cada depósito y usar su campo bimestre
            for (const deposit of props.deposits) {
                if (deposit.bimestre && deposit.bimestre >= 1 && deposit.bimestre <= 3) {
                    bimestresConDepositos.add(deposit.bimestre);
                }
            }
            
            // Debug: mostrar información en consola
            console.log('Constancia Verde Debug:', {
                totalDeposits: props.deposits.length,
                bimestresEncontrados: Array.from(bimestresConDepositos).sort(),
                depositsPerBimester: props.deposits.map(d => ({ bimestre: d.bimestre, fecha: d.fecha_deposito })),
                logrado: bimestresConDepositos.size >= 3
            });
            
            // Debe tener depósitos en los 4 bimestres
            return bimestresConDepositos.size >= 3;
        }),
    },
]);

const getBimesterPoints = (bimester: number) => {
    if (!props.pointsByPeriod) return 0;

    // 1) Preferir coincidencia directa por número de bimestre si viene desde backend
    for (const periodData of Object.values(props.pointsByPeriod)) {
        if (periodData.bimestre === bimester) {
            return periodData.puntos;
        }
    }

    // 2) Fallback a coincidencia por nombre si no existe el campo bimestre
    const bimesterNames = ['primer', 'segundo', 'tercer'];
    const bimesterName = bimesterNames[bimester - 1];
    for (const periodData of Object.values(props.pointsByPeriod)) {
        const periodName = (periodData.periodo_nombre || '').toLowerCase();
        if (periodName.includes(bimesterName)) {
            return periodData.puntos;
        }
    }

    return 0;
};

// Función auxiliar para mostrar detalles del depósito
const showDepositDetails = (deposit: any) => {
    selectedDeposit.value = deposit;
};

const getBimesterDeposits = (bimester: number) => {
    if (!props.deposits || props.deposits.length === 0) return 0;
    return props.deposits.filter((d) => d.bimestre === bimester).length;
};

const getBimesterWeight = (bimester: number) => {
    if (!props.deposits || props.deposits.length === 0) return '0.0';
    return props.deposits
        .filter((d) => d.bimestre === bimester)
        .reduce((sum, d) => sum + d.cantidad, 0)
        .toFixed(1);
};

// Function for progress rings (based on 100 points goal per bimester)
const getBimesterProgress = (bimester: number) => {
    const bimesterPoints = getBimesterPoints(bimester);
    const goalPoints = 100; // Meta de 100 puntos por bimestre
    return Math.round(Math.min((bimesterPoints / goalPoints) * 100, 100));
};

const getBimesterName = (bimester: number) => {
    return bimesters.value.find((b) => b.number === bimester)?.name || '';
};

const getFilteredDeposits = (bimester: number) => {
    if (!props.deposits || props.deposits.length === 0) return [];
    const filtered = props.deposits
        .filter((d) => d.bimestre === bimester)
        .sort((a, b) => new Date(b.fecha_deposito).getTime() - new Date(a.fecha_deposito).getTime());
    
    // Aplicar paginación
    const startIndex = (currentPage.value - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    return filtered.slice(startIndex, endIndex);
};

const getAllFilteredDeposits = (bimester: number) => {
    if (!props.deposits || props.deposits.length === 0) return [];
    return props.deposits
        .filter((d) => d.bimestre === bimester)
        .sort((a, b) => new Date(b.fecha_deposito).getTime() - new Date(a.fecha_deposito).getTime());
};

const getTotalPages = (bimester: number) => {
    const totalDeposits = getAllFilteredDeposits(bimester).length;
    return Math.ceil(totalDeposits / itemsPerPage);
};

const goToPage = (page: number) => {
    currentPage.value = page;
};

const nextPage = (bimester: number) => {
    if (currentPage.value < getTotalPages(bimester)) {
        currentPage.value++;
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

const getProgressPercentage = (bimester: number) => {
    const bimesterPoints = getBimesterPoints(bimester);
    const goalPoints = 100; // Meta de 100 puntos por bimestre
    return Math.round(Math.min((bimesterPoints / goalPoints) * 100, 100));
};

const formatDate = (dateString: string) => {
    try {
        return new Date(dateString).toLocaleDateString('es-ES', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    } catch (error) {
        console.error('Error formatting date:', dateString, error);
        return 'Fecha inválida';
    }
};

const formatTime = (dateString: string) => {
    try {
        return new Date(dateString).toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch (error) {
        console.error('Error formatting time:', dateString, error);
        return 'Hora inválida';
    }
};
</script>

<style scoped>
/* Ocultar scrollbar del navegador */
:deep(html) {
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

:deep(html::-webkit-scrollbar) {
    display: none; /* Chrome, Safari and Opera */
}

:deep(body) {
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

:deep(body::-webkit-scrollbar) {
    display: none; /* Chrome, Safari and Opera */
}
</style>