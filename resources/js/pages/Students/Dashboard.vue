<template>
    <StudentLayout :student="student">
        <!-- Hero Section -->
        <StudentWelcome
            :student-name="student.nombres"
            :course="student.curso?.nombre"
            :parallel="student.paralelo?.nombre"
            :total-points="totalPoints"
            :ranking="ranking"
            :current-period="currentPeriod?.nombre"
        />

        <!-- Main Content -->
        <div class="px-6 py-12 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-7xl space-y-8">
                <!-- Alerta QR Desactivado -->
                <div v-if="!student.qr_codigo || student.qr_codigo === ''" class="rounded-lg border-2 border-red-300 bg-red-50 dark:border-red-600 dark:bg-red-900/20 p-6">
                    <div class="flex items-start gap-4">
                        <div class="rounded-full bg-red-100 dark:bg-red-900 p-3">
                            <AlertCircle class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-red-900 dark:text-red-100 mb-2">
                                ⚠️ Código QR Desactivado
                            </h3>
                            <p class="text-red-800 dark:text-red-200 mb-3">
                                Tu código QR está actualmente <strong>desactivado</strong>. No podrás acumular puntos ni registrar depósitos hasta que sea reactivado.
                            </p>
                            <div class="rounded-md bg-red-100 dark:bg-red-900/40 p-4 mb-3">
                                <p class="text-sm text-red-900 dark:text-red-100 font-medium mb-2">
                                    📞 Para reactivar tu código QR, comunícate con la <strong>Dirección del colegio</strong>
                                </p>
                                <p class="text-xs text-red-700 dark:text-red-300">
                                    Código de estudiante: <strong>{{ student.codigo_estudiante || student.id }}</strong>
                                </p>
                            </div>
                            
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Card
                        class="border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 dark:border-green-700 dark:from-green-900/50 dark:to-emerald-900/50"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-green-100 p-3">
                                    <Leaf class="h-6 w-6 text-green-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Puntos Este Bimestre</p>
                                    <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ currentBimesterPoints }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-blue-200 bg-gradient-to-br from-blue-50 to-cyan-50 dark:border-blue-700 dark:from-blue-900/50 dark:to-cyan-900/50"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-blue-100 p-3">
                                    <Recycle class="h-6 w-6 text-blue-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Depósitos Realizados</p>
                                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ totalDeposits }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50 dark:border-purple-700 dark:from-purple-900/50 dark:to-pink-900/50"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-purple-100 p-3">
                                    <Trophy class="h-6 w-6 text-purple-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Posición en Curso</p>
                                    <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">#{{ ranking }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-orange-200 bg-gradient-to-br from-orange-50 to-yellow-50 dark:border-orange-700 dark:from-orange-900/50 dark:to-yellow-900/50"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-full bg-orange-100 p-3">
                                    <Target class="h-6 w-6 text-orange-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-orange-600 dark:text-orange-400">Meta del Bimestre</p>
                                    <p class="text-2xl font-bold text-orange-900 dark:text-orange-100">{{ goalProgress }}%</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Main Horizontal Layout -->
                <div class="grid gap-8 xl:grid-cols-3 lg:grid-cols-2">
                    <!-- Progress & Recent Activity -->
                    <div class="space-y-6 xl:col-span-2">
                        <!-- Progress Section -->
                        <Card class="border-green-200 dark:border-green-700">
                            <CardHeader>
                                <CardTitle class="flex items-center text-green-800 dark:text-green-300">
                                    <TrendingUp class="mr-2 h-5 w-5" />
                                    Tu Progreso Ecológico
                                </CardTitle>
                                <CardDescription> Progreso hacia tu meta de {{ bimesterGoal }} puntos este bimestre </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium">{{ currentBimesterPoints }} / {{ bimesterGoal }} puntos</span>
                                        <span class="text-muted-foreground text-sm">{{ goalProgress }}%</span>
                                    </div>
                                    <div class="relative h-3 w-full overflow-hidden rounded-full bg-green-100 dark:bg-green-900">
                                        <div 
                                            class="h-full bg-gradient-to-r from-green-500 to-emerald-600 rounded-full transition-all duration-700 ease-out"
                                            :style="`width: ${goalProgress}%`"
                                        ></div>
                                        <div v-if="goalProgress >= 100" class="absolute inset-0 flex items-center justify-center gap-1">
                                            <Trophy class="h-3 w-3 text-white" />
                                            <span class="text-xs font-bold text-white">¡Meta alcanzada!</span>
                                        </div>
                                    </div>
                                    <div class="text-muted-foreground flex justify-between text-xs">
                                        <span>Inicio del bimestre</span>
                                        <span>Meta alcanzada</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Combined Activity Grid -->
                        <div class="grid gap-6 lg:grid-cols-2">
                            <!-- Recent Deposits -->
                            <Card class="border-blue-200 dark:border-blue-700">
                                <CardHeader>
                                    <CardTitle class="flex items-center text-blue-800 dark:text-blue-300">
                                        <History class="mr-2 h-5 w-5" />
                                        Depósitos Recientes
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-3">
                                        <div
                                            v-for="deposit in recentDeposits.slice(0, 4)"
                                            :key="deposit.id"
                                            class="hover:bg-muted/50 flex items-center justify-between rounded-lg border p-3 transition-colors dark:border-gray-600 dark:hover:bg-gray-700/50"
                                        >
                                            <div class="flex items-center space-x-3">
                                                <div class="rounded-full bg-green-100 p-2">
                                                    <Trash2 class="h-3 w-3 text-green-600" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium dark:text-gray-100">{{ deposit.tipo_basura?.nombre }}</p>
                                                    <p class="text-muted-foreground text-xs">
                                                        {{ formatDate(deposit.fecha_deposito) }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-bold text-green-600">+{{ deposit.puntaje_obtenido }}</p>
                                            </div>
                                        </div>

                                        <div v-if="recentDeposits.length === 0" class="text-muted-foreground py-6 text-center">
                                            <Leaf class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                            <p class="text-sm">Sin depósitos recientes</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Docentes y sus Materias -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center">
                                        <User class="mr-2 h-5 w-5" />
                                        Docentes y sus Materias - {{ currentPeriod?.nombre || 'Período Actual' }}
                                    </CardTitle>
                                    <CardDescription>
                                        Profesores que pueden asignarte puntos académicos este período
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <div
                                            v-for="teacherGroup in subjectsByTeacher.slice(0, 3)"
                                            :key="teacherGroup.teacher.nombre_completo"
                                            class="group relative overflow-hidden rounded-xl border p-4 transition-all duration-200 hover:shadow-lg"
                                            :class="[
                                                teacherGroup.hasAnyGrades
                                                    ? 'border-amber-200 bg-gradient-to-r from-amber-50 to-yellow-50 dark:border-amber-700 dark:from-amber-900/30 dark:to-yellow-900/30'
                                                    : 'bg-white/90 dark:bg-gray-800/50'
                                            ]"
                                        >
                                            <!-- Cabecera del docente -->
                                            <div class="flex items-start gap-3 mb-3">
                                                <div 
                                                    class="flex-shrink-0 rounded-full p-2 shadow-sm"
                                                    :class="teacherGroup.hasAnyGrades ? 'bg-amber-100 dark:bg-amber-800' : 'bg-gray-100 dark:bg-gray-800'"
                                                >
                                                    <User 
                                                        class="h-4 w-4" 
                                                        :class="teacherGroup.hasAnyGrades ? 'text-amber-600 dark:text-amber-300' : 'text-gray-600 dark:text-gray-300'" 
                                                    />
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <h4 
                                                            class="font-bold truncate text-sm" 
                                                            :class="teacherGroup.hasAnyGrades ? 'text-amber-900 dark:text-amber-100' : 'text-gray-900 dark:text-gray-100'"
                                                        >
                                                            {{ teacherGroup.teacher.nombre_completo }}
                                                        </h4>
                                                        <CheckCircle v-if="teacherGroup.hasAnyGrades" class="h-3 w-3 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                                                    </div>
                                                    <Badge 
                                                        v-if="teacherGroup.hasAnyGrades" 
                                                        class="bg-amber-500 text-white hover:bg-amber-600 text-xs"
                                                    >
                                                        <Star class="mr-1 h-2 w-2" />
                                                        Ya te ha calificado
                                                    </Badge>
                                                    <Badge 
                                                        v-else 
                                                        variant="outline" 
                                                        class="text-xs"
                                                    >
                                                        <Zap class="mr-1 h-2 w-2" />
                                                        Disponible
                                                    </Badge>
                                                </div>
                                            </div>

                                            <!-- Materias del docente (compactas) -->
                                            <div class="ml-9 space-y-1">
                                                <div
                                                    v-for="subject in teacherGroup.subjects.slice(0, 2)"
                                                    :key="subject.id"
                                                    class="flex items-center justify-between text-xs"
                                                >
                                                    <div class="flex items-center gap-1">
                                                        <BookOpen class="h-2 w-2 text-gray-400" />
                                                        <span class="truncate">{{ subject.materia }}</span>
                                                    </div>
                                                    <CheckCircle v-if="subject.hasGrades" class="h-2 w-2 text-amber-500" />
                                                </div>
                                                
                                                <div v-if="teacherGroup.subjects.length > 2" 
                                                     class="text-xs text-gray-500 text-center"
                                                >
                                                    +{{ teacherGroup.subjects.length - 2 }} más
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="subjectsByTeacher.length === 0" class="py-6 text-center">
                                            <User class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                            <p class="text-sm text-gray-500">No hay docentes configurados</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6 xl:col-span-1">
                        <!-- Quick Stats -->
                        <Card class="border-indigo-200 dark:border-indigo-700">
                            <CardHeader>
                                <CardTitle class="flex items-center text-indigo-800 dark:text-indigo-300">
                                    <Target class="mr-2 h-5 w-5" />
                                    Resumen Rápido
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Posición</span>
                                    <span class="font-bold text-purple-600">#{{ ranking }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Depósitos</span>
                                    <span class="font-bold text-blue-600">{{ totalDeposits }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Meta</span>
                                    <span class="font-bold text-orange-600">{{ goalProgress }}%</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Notas</span>
                                    <span class="font-bold text-yellow-600">{{ academicGrades.length }}</span>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Top 3 del Curso -->
                        <Card class="border-purple-200 dark:border-purple-700">
                            <CardHeader>
                                <CardTitle class="flex items-center text-purple-800 dark:text-purple-300">
                                    <Trophy class="mr-2 h-5 w-5" />
                                    Top 3 de tu Curso
                                </CardTitle>
                                <CardDescription>
                                    Los estudiantes con más puntos en {{ student.curso?.nombre }} "{{ student.paralelo?.nombre }}"
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-3">
                                    <div
                                        v-for="(topStudent, index) in courseTop3"
                                        :key="topStudent.id"
                                        class="flex items-center justify-between rounded-lg border p-3 transition-colors"
                                        :class="
                                            topStudent.id === student.id
                                                ? 'border-purple-500 bg-purple-50 dark:border-purple-400 dark:bg-purple-900/50'
                                                : 'border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-800'
                                        "
                                    >
                                        <div class="flex items-center space-x-3">
                                            <div
                                                :class="[
                                                    'flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold text-white',
                                                    index === 0 ? 'bg-yellow-500' : index === 1 ? 'bg-gray-400' : 'bg-orange-500',
                                                ]"
                                            >
                                                {{ index + 1 }}
                                            </div>
                                            <div>
                                                <p class="font-medium dark:text-gray-100">{{ topStudent.apellidos }}, {{ topStudent.nombres }}</p>
                                                <p v-if="topStudent.id === student.id" class="text-sm text-purple-600 dark:text-purple-400">
                                                    ¡Eres tú!
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-purple-600 dark:text-purple-400">{{ topStudent.puntaje }} pts</p>
                                        </div>
                                    </div>

                                    <div v-if="courseTop3.length === 0" class="text-muted-foreground py-4 text-center">
                                        <Trophy class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                        <p class="text-sm">No hay datos de ranking disponibles</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Estadísticas de Actividad (Prioritario) -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <TrendingUp class="mr-2 h-5 w-5" />
                            Estadísticas de Actividad
                        </CardTitle>
                        <CardDescription>
                            Tu progreso y actividad de reciclaje reciente
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <!-- Actividad reciente -->
                        <div class="space-y-6">
                            <div>
                                <h4 class="mb-4 font-semibold text-gray-800 dark:text-gray-200">Actividad Reciente</h4>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-4 dark:border-blue-700 dark:bg-blue-900/20">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="rounded-full bg-blue-500 p-2">
                                                    <History class="h-4 w-4 text-white" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Esta semana</p>
                                                    <p class="text-xs text-blue-600 dark:text-blue-400">Últimos 7 días</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xl font-bold text-blue-800 dark:text-blue-200">{{ activityStats.thisWeek }}</p>
                                                <p class="text-xs text-blue-600 dark:text-blue-400">depósitos</p>
                                                <Badge class="mt-1 bg-blue-500 text-white">+{{ activityStats.weeklyPoints }} pts</Badge>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="rounded-lg border border-green-200 bg-green-50/50 p-4 dark:border-green-700 dark:bg-green-900/20">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="rounded-full bg-green-500 p-2">
                                                    <Target class="h-4 w-4 text-white" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-green-800 dark:text-green-200">Este mes</p>
                                                    <p class="text-xs text-green-600 dark:text-green-400">Últimos 30 días</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xl font-bold text-green-800 dark:text-green-200">{{ activityStats.thisMonth }}</p>
                                                <p class="text-xs text-green-600 dark:text-green-400">depósitos</p>
                                                <Badge class="mt-1 bg-green-500 text-white">+{{ activityStats.monthlyPoints }} pts</Badge>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Impacto estimado -->
                            <div class="border-t pt-6">
                                <h4 class="mb-4 font-semibold text-gray-800 dark:text-gray-200">Impacto Ambiental Estimado</h4>
                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                    <div class="text-center rounded-lg border p-3">
                                        <TreePine class="mx-auto mb-2 h-6 w-6 text-green-600" />
                                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ environmentalImpact.treesSaved }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Árboles salvados</p>
                                    </div>
                                    
                                    <div class="text-center rounded-lg border p-3">
                                        <Droplets class="mx-auto mb-2 h-6 w-6 text-blue-600" />
                                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ environmentalImpact.waterSaved }}L</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Agua ahorrada</p>
                                    </div>
                                    
                                    <div class="text-center rounded-lg border p-3">
                                        <Lightning class="mx-auto mb-2 h-6 w-6 text-yellow-600" />
                                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ environmentalImpact.energySaved }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">kWh ahorrados</p>
                                    </div>
                                    
                                    <div class="text-center rounded-lg border p-3">
                                        <Globe class="mx-auto mb-2 h-6 w-6 text-purple-600" />
                                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ environmentalImpact.co2Reduced }}kg</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">CO₂ reducido</p>
                                    </div>
                                </div>
                                
                                <div class="mt-4 rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                    <div class="flex items-center justify-center gap-2">
                                        <Globe class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                        <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                                            Total: {{ environmentalImpact.totalDeposits }} depósitos de reciclaje realizados
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Secondary Grid -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Tipos de Basura -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center">
                                <Recycle class="mr-2 h-5 w-5" />
                                Tipos de Basura
                            </CardTitle>
                            <CardDescription>
                                Materiales que puedes reciclar y sus puntos
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div
                                    v-for="wasteType in wasteTypes"
                                    :key="wasteType.id"
                                    class="group relative overflow-hidden rounded-xl border bg-white/90 p-4 shadow-sm transition-all duration-200 hover:shadow-lg hover:scale-[1.02] dark:bg-gray-800/50"
                                    :class="[
                                        `border-${getWasteTypeIcon(wasteType.materia).color.split('-')[1]}-200`,
                                        `dark:border-${getWasteTypeIcon(wasteType.materia).color.split('-')[1]}-700`
                                    ]"
                                >
                                    <div class="flex items-start gap-4">
                                        <!-- Icono específico por tipo -->
                                        <div 
                                            class="flex-shrink-0 rounded-xl p-3 shadow-sm"
                                            :class="[
                                                getWasteTypeIcon(wasteType.materia).bgColor,
                                                getWasteTypeIcon(wasteType.materia).darkBgColor
                                            ]"
                                        >
                                            <component 
                                                :is="getWasteTypeIcon(wasteType.materia).icon" 
                                                class="h-6 w-6"
                                                :class="[
                                                    getWasteTypeIcon(wasteType.materia).color,
                                                    `dark:${getWasteTypeIcon(wasteType.materia).color.replace('text-', 'text-').replace('-600', '-300')}`
                                                ]"
                                            />
                                        </div>
                                        
                                        <!-- Contenido -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-bold text-gray-900 dark:text-gray-100 truncate">
                                                        {{ wasteType.materia }}
                                                    </h4>
                                                    <p v-if="wasteType.descripcion" class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                                        {{ wasteType.descripcion }}
                                                    </p>
                                                </div>
                                                
                                                <!-- Puntos destacados -->
                                                <div class="flex-shrink-0 text-right">
                                                    <div class="inline-flex items-center rounded-full px-3 py-2 text-white font-bold shadow-lg bg-gray-600">
                                                        <span class="text-lg">+{{ wasteType.puntos }}</span>
                                                    </div>
                                                    <p class="mt-1 text-xs font-medium text-gray-600 dark:text-gray-400">
                                                        puntos
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Efecto de brillo al hover -->
                                    <div 
                                        class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                        :class="`dark:via-${getWasteTypeIcon(wasteType.materia).color.split('-')[1]}-400/10`"
                                    ></div>
                                </div>

                                <div v-if="wasteTypes.length === 0" class="col-span-full py-8 text-center">
                                    <Recycle class="mx-auto mb-3 h-12 w-12 text-green-300 dark:text-green-600" />
                                    <p class="text-sm text-green-600 dark:text-green-400">No hay tipos de basura configurados</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Notas Recientes -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center">
                                <GraduationCap class="mr-2 h-5 w-5" />
                                Notas Recientes - {{ currentPeriod?.nombre || 'Período Actual' }}
                            </CardTitle>
                            <CardDescription>
                                Tus calificaciones académicas más recientes
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="grade in academicGrades.slice(0, 6)"
                                    :key="grade.idMateria"
                                    class="rounded-lg border p-3 transition hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                {{ grade.materia }}
                                            </h4>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                                {{ grade.docentes }}
                                            </p>
                                        </div>
                                        <div class="text-right flex-shrink-0 ml-3">
                                            <Badge class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ grade.total }} pts
                                            </Badge>
                                            <p v-if="grade.ultima_fecha" class="text-xs text-gray-500 mt-1">
                                                {{ formatDate(grade.ultima_fecha) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="academicGrades.length === 0" class="py-6 text-center">
                                    <GraduationCap class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                    <p class="text-sm text-gray-500">Sin notas registradas este período</p>
                                </div>

                                <div v-if="academicGrades.length > 6" class="pt-2 text-center">
                                    <button class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                        Ver todas las notas ({{ academicGrades.length }})
                                    </button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>


                    <!-- Motivational Section -->
                    <Card
                        class="border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 dark:border-emerald-700 dark:from-emerald-900/50 dark:to-teal-900/50"
                    >
                        <CardContent class="p-8 text-center">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                                <Heart class="h-8 w-8 text-emerald-600" />
                            </div>
                            <h3 class="mb-2 text-xl font-bold text-emerald-800 dark:text-emerald-300">¡Excelente trabajo cuidando el planeta!</h3>
                            <p class="mb-4 text-emerald-700 dark:text-emerald-300">
                                Cada punto que ganas representa tu compromiso con el medio ambiente. ¡Sigue así y serás un verdadero héroe ecológico!
                            </p>
                            <div class="flex justify-center space-x-3">
                                <Leaf class="h-6 w-6 text-emerald-600" />
                                <Recycle class="h-6 w-6 text-emerald-600" />
                                <Globe class="h-6 w-6 text-emerald-600" />
                                <Heart class="h-6 w-6 text-emerald-600" />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup lang="ts">
import StudentWelcome from '@/components/student/StudentWelcome.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { AlertCircle, BookOpen, Clock, GraduationCap, Heart, History, Leaf, Recycle, Target, Trash2, TrendingUp, Trophy, User, CheckCircle, Star, Award, Zap, Package, Newspaper, Battery, Lightbulb, Cpu, Smartphone, Wine, Box, FileText, TreePine, Droplets, Zap as Lightning, Globe } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    student: {
        id: number;
        nombres: string;
        apellidos: string;
        codigo_estudiante?: string;
        qr_codigo?: string | null;
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
        tipo_basura?: {
            id?: number;
            nombre: string;
        };
    }>;
    totalDepositsCount: number;
    currentPeriod?: {
        id: number;
        nombre: string;
    };
    totalPoints: number;
    currentBimesterPoints: number;
    ranking: number;
    bimesterGoal: number;
    courseTop3: Array<{
        id: number;
        nombres: string;
        apellidos: string;
        puntaje: number;
        posicion: number;
    }>;
    subjectsInfo: Array<{
        id: string;
        tipo: 'tipo_basura' | 'materia';
        materia: string;
        puntos: number;
        descripcion?: string;
        docentes: Array<{
            nombres: string;
            apellidos: string;
            nombre_completo: string;
        }>;
    }>;
    academicGrades: Array<{
        idMateria: number;
        materia: string;
        docentes: string;
        puntos_depositos: number;
        puntos_extracurriculares: number;
        total: number;
        ultima_fecha: string | null;
    }>;
}

const props = defineProps<Props>();

const totalDeposits = computed(() => props.totalDepositsCount);

const recentDeposits = computed(() =>
    props.deposits.sort((a, b) => new Date(b.fecha_deposito).getTime() - new Date(a.fecha_deposito).getTime()).slice(0, 5),
);

const goalProgress = computed(() => Math.min(Math.round((props.currentBimesterPoints / props.bimesterGoal) * 100), 100));

// Separar tipos de basura de materias académicas
const wasteTypes = computed(() => 
    props.subjectsInfo.filter(subject => subject.tipo === 'tipo_basura')
);

const academicSubjects = computed(() => 
    props.subjectsInfo.filter(subject => subject.tipo === 'materia')
);

// Obtener materias que ya han dado notas
const subjectsWithGrades = computed(() => {
    const gradedSubjects = new Set(props.academicGrades.map(grade => grade.materia));
    return academicSubjects.value.map(subject => ({
        ...subject,
        hasGrades: gradedSubjects.has(subject.materia)
    }));
});

// Agrupar materias por docente
const subjectsByTeacher = computed(() => {
    const teacherMap = new Map();
    const gradedSubjects = new Set(props.academicGrades.map(grade => grade.materia));
    
    academicSubjects.value.forEach(subject => {
        subject.docentes.forEach(docente => {
            const teacherKey = docente.nombre_completo;
            if (!teacherMap.has(teacherKey)) {
                teacherMap.set(teacherKey, {
                    teacher: docente,
                    subjects: [],
                    hasAnyGrades: false
                });
            }
            
            const hasGrades = gradedSubjects.has(subject.materia);
            teacherMap.get(teacherKey).subjects.push({
                ...subject,
                hasGrades
            });
            
            if (hasGrades) {
                teacherMap.get(teacherKey).hasAnyGrades = true;
            }
        });
    });
    
    return Array.from(teacherMap.values()).sort((a, b) => {
        // Priorizar docentes que ya han dado notas
        if (a.hasAnyGrades && !b.hasAnyGrades) return -1;
        if (!a.hasAnyGrades && b.hasAnyGrades) return 1;
        return a.teacher.nombre_completo.localeCompare(b.teacher.nombre_completo);
    });
});

// Función para obtener icono y color por tipo de basura
const getWasteTypeIcon = (name: string) => {
    const lowerName = name.toLowerCase();
    
    if (lowerName.includes('plástico') || lowerName.includes('plastico') || lowerName.includes('botella')) {
        return { icon: Wine, color: 'text-blue-600', bgColor: 'bg-blue-100', darkBgColor: 'dark:bg-blue-800' };
    } else if (lowerName.includes('papel') || lowerName.includes('cartón') || lowerName.includes('carton')) {
        return { icon: FileText, color: 'text-orange-600', bgColor: 'bg-orange-100', darkBgColor: 'dark:bg-orange-800' };
    } else if (lowerName.includes('vidrio') || lowerName.includes('cristal')) {
        return { icon: Wine, color: 'text-emerald-600', bgColor: 'bg-emerald-100', darkBgColor: 'dark:bg-emerald-800' };
    } else if (lowerName.includes('metal') || lowerName.includes('aluminio') || lowerName.includes('lata')) {
        return { icon: Package, color: 'text-gray-600', bgColor: 'bg-gray-100', darkBgColor: 'dark:bg-gray-800' };
    } else if (lowerName.includes('electrónico') || lowerName.includes('electronico') || lowerName.includes('batería') || lowerName.includes('bateria')) {
        return { icon: Smartphone, color: 'text-purple-600', bgColor: 'bg-purple-100', darkBgColor: 'dark:bg-purple-800' };
    } else if (lowerName.includes('orgánico') || lowerName.includes('organico') || lowerName.includes('comida')) {
        return { icon: Leaf, color: 'text-green-600', bgColor: 'bg-green-100', darkBgColor: 'dark:bg-green-800' };
    } else {
        return { icon: Trash2, color: 'text-green-600', bgColor: 'bg-green-100', darkBgColor: 'dark:bg-green-800' };
    }
};

// Calcular impacto ambiental basado en los depósitos
const environmentalImpact = computed(() => {
    const totalDeposits = props.totalDepositsCount;
    const totalPoints = props.totalPoints;
    
    // Estimaciones basadas en estudios ambientales (basado en puntos de reciclaje)
    const treesSaved = Math.round(totalPoints * 0.01); // 1 árbol por cada 100 puntos
    const waterSaved = Math.round(totalPoints * 3); // 3L por punto
    const energySaved = Math.round(totalPoints * 0.4); // 0.4kWh por punto
    const co2Reduced = Math.round(totalPoints * 0.25); // 0.25kg CO2 por punto
    
    return {
        totalDeposits,
        treesSaved: Math.max(treesSaved, 0),
        waterSaved: Math.max(waterSaved, 0),
        energySaved: Math.max(energySaved, 0),
        co2Reduced: Math.max(co2Reduced, 0)
    };
});

// Estadísticas de actividad reciente
const activityStats = computed(() => {
    const now = new Date();
    const thisWeek = props.deposits.filter(deposit => {
        const depositDate = new Date(deposit.fecha_deposito);
        const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
        return depositDate >= weekAgo;
    });
    
    const thisMonth = props.deposits.filter(deposit => {
        const depositDate = new Date(deposit.fecha_deposito);
        const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
        return depositDate >= monthAgo;
    });
    
    return {
        thisWeek: thisWeek.length,
        thisMonth: thisMonth.length,
        weeklyPoints: thisWeek.reduce((sum, deposit) => sum + deposit.puntaje_obtenido, 0),
        monthlyPoints: thisMonth.reduce((sum, deposit) => sum + deposit.puntaje_obtenido, 0)
    };
});

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

</script>
