<?php

use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\EstudianteImportController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CursosMateriasController;
use App\Http\Controllers\BasureroController;
use App\Http\Controllers\TipoBasuraController;
use App\Http\Controllers\DepositoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocenteDashboardController;
use App\Http\Controllers\PeriodoAcademicoController;

Route::get('/', [\App\Http\Controllers\WelcomeController::class, 'index'])->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', RoleMiddleware::class.':administrador'])->group(function () {
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    // Rutas de importación y plantillas de estudiantes (DEBEN IR ANTES que las rutas con parámetros)
    Route::get('/admin/estudiantes/plantilla', [EstudianteImportController::class, 'descargarPlantilla'])
        ->name('admin.estudiantes.plantilla');
    Route::post('/admin/estudiantes/importar', [EstudianteImportController::class, 'importar'])
        ->name('admin.estudiantes.importar');
    
    // Rutas de estudiantes
    Route::get('/admin/estudiantes', [EstudianteController::class, 'index'])
        ->name('admin.estudiantes');
    Route::get('/admin/estudiantes/create', [EstudianteController::class, 'create'])
        ->name('admin.estudiantes.create');
    Route::post('/admin/estudiantes', [EstudianteController::class, 'store'])
        ->name('admin.estudiantes.store');
    Route::get('/admin/estudiantes/{id}', [EstudianteController::class, 'show'])
        ->name('admin.estudiantes.show');
    Route::put('/admin/estudiantes/{id}', [EstudianteController::class, 'update'])
        ->name('admin.estudiantes.update');
    Route::delete('/admin/estudiantes/{id}', [EstudianteController::class, 'destroy'])
        ->name('admin.estudiantes.destroy');

    // Rutas de docentes
    Route::get('/admin/docentes', [DocenteController::class, 'index'])
        ->name('admin.docentes');
    Route::get('/admin/docentes/create', [DocenteController::class, 'create'])
        ->name('admin.docentes.create');
    Route::post('/admin/docentes', [DocenteController::class, 'store'])
        ->name('admin.docentes.store');
    Route::get('/admin/docentes/{id}', [DocenteController::class, 'show'])
        ->name('admin.docentes.show');
    Route::get('/admin/docentes/{id}/edit', [DocenteController::class, 'edit'])
        ->name('admin.docentes.edit');
    Route::put('/admin/docentes/{id}', [DocenteController::class, 'update'])
        ->name('admin.docentes.update');
    Route::delete('/admin/docentes/{id}', [DocenteController::class, 'destroy'])
        ->name('admin.docentes.destroy');
    
    // API endpoints para gestión de asignaciones
    Route::get('/admin/materias-por-curso', [DocenteController::class, 'getMateriasByCurso'])
        ->name('admin.materias.por.curso');
    Route::post('/admin/verificar-conflictos', [DocenteController::class, 'verificarConflictos'])
        ->name('admin.verificar.conflictos');
    Route::get('/admin/materias-disponibles', [DocenteController::class, 'getMateriasDisponibles'])
        ->name('admin.materias.disponibles');

    // Rutas de cursos y materias
    Route::get('/admin/cursos-materias', [CursosMateriasController::class, 'index'])
        ->name('admin.cursos.materias');
    
    // API endpoints para gestión de materias por curso-paralelo
    Route::get('/admin/materias-curso-paralelo', [CursosMateriasController::class, 'getMateriasByCursoParalelo'])
        ->name('admin.materias.curso.paralelo');
    Route::post('/admin/asignar-materia', [CursosMateriasController::class, 'asignarMateria'])
        ->name('admin.asignar.materia');
    Route::delete('/admin/quitar-materia', [CursosMateriasController::class, 'quitarMateria'])
        ->name('admin.quitar.materia');

    // CRUD de cursos
    Route::post('/admin/cursos', [CursosMateriasController::class, 'storeCurso'])
        ->name('admin.cursos.store');
    Route::put('/admin/cursos/{id}', [CursosMateriasController::class, 'updateCurso'])
        ->name('admin.cursos.update');
    Route::delete('/admin/cursos/{id}', [CursosMateriasController::class, 'destroyCurso'])
        ->name('admin.cursos.destroy');

    // CRUD de paralelos
    Route::post('/admin/paralelos', [CursosMateriasController::class, 'storeParalelo'])
        ->name('admin.paralelos.store');
    Route::put('/admin/paralelos/{id}', [CursosMateriasController::class, 'updateParalelo'])
        ->name('admin.paralelos.update');
    Route::delete('/admin/paralelos/{id}', [CursosMateriasController::class, 'destroyParalelo'])
        ->name('admin.paralelos.destroy');

    // CRUD de materias
    Route::post('/admin/materias', [CursosMateriasController::class, 'storeMateria'])
        ->name('admin.materias.store');
    Route::put('/admin/materias/{id}', [CursosMateriasController::class, 'updateMateria'])
        ->name('admin.materias.update');
    Route::delete('/admin/materias/{id}', [CursosMateriasController::class, 'destroyMateria'])
        ->name('admin.materias.destroy');

    // ===== RUTAS DE GESTIÓN DE RESIDUOS =====
    // Basureros
    Route::get('/admin/basureros', [BasureroController::class, 'index'])
        ->name('admin.basureros.index');
    Route::get('/admin/basureros/create', [BasureroController::class, 'create'])
        ->name('admin.basureros.create');
    Route::post('/admin/basureros', [BasureroController::class, 'store'])
        ->name('admin.basureros.store');
    Route::get('/admin/basureros/{basurero}', [BasureroController::class, 'show'])
        ->name('admin.basureros.show');
    Route::get('/admin/basureros/{basurero}/edit', [BasureroController::class, 'edit'])
        ->name('admin.basureros.edit');
    Route::put('/admin/basureros/{basurero}', [BasureroController::class, 'update'])
        ->name('admin.basureros.update');
    Route::delete('/admin/basureros/{basurero}', [BasureroController::class, 'destroy'])
        ->name('admin.basureros.destroy');
    Route::patch('/admin/basureros/{basurero}/toggle-estado', [BasureroController::class, 'toggleEstado'])
        ->name('admin.basureros.toggle-estado');

    // Tipos de basura
    Route::get('/admin/tipos-basura', [TipoBasuraController::class, 'index'])
        ->name('admin.tipos-basura.index');
    Route::get('/admin/tipos-basura/create', [TipoBasuraController::class, 'create'])
        ->name('admin.tipos-basura.create');
    Route::post('/admin/tipos-basura', [TipoBasuraController::class, 'store'])
        ->name('admin.tipos-basura.store');
    Route::get('/admin/tipos-basura/{tipoBasura}', [TipoBasuraController::class, 'show'])
        ->name('admin.tipos-basura.show');
    Route::get('/admin/tipos-basura/{tipoBasura}/edit', [TipoBasuraController::class, 'edit'])
        ->name('admin.tipos-basura.edit');
    Route::put('/admin/tipos-basura/{tipoBasura}', [TipoBasuraController::class, 'update'])
        ->name('admin.tipos-basura.update');
    Route::delete('/admin/tipos-basura/{tipoBasura}', [TipoBasuraController::class, 'destroy'])
        ->name('admin.tipos-basura.destroy');

    // Depósitos
    Route::get('/admin/depositos', [DepositoController::class, 'index'])
        ->name('admin.depositos.index');
    Route::get('/admin/depositos/create', [DepositoController::class, 'create'])
        ->name('admin.depositos.create');
    Route::post('/admin/depositos', [DepositoController::class, 'store'])
        ->name('admin.depositos.store');
    Route::get('/admin/depositos/estadisticas', [DepositoController::class, 'estadisticas'])
        ->name('admin.depositos.estadisticas');
    Route::get('/admin/depositos/{deposito}', [DepositoController::class, 'show'])
        ->name('admin.depositos.show');
    Route::get('/admin/depositos/{deposito}/edit', [DepositoController::class, 'edit'])
        ->name('admin.depositos.edit');
    Route::put('/admin/depositos/{deposito}', [DepositoController::class, 'update'])
        ->name('admin.depositos.update');
    Route::delete('/admin/depositos/{deposito}', [DepositoController::class, 'destroy'])
        ->name('admin.depositos.destroy');

    // Rutas de reportes
    Route::get('/admin/reportes', [ReporteController::class, 'index'])->name('admin.reportes.index');
    Route::get('/admin/reportes/depositos/pdf', [ReporteController::class, 'exportarPDF'])->name('admin.reportes.depositos.pdf');
    Route::get('/admin/reportes/depositos/excel', [ReporteController::class, 'exportarExcel'])->name('admin.reportes.depositos.excel');
    Route::get('/admin/reportes/ranking/pdf', [ReporteController::class, 'exportarRankingPDF'])->name('admin.reportes.ranking.pdf');
    Route::get('/admin/reportes/basurero/pdf', [ReporteController::class, 'exportarBasureroPDF'])->name('admin.reportes.basurero.pdf');
    Route::get('/admin/reportes/fecha/pdf', [ReporteController::class, 'exportarDepositosPorFechaPDF'])->name('admin.reportes.fecha.pdf');
    Route::get('/admin/reportes/depositos', [ReporteController::class, 'depositos'])->name('admin.reportes.depositos');
    Route::get('/admin/reportes/ranking', [ReporteController::class, 'ranking'])->name('admin.reportes.ranking');
    Route::get('/admin/reportes/basureros', [ReporteController::class, 'basureros'])->name('admin.reportes.basureros');
    Route::get('/admin/reportes/tendencias', [ReporteController::class, 'tendencias'])->name('admin.reportes.tendencias');
    Route::get('/admin/reportes/impacto', [ReporteController::class, 'impacto'])->name('admin.reportes.impacto');

    // Rutas de gestión de usuarios
    Route::get('/admin/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/admin/users/create', [UserController::class, 'create'])->name('users.create');
    Route::get('/admin/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/admin/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Rutas para Períodos Académicos
    Route::prefix('admin')->group(function () {
        Route::resource('periodos', PeriodoAcademicoController::class)->names([
            'index' => 'admin.periodos.index',
            'store' => 'admin.periodos.store',
            'update' => 'admin.periodos.update',
            'destroy' => 'admin.periodos.destroy',
        ]);
    });
});


// Rutas del Docente
Route::middleware(['auth', RoleMiddleware::class.':docente'])->group(function () {
    Route::get('/docente/dashboard', [DocenteDashboardController::class, 'index'])->name('docente.dashboard');
    Route::get('/docente/curso/{idCursoParalelo}', [DocenteDashboardController::class, 'cursoDetalle'])->name('docente.curso.detalle');
    Route::post('/docente/curso/{idCursoParalelo}/asignar-puntos', [DocenteDashboardController::class, 'asignarPuntos'])->name('docente.curso.asignar');
    Route::get('/docente/estudiantes/{idCursoParalelo}', [DocenteDashboardController::class, 'estudiantesPorCurso'])
        ->name('docente.estudiantes');
    Route::get('/docente/reportes/curso/{idCursoParalelo}', [DocenteDashboardController::class, 'reportePuntosPorCurso'])
        ->name('docente.reportes.curso');
    Route::get('/docente/reportes/materia/{idCursoParalelo}/{idMateria}', [DocenteDashboardController::class, 'reportePuntosPorMateria'])
        ->name('docente.reportes.materia');
    Route::get('/docente/exportar/materia/{idCursoParalelo}/{idMateria}', [DocenteDashboardController::class, 'exportarMateriaExcel'])
        ->name('docente.curso.exportar-materia-excel');
    // Ruta para descargar plantilla de estudiantes
    Route::get('/docente/plantilla-estudiantes', [DocenteDashboardController::class, 'descargarPlantillaEstudiantes'])
        ->name('docente.plantilla-estudiantes');
});
require __DIR__.'/auth.php';

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
