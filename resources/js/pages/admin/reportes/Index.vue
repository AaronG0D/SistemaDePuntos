<template>
    <Head title="Reportes" />
    <AppLayout>
        <div class="container mx-auto py-6">
            <div class="mb-8">
                <h1 class="flex items-center gap-3 text-3xl font-bold">
                    <FileText class="h-8 w-8 text-emerald-600" />
                    Reportes de Gestión de Residuos
                </h1>
                <p class="text-muted-foreground mt-2">Estadísticas y reportes del sistema de gestión de residuos</p>
            </div>

            <!-- Panel de Filtros -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon name="filter" class="h-5 w-5" />
                        Configuración del Reporte
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Selección de tipo de reporte -->
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium">Tipo de Reporte</label>
                        <Select v-model="tipoReporte">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar tipo de reporte" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="depositos">Reporte de Depósitos</SelectItem>
                                <SelectItem value="ranking">Ranking</SelectItem>
                                <SelectItem value="estudiante">Evolución de Estudiante</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filtros Específicos -->
                    <div v-if="tipoReporte === 'depositos'" class="space-y-4">
                        <!-- Selector de Modo de Fechas (Compacto) -->
                        <div class="mb-4 flex items-center gap-4 rounded-lg border p-2 bg-muted/20">
                            <span class="text-sm font-medium">Filtrar por:</span>
                            <div class="flex items-center space-x-2">
                                <input type="radio" id="modo_periodo_depositos" value="periodo" v-model="modo_seleccion_fecha" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                <label for="modo_periodo_depositos" class="text-sm text-gray-700 dark:text-gray-300">Periodo Académico</label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="radio" id="modo_rango_depositos" value="rango" v-model="modo_seleccion_fecha" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                <label for="modo_rango_depositos" class="text-sm text-gray-700 dark:text-gray-300">Rango de Fechas</label>
                            </div>
                        </div>

                        <div v-if="modo_seleccion_fecha === 'periodo'" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Año</label>
                                <Select v-model="anio_filtro_estudiante">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar Año" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="year in availableYears" :key="year" :value="year">
                                            {{ year }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Periodo Académico</label>
                                <Select v-model="periodo_estudiante_seleccionado">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar periodo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="custom">Seleccione un periodo...</SelectItem>
                                        <SelectItem v-for="periodo in filteredPeriodosEstudiante" :key="periodo.idPeriodo" :value="periodo.idPeriodo">
                                            {{ periodo.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div v-if="modo_seleccion_fecha === 'rango'" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Fecha Inicio</label>
                                <Input type="date" v-model="filtros.fecha_inicio" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Fecha Fin</label>
                                <Input type="date" v-model="filtros.fecha_fin" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium">Tipo de Residuo</label>
                            <div class="relative">
                                <button type="button" @click="toggleResiduosDropdown" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                    <span class="truncate">
                                        {{ selectedResiduosLabel }}
                                    </span>
                                    <Icon name="chevron-down" class="h-4 w-4 opacity-50" />
                                </button>
                                
                                <div v-if="showResiduosDropdown" class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover text-popover-foreground shadow-md bg-white dark:bg-gray-800 dark:border-gray-700">
                                    <div class="p-1">
                                        <div 
                                            class="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-gray-100 dark:hover:bg-gray-700 data-[disabled]:pointer-events-none data-[disabled]:opacity-50 cursor-pointer"
                                            @click="toggleAllResiduos"
                                        >
                                            <div class="mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary" :class="{'bg-blue-600 text-white border-blue-600': isAllResiduosSelected, 'border-gray-300 dark:border-gray-500': !isAllResiduosSelected}">
                                                <Icon v-if="isAllResiduosSelected" name="check" class="h-4 w-4" />
                                            </div>
                                            <span class="dark:text-gray-200">Todos los tipos</span>
                                        </div>
                                        
                                        <div 
                                            v-for="tipo in tiposResiduos" 
                                            :key="tipo.id" 
                                            class="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-gray-100 dark:hover:bg-gray-700 data-[disabled]:pointer-events-none data-[disabled]:opacity-50 cursor-pointer"
                                            @click="toggleResiduo(tipo.id)"
                                        >
                                            <div class="mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary" :class="{'bg-blue-600 text-white border-blue-600': filtros.tipo_residuo_id.includes(tipo.id), 'border-gray-300 dark:border-gray-500': !filtros.tipo_residuo_id.includes(tipo.id)}">
                                                <Icon v-if="filtros.tipo_residuo_id.includes(tipo.id)" name="check" class="h-4 w-4" />
                                            </div>
                                            <span class="dark:text-gray-200">{{ tipo.nombre }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <p v-if="dateRangeError" class="text-sm text-red-500">{{ dateRangeError }}</p>
                        <p v-else class="text-xs text-gray-500">
                            <span v-if="modo_seleccion_fecha === 'rango'">* Si no seleccionas fechas, se mostrarán los últimos 3 meses por defecto.</span>
                            <span v-else>* Selecciona un periodo académico para filtrar.</span>
                        </p>
                    </div>

                    <!-- Filtros para Ranking (Unificado) -->
                    <div v-else-if="tipoReporte === 'ranking'" class="space-y-4">
                        <!-- Selector de Tipo de Ranking -->
                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium">Mostrar ranking de:</label>
                            <div class="flex gap-4">
                                <div class="flex items-center space-x-2">
                                    <input type="radio" id="tipo_estudiantes" value="estudiantes" v-model="tipoRanking" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                    <label for="tipo_estudiantes" class="text-sm font-medium text-gray-700 dark:text-gray-300">Estudiantes</label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <input type="radio" id="tipo_cursos" value="cursos" v-model="tipoRanking" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                    <label for="tipo_cursos" class="text-sm font-medium text-gray-700 dark:text-gray-300">Cursos</label>
                                </div>
                            </div>
                        </div>
                        <!-- Selector de Modo de Fechas -->
                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium">Seleccionar Periodo por:</label>
                            <div class="flex gap-4">
                                <div class="flex items-center space-x-2">
                                    <input type="radio" id="modo_filtros_rapidos_ranking" value="filtros_rapidos" v-model="modo_seleccion_fecha" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                    <label for="modo_filtros_rapidos_ranking" class="text-sm font-medium text-gray-700 dark:text-gray-300">Filtros Rápidos</label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <input type="radio" id="modo_periodo_ranking" value="periodo" v-model="modo_seleccion_fecha" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                    <label for="modo_periodo_ranking" class="text-sm font-medium text-gray-700 dark:text-gray-300">Periodo Académico</label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <input type="radio" id="modo_rango_ranking" value="rango" v-model="modo_seleccion_fecha" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                    <label for="modo_rango_ranking" class="text-sm font-medium text-gray-700 dark:text-gray-300">Rango de Fechas</label>
                                </div>
                            </div>
                        </div>

                        <div v-if="modo_seleccion_fecha === 'filtros_rapidos'" class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Filtro de Tiempo</label>
                                <Select v-model="filtro_tiempo_ranking">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar periodo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="semana">Semana Actual</SelectItem>
                                        <SelectItem value="mes">Mes Actual</SelectItem>
                                        <SelectItem value="anio">Año Actual</SelectItem>
                                        <SelectItem value="todos">Últimos 3 Meses</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div v-if="modo_seleccion_fecha === 'periodo'" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Año</label>
                                <Select v-model="anio_filtro_estudiante">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar Año" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="year in availableYears" :key="year" :value="year">
                                            {{ year }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Periodo Académico</label>
                                <Select v-model="periodo_estudiante_seleccionado">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar periodo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="custom">Seleccione un periodo...</SelectItem>
                                        <SelectItem v-for="periodo in filteredPeriodosEstudiante" :key="periodo.idPeriodo" :value="periodo.idPeriodo">
                                            {{ periodo.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div v-if="modo_seleccion_fecha === 'rango'" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Fecha Inicio</label>
                                <Input type="date" v-model="filtros.fecha_inicio" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Fecha Fin</label>
                                <Input type="date" v-model="filtros.fecha_fin" />
                            </div>
                        </div>
                        
                        <p v-if="dateRangeError" class="text-sm text-red-500">{{ dateRangeError }}</p>
                        <p v-else class="text-xs text-gray-500">
                            <span v-if="modo_seleccion_fecha === 'filtros_rapidos'">* Selecciona un filtro rápido de tiempo.</span>
                            <span v-else-if="modo_seleccion_fecha === 'rango'">* Si no seleccionas fechas, se mostrarán los últimos 3 meses por defecto.</span>
                            <span v-else>* Selecciona un periodo académico para filtrar.</span>
                        </p>
                    </div>

                    <!-- Filtros para Evolución Estudiante -->
                    <div v-else-if="tipoReporte === 'estudiante'" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Curso</label>
                                <select 
                                    v-model="filtros.curso_id"
                                    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="todos">Todos los cursos</option>
                                    <option v-for="curso in cursos" :key="curso.id" :value="String(curso.id)">
                                        {{ curso.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Paralelo</label>
                                <select 
                                    v-model="filtros.paralelo_id"
                                    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="todos">Todos los paralelos</option>
                                    <option v-for="paralelo in paralelos" :key="paralelo.id" :value="String(paralelo.id)">
                                        {{ paralelo.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Estudiante</label>
                                <select 
                                    v-model="filtros.estudiante_id"
                                    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Seleccionar estudiante</option>
                                    <option v-for="estudiante in filteredEstudiantes" :key="estudiante.id" :value="String(estudiante.id)">
                                        {{ estudiante.nombre_completo }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Selector de Modo de Fechas (Compacto) -->
                        <div class="mb-4 flex items-center gap-4 rounded-lg border p-2 bg-muted/20">
                            <span class="text-sm font-medium">Filtrar por:</span>
                            <div class="flex items-center space-x-2">
                                <input type="radio" id="modo_periodo" value="periodo" v-model="modo_seleccion_fecha" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                <label for="modo_periodo" class="text-sm text-gray-700 dark:text-gray-300">Periodo Académico</label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="radio" id="modo_rango" value="rango" v-model="modo_seleccion_fecha" class="h-4 w-4 text-blue-600 focus:ring-blue-500" />
                                <label for="modo_rango" class="text-sm text-gray-700 dark:text-gray-300">Rango de Fechas</label>
                            </div>
                        </div>

                        <!-- Selector de Periodo Académico para Estudiante -->
                        <div v-if="modo_seleccion_fecha === 'periodo'" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Año para Periodos</label>
                                <select 
                                    v-model="anio_filtro_estudiante"
                                    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Seleccionar Año</option>
                                    <option v-for="year in availableYears" :key="year" :value="year">
                                        {{ year }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Periodo Académico</label>
                                <select 
                                    v-model="periodo_estudiante_seleccionado"
                                    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="custom">Seleccione un periodo...</option>
                                    <option v-for="periodo in filteredPeriodosEstudiante" :key="periodo.idPeriodo" :value="String(periodo.idPeriodo)">
                                        {{ periodo.nombre }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div v-if="modo_seleccion_fecha === 'rango'" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium">Fecha Inicio</label>
                                <Input type="date" v-model="filtros.fecha_inicio" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Fecha Fin</label>
                                <Input type="date" v-model="filtros.fecha_fin" />
                            </div>
                        </div>
                        
                        <p v-if="dateRangeError" class="text-sm text-red-500">{{ dateRangeError }}</p>
                        <p v-else class="text-xs text-gray-500">
                            <span v-if="modo_seleccion_fecha === 'rango'">* Si no seleccionas fechas, se mostrarán los últimos 3 meses por defecto.</span>
                            <span v-else>* Selecciona un periodo académico para ver los datos de ese lapso.</span>
                        </p>
                    </div>







                    <!-- Período del Reporte (Resumen) -->
                    <div class="mt-6">
                        <PeriodoDisplay :tipo-reporte="tipoReporte" :tipo-ranking="tipoRanking" :periodo-nombre="periodoNombre" :filtros="filtros" :tipos-residuos="tiposResiduos" :basureros="basureros" :estudiantes="props.estudiantes" />
                    </div>

                    <!-- Botones de exportación -->
                    <div class="mt-6 flex gap-3">
                        <Button
                            @click="exportarPDF" 
                            :disabled="loading || !!dateRangeError || !canExport" 
                            class="flex items-center gap-2 bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <FileText class="mr-2 h-4 w-4" />
                            <span v-if="!loading">Exportar PDF</span>
                            <span v-else>Cargando...</span>
                        </Button>
                        
                        <!-- Mensaje de validación eliminado por solicitud -->
                    </div>
                </CardContent>
            </Card>

            <!-- Vista Previa de Depósitos con Estadísticas -->
            <Card v-if="tipoReporte === 'depositos'" class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Table2 class="h-5 w-5" />
                        Vista Previa de Depósitos
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Estadísticas Generales -->
                    <div v-if="estadisticasDepositos.total_depositos > 0" class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                        <Card>
                            <CardContent class="pt-6">
                                <div class="text-center">
                                    <p class="text-sm text-muted-foreground">Total Depósitos</p>
                                    <p class="text-2xl font-bold text-blue-600">{{ estadisticasDepositos.total_depositos }}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="pt-6">
                                <div class="text-center">
                                    <p class="text-sm text-muted-foreground">Total Puntos</p>
                                    <p class="text-2xl font-bold text-green-600">{{ estadisticasDepositos.total_puntos }}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="pt-6">
                                <div class="text-center">
                                    <p class="text-sm text-muted-foreground">Usuarios Únicos</p>
                                    <p class="text-2xl font-bold text-purple-600">{{ estadisticasDepositos.usuarios_unicos }}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="pt-6">
                                <div class="text-center">
                                    <p class="text-sm text-muted-foreground">Tipos de Residuo</p>
                                    <p class="text-2xl font-bold text-orange-600">{{ estadisticasDepositos.por_tipo.length }}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Tabla de Depósitos -->
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Usuario</TableHead>
                                    <TableHead>Tipo de Residuo</TableHead>
                                    <TableHead>Basurero</TableHead>
                                    <TableHead class="text-right">Puntos</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="loading">
                                    <TableCell colspan="5" class="text-center py-8">
                                        <div class="flex items-center justify-center">
                                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                                            <span class="ml-2">Cargando datos...</span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-else-if="depositosData.length === 0">
                                    <TableCell colspan="5" class="text-center py-8 text-muted-foreground">
                                        No hay depósitos para mostrar con los filtros seleccionados
                                    </TableCell>
                                </TableRow>
                                <TableRow v-else v-for="deposito in depositosData" :key="deposito.idDeposito">
                                    <TableCell>{{ formatDate(deposito.fechaHora) }}</TableCell>
                                    <TableCell>{{ deposito.user?.nombres }} {{ deposito.user?.primerApellido }}</TableCell>
                                    <TableCell>
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ deposito.tipo_basura?.nombre || deposito.tipoBasura?.nombre }}
                                        </span>
                                    </TableCell>
                                    <TableCell>{{ deposito.basurero?.ubicacion }}</TableCell>
                                    <TableCell class="text-right font-medium text-green-600">
                                        +{{ deposito.puntos_generados || 0 }} pts
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="paginacion.total > 0" class="mt-4 flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            Mostrando {{ paginacion.from }} - {{ paginacion.to }} de {{ paginacion.total }} registros
                        </p>
                        <div class="flex gap-2">
                            <Button 
                                @click="paginaAnterior" 
                                :disabled="paginacion.current_page === 1"
                                variant="outline"
                                size="sm"
                            >
                                Anterior
                            </Button>
                            <Button 
                                @click="paginaSiguiente" 
                                :disabled="paginacion.current_page === paginacion.last_page"
                                variant="outline"
                                size="sm"
                                >
                                    Siguiente
                            </Button>
                        </div>
                    </div>

                    <p class="mt-4 text-xs text-muted-foreground text-center">
                        💡 El PDF contendrá todos los {{ paginacion.total }} registros, no solo esta página
                    </p>
                </CardContent>
            </Card>

            <!-- Vista Previa de Ranking -->
            <Card v-if="tipoReporte === 'ranking' && rankingData.length > 0" class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Table2 class="h-5 w-5" />
                        Vista Previa de Ranking - {{ tipoRanking === 'estudiantes' ? 'Estudiantes' : 'Cursos' }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Estadísticas del Ranking -->
                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-lg border bg-card p-4">
                            <div class="text-2xl font-bold text-primary">{{ estadisticasRanking.total_registros || 0 }}</div>
                            <div class="text-sm text-muted-foreground">Total {{ tipoRanking === 'estudiantes' ? 'Estudiantes' : 'Cursos' }}</div>
                        </div>
                        <div class="rounded-lg border bg-card p-4">
                            <div class="text-2xl font-bold text-primary">{{ Math.round(estadisticasRanking.total_puntos || 0).toLocaleString() }}</div>
                            <div class="text-sm text-muted-foreground">Puntos Totales</div>
                        </div>
                        <div class="rounded-lg border bg-card p-4">
                            <div class="text-2xl font-bold text-primary">{{ estadisticasRanking.top_nombre || 'N/A' }}</div>
                            <div class="text-sm text-muted-foreground">{{ tipoRanking === 'estudiantes' ? '1º Estudiante' : '1º Curso' }}</div>
                        </div>
                    </div>

                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Posición</TableHead>
                                    <TableHead v-if="tipoRanking === 'estudiantes'">Estudiante</TableHead>
                                    <TableHead v-if="tipoRanking === 'cursos'">Curso</TableHead>
                                    <TableHead v-if="tipoRanking === 'cursos'">Estudiantes</TableHead>
                                    <TableHead class="text-right">Total Depósitos</TableHead>
                                    <TableHead class="text-right">Total Puntos</TableHead>
                                    <TableHead v-if="tipoRanking === 'cursos'" class="text-right">Promedio/Estudiante</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item, index) in rankingData" :key="index">
                                    <TableCell>
                                        <span :class="{'font-bold text-yellow-600': index === 0, 'font-bold text-gray-500': index === 1, 'font-bold text-orange-600': index === 2}">
                                            {{ index + 1 }}º
                                        </span>
                                    </TableCell>
                                    <TableCell v-if="tipoRanking === 'estudiantes'">
                                        {{ item.nombres }} {{ item.primerApellido }}
                                    </TableCell>
                                    <TableCell v-if="tipoRanking === 'cursos'">
                                        {{ item.nombre_completo }}
                                    </TableCell>
                                    <TableCell v-if="tipoRanking === 'cursos'">
                                        {{ item.cantidad_estudiantes }}
                                    </TableCell>
                                    <TableCell class="text-right">{{ item.total_depositos }}</TableCell>
                                    <TableCell class="text-right font-medium text-green-600">
                                        {{ item.total_puntos }} pts
                                    </TableCell>
                                    <TableCell v-if="tipoRanking === 'cursos'" class="text-right">
                                        {{ item.promedio_por_estudiante }} pts
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>


            <!-- Vista Previa de Estudiante -->
            <Card v-if="tipoReporte === 'estudiante' && filtros.estudiante_id" class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Table2 class="h-5 w-5" />
                        Vista Previa de Evolución del Estudiante
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Estadísticas de Evolución -->
                    <div v-if="estadisticasEvolucion.estudiante_nombre" class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <Card>
                            <CardContent class="pt-6">
                                <div class="text-center">
                                    <p class="text-sm text-muted-foreground">Estudiante</p>
                                    <p class="text-lg font-bold text-blue-600">{{ estadisticasEvolucion.estudiante_nombre }}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="pt-6">
                                <div class="text-center">
                                    <p class="text-sm text-muted-foreground">Total Depósitos</p>
                                    <p class="text-2xl font-bold text-green-600">{{ estadisticasEvolucion.total_depositos }}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="pt-6">
                                <div class="text-center">
                                    <p class="text-sm text-muted-foreground">Puntos Totales</p>
                                    <p class="text-2xl font-bold text-orange-600">{{ Math.round(estadisticasEvolucion.total_puntos) }} pts</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Tipo de Residuo</TableHead>
                                    <TableHead>Basurero</TableHead>
                                    <TableHead class="text-right">Puntos</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="deposito in estudianteData" :key="deposito.idDeposito">
                                    <TableCell>{{ formatDate(deposito.fechaHora) }}</TableCell>
                                    <TableCell>
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ deposito.tipo_basura?.nombre || deposito.tipoBasura?.nombre }}
                                        </span>
                                    </TableCell>
                                    <TableCell>{{ deposito.basurero?.ubicacion }}</TableCell>
                                    <TableCell class="text-right font-medium text-green-600">
                                        +{{ deposito.tipo_basura?.puntos || deposito.tipoBasura?.puntos }} pts
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="estudianteData.length === 0">
                                    <TableCell colspan="4" class="text-center py-8 text-muted-foreground">
                                        No hay registros de depósitos para este estudiante en el periodo seleccionado
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <p class="mt-4 text-sm text-muted-foreground text-center">
                        Total de registros: {{ estudianteData.length }}
                    </p>
                </CardContent>
            </Card>




            <!-- Estadísticas Generales (Ocultar si es reporte de estudiante) -->
            <div v-if="tipoReporte !== 'estudiante'" class="mb-8 grid gap-4 md:grid-cols-4">
                <Card class="border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium text-blue-900">
                            <Icon name="package" class="h-4 w-4" />
                            Total Depósitos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-900">{{ estadisticas.total_depositos.toLocaleString() }}</div>
                        <p class="mt-1 text-xs text-blue-700">Registros totales</p>
                    </CardContent>
                </Card>

                <Card class="border-green-200 bg-gradient-to-br from-green-50 to-green-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium text-green-900">
                            <Icon name="star" class="h-4 w-4" />
                            Total Puntos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-900">{{ estadisticas.total_puntos.toLocaleString() }}</div>
                        <p class="mt-1 text-xs text-green-700">Puntos acumulados</p>
                    </CardContent>
                </Card>

                <Card class="border-purple-200 bg-gradient-to-br from-purple-50 to-purple-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium text-purple-900">
                            <Icon name="recycle" class="h-4 w-4" />
                            Tipos de Residuos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-purple-900">{{ estadisticas.total_tipos_residuos }}</div>
                        <p class="mt-1 text-xs text-purple-700">Categorías activas</p>
                    </CardContent>
                </Card>

                <Card class="border-orange-200 bg-gradient-to-br from-orange-50 to-orange-100">
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm font-medium text-orange-900">
                            <Icon name="trash-2" class="h-4 w-4" />
                            Basureros Activos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-900">{{ estadisticas.total_basureros }}</div>
                        <p class="mt-1 text-xs text-orange-700">Ubicaciones activas</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Gráficos Estadísticos (Ocultar si es reporte de estudiante) -->
            <div v-if="tipoReporte !== 'estudiante'" class="grid gap-6 md:grid-cols-2">
                <!-- Gráfico de Depósitos por Tipo -->
                <Card class="transition hover:shadow-md">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="bar-chart-3" class="h-5 w-5" />
                            Depósitos por Tipo de Residuo
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64 w-full overflow-hidden">
                            <DepositosChart
                                v-if="datosGraficos?.porTipo?.labels?.length"
                                :data="{
                                    labels: datosGraficos.porTipo.labels || [],
                                    datasets: (datosGraficos.porTipo.datasets || []).map((dataset) => {
                                        const colorMap: Record<string, string> = {
                                            papel: '#f97316',
                                            plastico: '#3b82f6',
                                            metal: '#ef4444',
                                            biodegradable: '#22c55e',
                                            default: '#9ca3af',
                                        };
                                        const labels = datosGraficos.porTipo.labels || [];
                                        const normalize = (s: string) =>
                                            s
                                                .normalize('NFD')
                                                .replace(/[\u0300-\u036f]/g, '')
                                                .toLowerCase();
                                        const backgroundColors = labels.map((label) => colorMap[normalize(label)] || colorMap.default);

                                        return {
                                            label: dataset.label || 'Sin etiqueta',
                                            data: dataset.data || [],
                                            backgroundColor: backgroundColors,
                                            borderColor: '#4b5563',
                                            borderWidth: 2,
                                        };
                                    }),
                                }"
                                type="doughnut"
                                :options="{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        title: {
                                            display: false,
                                        },
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                boxWidth: 12,
                                                padding: 10,
                                                usePointStyle: true,
                                                color: '#d1d5db',
                                            },
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function (context: { label: string; raw: number }) {
                                                    return `${context.label}: ${context.raw}`;
                                                },
                                            },
                                            backgroundColor: getThemeColors().tooltipBg,
                                            titleColor: getThemeColors().tooltipText,
                                            bodyColor: getThemeColors().tooltipText,
                                            borderColor: getThemeColors().tooltipBorder,
                                            borderWidth: 1,
                                        },
                                    },
                                }"
                            />
                            <div v-else class="text-muted-foreground flex h-full items-center justify-center">
                                <p>No hay datos disponibles para mostrar</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Gráfico de Depósitos por Mes -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="trending-up" class="h-5 w-5" />
                            Tendencia de Depósitos (Últimos 6 meses)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64 w-full overflow-hidden">
                            <DepositosChart
                                v-if="datosGraficos?.porMes?.labels?.length"
                                :data="{
                                    labels: datosGraficos.porMes.labels || [],
                                    datasets: (datosGraficos.porMes.datasets || []).map((dataset) => ({
                                        label: dataset.label || 'Depósitos',
                                        data: dataset.data || [],
                                        borderColor: dataset.borderColor || '#1E40AF',
                                        borderWidth: dataset.borderWidth || 2,
                                        fill: false,
                                        tension: 0.1,
                                    })),
                                }"
                                type="line"
                                :options="{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        title: {
                                            display: false,
                                        },
                                        legend: {
                                            position: 'top',
                                            labels: {
                                                boxWidth: 12,
                                                padding: 10,
                                                usePointStyle: true,
                                                color: '#d1d5db',
                                            },
                                        },
                                        tooltip: {
                                            backgroundColor: '#111827',
                                            titleColor: '#F9FAFB',
                                            bodyColor: '#F9FAFB',
                                            borderColor: '#374151',
                                            borderWidth: 1,
                                        },
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1,
                                            },
                                        },
                                        x: {
                                            ticks: {
                                                maxRotation: 45,
                                            },
                                        },
                                    },
                                }"
                            />
                            <div v-else class="text-muted-foreground flex h-full items-center justify-center">
                                <p>No hay datos de tendencias disponibles</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
               
                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="trophy" class="h-5 w-5" />
                            Top 10 Usuarios con Más Puntos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64 w-full overflow-hidden">
                            <DepositosChart
                                v-if="datosGraficos?.topUsuarios?.labels?.length"
                                :data="{
                                    labels: datosGraficos.topUsuarios.labels || [],
                                    datasets: (datosGraficos.topUsuarios.datasets || []).map((dataset) => ({
                                        label: dataset.label || 'Puntos',
                                        data: dataset.data || [],
                                        backgroundColor: [
                                            '#3B82F6',
                                            '#10B981',
                                            '#F59E0B',
                                            '#EF4444',
                                            '#8B5CF6',
                                            '#06B6D4',
                                            '#84CC16',
                                            '#F97316',
                                            '#EC4899',
                                            '#6366F1',
                                        ],
                                        borderColor: dataset.borderColor || '#1E40AF',
                                        borderWidth: dataset.borderWidth || 1,
                                    })),
                                }"
                                type="bar"
                                :options="{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        title: {
                                            display: false,
                                        },
                                        legend: {
                                            display: false,
                                        },
                                        tooltip: {
                                            backgroundColor: '#111827',
                                            titleColor: '#FFFFFF',
                                            bodyColor: '#F9FAFB',
                                            borderColor: '#374151',
                                            borderWidth: 1,
                                        },
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1,
                                            },
                                        },
                                        x: {
                                            ticks: {
                                                maxRotation: 45,
                                            },
                                        },
                                    },
                                    indexAxis: 'y',
                                }"
                            />
                            <div v-else class="text-muted-foreground flex h-full items-center justify-center">
                                <p>No hay datos de usuarios disponibles</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import PeriodoDisplay from '@/components/PeriodoDisplay.vue';
import DepositosChart from '@/components/charts/DepositosChart.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Basurero, DatosGraficos, Estadisticas, TipoResiduo } from '@/types';
import { FileText, Table2 } from 'lucide-vue-next';
import { ref, watch, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from '../../../composables/useToast';

const props = withDefaults(
    defineProps<{
        estadisticas: Estadisticas;
        tiposResiduos?: TipoResiduo[];
        basureros?: Basurero[];
        periodosAcademicos?: any[];
        estudiantes?: any[];
        cursos?: any[];
        paralelos?: any[];
        datosGraficos: DatosGraficos;
    }>(),
    {
        tiposResiduos: () => [],
        basureros: () => [],
        periodosAcademicos: () => [],
        estudiantes: () => [],
        cursos: () => [],
        paralelos: () => [],
    },
);

const tipoReporte = ref('depositos');
const filtros = ref({
    tipo_residuo_id: [] as (number | string)[],
    fecha_inicio: undefined as string | undefined,
    fecha_fin: undefined as string | undefined,
    filtro: 'todos' as string | number, // Unificado: 'semana', 'mes', 'anio', 'todos' o ID
    anio_filtro: new Date().getFullYear() as number, // Para filtrar períodos
    estudiante_id: undefined as number | undefined,
    curso_id: 'todos' as string | number,
    paralelo_id: 'todos' as string | number,
});

const loading = ref(false);
const dateRangeError = ref('');
const { toast } = useToast();
const anio_filtro = ref(new Date().getFullYear().toString());
const anio_filtro_estudiante = ref(new Date().getFullYear().toString());
const periodo_estudiante_seleccionado = ref('custom');
const modo_seleccion_fecha = ref('rango'); // Cambiar a 'rango' por defecto para que cargue datos
const reportData = ref<any[]>([]);
const tipoRanking = ref('estudiantes'); // 'estudiantes' o 'cursos'

// Datos para la tabla de depósitos con paginación
const depositosData = ref<any[]>([]);
const paginacion = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0,
});
const estadisticasDepositos = ref({
    total_depositos: 0,
    total_puntos: 0,
    usuarios_unicos: 0,
    por_tipo: []
});

// Datos para otros reportes
const rankingData = ref<any[]>([]);
const estadisticasRanking = ref({
    total_registros: 0,
    total_puntos: 0,
    top_nombre: 'N/A'
});
const estudianteData = ref<any[]>([]);
const estadisticasEvolucion = ref({
    estudiante_nombre: '',
    total_depositos: 0,
    total_puntos: 0
});
const filtro_tiempo_ranking = ref('todos'); // 'semana', 'mes', 'anio', 'todos'

// Get unique years from academic periods
const availableYears = computed(() => {
    if (!props.periodosAcademicos || props.periodosAcademicos.length === 0) {
        return [new Date().getFullYear().toString()];
    }
    const years = new Set(props.periodosAcademicos.map(p => new Date(p.fecha_inicio).getFullYear().toString()));
    return Array.from(years).sort((a, b) => b.localeCompare(a));
});

// Filter periods by selected year for Ranking
const filteredPeriodos = computed(() => {
    if (!props.periodosAcademicos) return [];
    return props.periodosAcademicos.filter(p => 
        new Date(p.fecha_inicio).getFullYear().toString() === anio_filtro.value
    );
});



// Filter periods by selected year for Student Report
const filteredPeriodosEstudiante = computed(() => {
    if (!props.periodosAcademicos) return [];
    return props.periodosAcademicos.filter(p => 
        new Date(p.fecha_inicio).getFullYear().toString() === anio_filtro_estudiante.value
    );
});



// Multi-select logic for Residuos
const showResiduosDropdown = ref(false);

const toggleResiduosDropdown = () => {
    showResiduosDropdown.value = !showResiduosDropdown.value;
};

const toggleResiduo = (id: number | string) => {
    const index = filtros.value.tipo_residuo_id.indexOf(id);
    if (index === -1) {
        filtros.value.tipo_residuo_id.push(id);
    } else {
        filtros.value.tipo_residuo_id.splice(index, 1);
    }
};

const toggleAllResiduos = () => {
    if (isAllResiduosSelected.value) {
        filtros.value.tipo_residuo_id = [];
    } else {
        filtros.value.tipo_residuo_id = props.tiposResiduos.map(t => t.id);
    }
};

const isAllResiduosSelected = computed(() => {
    return props.tiposResiduos && props.tiposResiduos.length > 0 && filtros.value.tipo_residuo_id.length === props.tiposResiduos.length;
});

const selectedResiduosLabel = computed(() => {
    if (!filtros.value.tipo_residuo_id || filtros.value.tipo_residuo_id.length === 0) {
        return 'Seleccionar tipos...';
    }
    if (isAllResiduosSelected.value) {
        return 'Todos los tipos';
    }
    if (filtros.value.tipo_residuo_id.length === 1) {
        const id = filtros.value.tipo_residuo_id[0];
        const tipo = props.tiposResiduos.find(t => t.id === id);
        return tipo ? tipo.nombre : 'Desconocido';
    }
    return `${filtros.value.tipo_residuo_id.length} seleccionados`;
});

// Close dropdown when clicking outside (simple implementation)
// In a real app, use a directive or a proper component library
// For now, we rely on the toggle button.

// Watch for student period selection to update dates (only if in period mode)
watch(periodo_estudiante_seleccionado, (newVal) => {
    if (modo_seleccion_fecha.value === 'periodo') {
        // If period selected, we clear dates to ensure backend uses period_id
        filtros.value.fecha_inicio = undefined;
        filtros.value.fecha_fin = undefined;
    }
});

// Watch mode change
watch(modo_seleccion_fecha, (newVal) => {
    if (newVal === 'periodo') {
        filtros.value.fecha_inicio = undefined;
        filtros.value.fecha_fin = undefined;
    } else {
        periodo_estudiante_seleccionado.value = 'custom';
    }
});

// Filter students by curso and paralelo
const filteredEstudiantes = computed(() => {
    if (!props.estudiantes) return [];
    
    let filtered = props.estudiantes;
    
    // Filtrar por curso si está seleccionado (convertir ambos a string para comparar)
    if (filtros.value.curso_id && filtros.value.curso_id !== 'todos') {
        filtered = filtered.filter(est => String(est.curso_id) === String(filtros.value.curso_id));
    }
    
    // Filtrar por paralelo si está seleccionado (convertir ambos a string para comparar)
    if (filtros.value.paralelo_id && filtros.value.paralelo_id !== 'todos') {
        filtered = filtered.filter(est => String(est.paralelo_id) === String(filtros.value.paralelo_id));
    }
    
    // Filtrar estudiantes que tengan id válido
    return filtered.filter(est => est && est.id !== undefined && est.id !== null);
});

// Helper function to format dates
const formatDate = (dateString: string | undefined) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const periodoNombre = computed(() => {
    if (tipoReporte.value === 'ranking') {
        if (modo_seleccion_fecha.value === 'filtros_rapidos') {
             const map: Record<string, string> = {
                'semana': 'Esta Semana',
                'mes': 'Este Mes',
                'anio': 'Este Año',
                'todos': 'Todo el Tiempo'
             };
             return map[filtro_tiempo_ranking.value as string] || 'Periodo Desconocido';
        } else if (modo_seleccion_fecha.value === 'periodo') {
            if (periodo_estudiante_seleccionado.value === 'custom') return 'Periodo Personalizado';
            const periodo = filteredPeriodosEstudiante.value.find(p => String(p.idPeriodo) === String(periodo_estudiante_seleccionado.value));
            return periodo ? periodo.nombre : 'Periodo Desconocido';
        } else if (modo_seleccion_fecha.value === 'rango') {
            if (filtros.value.fecha_inicio && filtros.value.fecha_fin) {
                return `${formatDate(filtros.value.fecha_inicio)} - ${formatDate(filtros.value.fecha_fin)}`;
            }
            return 'Ultimos 3 meses';
        }
        return 'Todos los Periodos';
    }

    if (modo_seleccion_fecha.value === 'periodo') {
        if (periodo_estudiante_seleccionado.value === 'custom') return 'Periodo Personalizado';
        const periodo = filteredPeriodosEstudiante.value.find(p => String(p.idPeriodo) === String(periodo_estudiante_seleccionado.value));
        return periodo ? periodo.nombre : 'Periodo Desconocido';
    } else if (modo_seleccion_fecha.value === 'rango') {
        if (filtros.value.fecha_inicio && filtros.value.fecha_fin) {
            return `${formatDate(filtros.value.fecha_inicio)} - ${formatDate(filtros.value.fecha_fin)}`;
        }
        return 'Ultimos 3 meses';
    }
    return 'Todos los Periodos';
});

// Validation: Check if required fields are filled
const canExport = computed(() => {
    switch (tipoReporte.value) {

        case 'estudiante':
            return !!filtros.value.estudiante_id;
        case 'depositos':
            // Validación de tipo de basura requerida (al menos uno seleccionado)
            return filtros.value.tipo_residuo_id && filtros.value.tipo_residuo_id.length > 0;

        case 'ranking':
        default:
            return true;
    }
});

// validationMessage computed property removed as requested

// Validate date range (max 4 months)
const validateDateRange = () => {
    if (!filtros.value.fecha_inicio || !filtros.value.fecha_fin) {
        dateRangeError.value = '';
        return true;
    }

    const inicio = new Date(filtros.value.fecha_inicio);
    const fin = new Date(filtros.value.fecha_fin);
    
    // Calculate difference in months
    const diffMonths = (fin.getFullYear() - inicio.getFullYear()) * 12 + (fin.getMonth() - inicio.getMonth());
    
    if (diffMonths > 4) {
        dateRangeError.value = 'El rango de fechas no puede exceder 4 meses.';
        return false;
    }
    
    if (inicio > fin) {
        dateRangeError.value = 'La fecha de inicio no puede ser posterior a la fecha fin.';
        return false;
    }

    dateRangeError.value = '';
    return true;
};

// Watch for date changes
watch([() => filtros.value.fecha_inicio, () => filtros.value.fecha_fin], () => {
    validateDateRange();
});

// Función para detectar modo oscuro/claro
const isDarkMode = () => {
    if (typeof window === 'undefined') return true;
    return document.documentElement.classList.contains('dark');
};

// Colores adaptables según el tema
const getChartColors = () => {
    const dark = isDarkMode();
    return {
        backgroundColor: dark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)',
        borderColor: dark ? '#10B981' : '#059669',
        textColor: dark ? '#F9FAFB' : '#1F2937',
    };
};

// Función para cargar datos de depósitos con paginación
const cargarDatosDepositos = async (page = 1) => {
    if (tipoReporte.value !== 'depositos') return;
    
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('page', page.toString());
        
        // Agregar filtros
        // Agregar filtros
        if (filtros.value.tipo_residuo_id && filtros.value.tipo_residuo_id.length > 0) {
            filtros.value.tipo_residuo_id.forEach(id => params.append('tipo_residuo_id[]', id.toString()));
        }
        
        // Agregar periodo_id si está en modo periodo y seleccionado
        if (modo_seleccion_fecha.value === 'periodo' && periodo_estudiante_seleccionado.value !== 'custom') {
            params.append('periodo_id', periodo_estudiante_seleccionado.value.toString());
        } else {
            if (filtros.value.fecha_inicio) params.append('fecha_inicio', filtros.value.fecha_inicio);
            if (filtros.value.fecha_fin) params.append('fecha_fin', filtros.value.fecha_fin);
        }
        
        const url = `/admin/reportes/depositos?${params.toString()}`;
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error('Error al cargar datos');
        }
        
        const data = await response.json();
        depositosData.value = data.depositos || [];
        paginacion.value = data.pagination || paginacion.value;
        estadisticasDepositos.value = data.estadisticas || estadisticasDepositos.value;
        
    } catch (error) {
        toast({
            title: 'Error',
            description: 'No se pudieron cargar los datos del reporte',
            variant: 'destructive'
        });
    } finally {
        loading.value = false;
    }
};

// Debounce para evitar múltiples llamadas
let debounceTimer: ReturnType<typeof setTimeout> | null = null;
const cargarDatosDepositosDebounced = (page = 1) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        cargarDatosDepositos(page);
    }, 500);
};

// Funciones de paginación
const cambiarPagina = (page: number) => {
    if (page >= 1 && page <= paginacion.value.last_page) {
        cargarDatosDepositos(page);
    }
};

const paginaAnterior = () => {
    if (paginacion.value.current_page > 1) {
        cambiarPagina(paginacion.value.current_page - 1);
    }
};

const paginaSiguiente = () => {
    if (paginacion.value.current_page < paginacion.value.last_page) {
        cambiarPagina(paginacion.value.current_page + 1);
    }
};

// Función para cargar datos de evolución de estudiante
const cargarDatosEvolucionEstudiante = async () => {
    if (!filtros.value.estudiante_id || filtros.value.estudiante_id === null) {
        estudianteData.value = [];
        estadisticasEvolucion.value = {
            estudiante_nombre: '',
            total_depositos: 0,
            total_puntos: 0
        };
        return;
    }
    
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('estudiante_id', filtros.value.estudiante_id.toString());
        
        // Agregar periodo_id si está en modo periodo y seleccionado
        if (modo_seleccion_fecha.value === 'periodo' && periodo_estudiante_seleccionado.value !== 'custom') {
            params.append('periodo_id', periodo_estudiante_seleccionado.value.toString());
        } else {
            if (filtros.value.fecha_inicio) params.append('fecha_inicio', filtros.value.fecha_inicio);
            if (filtros.value.fecha_fin) params.append('fecha_fin', filtros.value.fecha_fin);
        }
        
        const url = `/admin/reportes/estudiante?${params.toString()}`;
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error('Error al cargar datos');
        }
        
        const data = await response.json();
        estudianteData.value = data.depositos || [];
        estadisticasEvolucion.value = {
            estudiante_nombre: data.estudiante?.nombre || '',
            total_depositos: estudianteData.value.length,
            total_puntos: data.total_puntos || 0
        };
        
    } catch (error) {
        toast({
            title: 'Error',
            description: 'No se pudieron cargar los datos del estudiante',
            variant: 'destructive'
        });
    } finally {
        loading.value = false;
    }
};

// Eliminado auto-reload para mejorar UX - ahora se carga manualmente con botón

// Función para cargar datos de ranking
const cargarDatosRanking = async () => {
    if (tipoReporte.value !== 'ranking') return;
    
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('tipo', tipoRanking.value);
        
        // Determinar qué parámetro enviar según el modo de selección
        if (modo_seleccion_fecha.value === 'filtros_rapidos') {
            params.append('filtro', filtro_tiempo_ranking.value);
        } else if (modo_seleccion_fecha.value === 'periodo' && periodo_estudiante_seleccionado.value !== 'custom') {
            params.append('filtro', periodo_estudiante_seleccionado.value.toString());
        } else if (modo_seleccion_fecha.value === 'rango' && filtros.value.fecha_inicio && filtros.value.fecha_fin) {
            params.append('fecha_inicio', filtros.value.fecha_inicio);
            params.append('fecha_fin', filtros.value.fecha_fin);
        } else {
            // Por defecto: últimos 3 meses
            params.append('filtro', 'todos');
        }
        
        const response = await fetch(`/admin/reportes/ranking?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) throw new Error('Error al cargar datos');
        
        const data = await response.json();
        rankingData.value = data.ranking || data || [];
        
        // Actualizar estadísticas si vienen en la respuesta
        if (data.estadisticas) {
            estadisticasRanking.value = {
                total_registros: data.estadisticas.total_registros || rankingData.value.length,
                total_puntos: data.estadisticas.total_puntos || 0,
                top_nombre: data.estadisticas.top_nombre || (rankingData.value[0]?.nombre_completo || 'N/A')
            };
        } else {
            // Calcular estadísticas localmente si no vienen del backend
            estadisticasRanking.value = {
                total_registros: rankingData.value.length,
                total_puntos: rankingData.value.reduce((sum, item) => sum + (item.total_puntos || 0), 0),
                top_nombre: rankingData.value[0]?.nombre_completo || 'N/A'
            };
        }
    } catch (error) {
        toast({ title: 'Error', description: 'No se pudieron cargar los datos del ranking', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};



// Función para cargar datos de estudiante
const cargarDatosEstudiante = async () => {
    if (tipoReporte.value !== 'estudiante' || !filtros.value.estudiante_id) return;
    
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('estudiante_id', filtros.value.estudiante_id.toString());
        
        if (periodo_estudiante_seleccionado.value !== 'custom') {
            params.append('periodo_id', periodo_estudiante_seleccionado.value.toString());
        } else if (filtros.value.fecha_inicio && filtros.value.fecha_fin) {
            params.append('fecha_inicio', filtros.value.fecha_inicio);
            params.append('fecha_fin', filtros.value.fecha_fin);
        }
        
        const response = await fetch(`/admin/reportes/estudiante?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!response.ok) throw new Error('Error al cargar datos');
        
        const data = await response.json();
        estudianteData.value = data.depositos || [];
    } catch (error) {
        toast({ title: 'Error', description: 'No se pudieron cargar los datos del estudiante', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

// Carga automática en tiempo real cuando cambian filtros o tipo de reporte
watch([tipoReporte, () => filtros.value.tipo_residuo_id, () => filtros.value.estudiante_id, tipoRanking, filtro_tiempo_ranking, modo_seleccion_fecha, periodo_estudiante_seleccionado, () => filtros.value.fecha_inicio, () => filtros.value.fecha_fin], () => {
    // Cargar datos automáticamente según el tipo de reporte
    if (tipoReporte.value === 'depositos') {
        cargarDatosDepositos(1);
    } else if (tipoReporte.value === 'ranking') {
        cargarDatosRanking();
    } else if (tipoReporte.value === 'ranking') {
        cargarDatosRanking();
    } else if (tipoReporte.value === 'estudiante') {
        if (filtros.value.estudiante_id) {
            cargarDatosEvolucionEstudiante();
        }
    }
}, { deep: true });

// Cargar datos iniciales cuando se monta el componente
onMounted(() => {
    if (tipoReporte.value === 'depositos') {
        cargarDatosDepositos(1);
    }
});

// Colores adaptables según el tema
const getThemeColors = () => {
    const dark = isDarkMode();
    return {
        tooltipBg: dark ? '#111827' : '#FFFFFF',
        tooltipText: dark ? '#F9FAFB' : '#1F2937',
        tooltipBorder: dark ? '#374151' : '#E5E7EB',
        legendText: dark ? '#d1d5db' : '#4B5563',
        borderColor: dark ? '#374151' : '#E5E7EB',
    };
};

const exportarPDF = async () => {
    // Validate date range before exporting
    if (!validateDateRange()) {
        toast({
            title: 'Error de validación',
            description: dateRangeError.value,
            variant: 'destructive',
        });
        return;
    }

    let url = '';
    const params = new URLSearchParams();
    if (tipoReporte.value === 'depositos') {
        url = '/admin/reportes/depositos/pdf';
        if (filtros.value.tipo_residuo_id && filtros.value.tipo_residuo_id.length > 0) {
            filtros.value.tipo_residuo_id.forEach(id => params.append('tipo_residuo_id[]', id.toString()));
        }
        
        // Agregar periodo_id si está en modo periodo y seleccionado
        if (modo_seleccion_fecha.value === 'periodo' && periodo_estudiante_seleccionado.value !== 'custom') {
            params.append('periodo_id', periodo_estudiante_seleccionado.value.toString());
        } else {
            if (filtros.value.fecha_inicio) params.append('fecha_inicio', filtros.value.fecha_inicio);
            if (filtros.value.fecha_fin) params.append('fecha_fin', filtros.value.fecha_fin);
        }
    } else if (tipoReporte.value === 'ranking') {
        url = '/admin/reportes/ranking/pdf';
        
        // Agregar tipo de ranking
        params.append('tipo', tipoRanking.value);
        
        // Determinar qué parámetro enviar según el modo de selección (igual que en cargarDatosRanking)
        if (modo_seleccion_fecha.value === 'filtros_rapidos') {
            params.append('filtro', filtro_tiempo_ranking.value);
        } else if (modo_seleccion_fecha.value === 'periodo' && periodo_estudiante_seleccionado.value !== 'custom') {
            params.append('filtro', periodo_estudiante_seleccionado.value.toString());
        } else if (modo_seleccion_fecha.value === 'rango' && filtros.value.fecha_inicio && filtros.value.fecha_fin) {
            params.append('fecha_inicio', filtros.value.fecha_inicio);
            params.append('fecha_fin', filtros.value.fecha_fin);
        } else {
            // Por defecto: últimos 3 meses
            params.append('filtro', 'todos');
        }

    } else if (tipoReporte.value === 'estudiante') {
        url = '/admin/reportes/estudiante/pdf';
        if (filtros.value.estudiante_id) params.append('estudiante_id', filtros.value.estudiante_id.toString());
        if (filtros.value.curso_id !== 'todos') params.append('curso_id', filtros.value.curso_id.toString());
        if (filtros.value.paralelo_id !== 'todos') params.append('paralelo_id', filtros.value.paralelo_id.toString());
        
        // Agregar periodo_id si está en modo periodo y seleccionado
        if (modo_seleccion_fecha.value === 'periodo' && periodo_estudiante_seleccionado.value !== 'custom') {
            params.append('periodo_id', periodo_estudiante_seleccionado.value.toString());
        } else {
            if (filtros.value.fecha_inicio) params.append('fecha_inicio', filtros.value.fecha_inicio);
            if (filtros.value.fecha_fin) params.append('fecha_fin', filtros.value.fecha_fin);
        }
    }
    try {
        loading.value = true;
        const response = await fetch(`${url}?${params.toString()}`, {
            method: 'GET',
            headers: {
                Accept: 'application/pdf',
            },
        });
        if (!response.ok) throw new Error('No se pudo generar el PDF');
        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = 'reporte.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(downloadUrl);
        toast({
            title: 'PDF generado',
            description: 'El reporte se descargó correctamente.',
        });
    } catch (e) {
        toast({
            title: 'Error al exportar',
            description: 'Ocurrió un problema al intentar exportar el PDF.',
            variant: 'destructive',
        });
    } finally {
        loading.value = false;
    }
};

const exportarExcel = async () => {
    const params = new URLSearchParams();
    if (filtros.value.tipo_residuo_id && filtros.value.tipo_residuo_id.length > 0) {
        filtros.value.tipo_residuo_id.forEach(id => params.append('tipo_residuo_id[]', id.toString()));
    }
    
    // Agregar periodo_id si está en modo periodo y seleccionado
    if (modo_seleccion_fecha.value === 'periodo' && periodo_estudiante_seleccionado.value !== 'custom') {
        params.append('periodo_id', periodo_estudiante_seleccionado.value.toString());
    } else {
        if (filtros.value.fecha_inicio) params.append('fecha_inicio', filtros.value.fecha_inicio);
        if (filtros.value.fecha_fin) params.append('fecha_fin', filtros.value.fecha_fin);
    }

    try {
        loading.value = true;
        const response = await fetch(`/admin/reportes/depositos/excel?${params.toString()}`, {
            method: 'GET',
            headers: {
                Accept: 'text/csv',
            },
        });
        if (!response.ok) throw new Error('No se pudo generar el Excel');
        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = 'reporte_depositos.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(downloadUrl);
        toast({
            title: 'Excel generado',
            description: 'El reporte se descargó correctamente.',
        });
    } catch (e) {
        toast({
            title: 'Error al exportar',
            description: 'Ocurrió un problema al intentar exportar el Excel.',
            variant: 'destructive',
        });
    } finally {
        loading.value = false;
    }
};
</script>
