<?php

use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\CursosMateriasController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\RoleMiddleware;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', RoleMiddleware::class.':administrador'])->group(function () {
    // Rutas de estudiantes
    Route::get('/admin/estudiantes', [EstudianteController::class, 'index'])
        ->name('admin.estudiantes');
    Route::get('/admin/estudiantes/{id}', [EstudianteController::class, 'show'])
        ->name('admin.estudiantes.show');
    Route::put('/admin/estudiantes/{id}', [EstudianteController::class, 'update'])
        ->name('admin.estudiantes.update');
    Route::delete('/admin/estudiantes/{id}', [EstudianteController::class, 'destroy'])
        ->name('admin.estudiantes.destroy');

    // Rutas de docentes
    Route::get('/admin/docentes', [DocenteController::class, 'index'])
        ->name('admin.docentes');
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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
