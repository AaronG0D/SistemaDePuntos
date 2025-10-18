<?php

use App\Http\Controllers\API\DocenteController;
use App\Http\Controllers\API\RaspberryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Rutas para el docente
    Route::prefix('docente')->group(function () {
        Route::get('/estudiantes/{cursoParalelo}', [DocenteController::class, 'getEstudiantes']);
        Route::post('/reporte-puntos', [DocenteController::class, 'generarReportePuntos']);
        Route::post('/reporte-masivo', [DocenteController::class, 'generarReporteMasivo']);
    });
});

// Rutas para Raspberry Pi (protegidas con API Key)
Route::middleware(['raspberry.api', 'throttle:60,1'])->prefix('raspberry')->group(function () {
    Route::post('/deposito', [RaspberryController::class, 'deposito']);
    Route::get('/verificar/{qr_codigo}', [RaspberryController::class, 'verificarEstudiante']);
});

// Ruta temporal de debug (sin protección para pruebas rápidas)
Route::get('/debug-student/{qr_codigo}', function($qr_codigo) {
    // Primero verificar la estructura de la tabla
    $user = \App\Models\User::where('qr_codigo', $qr_codigo)->first();
    $allUsers = \App\Models\User::select('id', 'qr_codigo', 'nombres', 'primerApellido', 'segundoApellido', 'rol')->get();
    $similar = \App\Models\User::where('qr_codigo', 'LIKE', "%{$qr_codigo}%")->get();
    
    return response()->json([
        'searched_qr' => $qr_codigo,
        'user_found' => $user ? [
            'id' => $user->idUsuario,
            'nombres' => $user->nombres,
            'apellidos' => $user->apellidos,
            'qr_codigo' => $user->qr_codigo,
            'rol' => $user->rol
        ] : null,
        'similar_users' => $similar->map(function($u) {
            return [
                'id' => $u->idUsuario,
                'qr_codigo' => $u->qr_codigo,
                'nombres' => $u->nombres,
                'apellidos' => $u->apellidos,
                'rol' => $u->rol
            ];
        }),
        'total_users' => $allUsers->count(),
        'students_count' => $allUsers->where('rol', 'estudiante')->count(),
        'first_5_students' => $allUsers->where('rol', 'estudiante')->take(5)->values()
    ]);
});

// Rutas temporales de prueba (comentadas - usar las protegidas)
// Route::get('/test-controller', [RaspberryController::class, 'test']);
// Route::post('/test-raspberry', [RaspberryController::class, 'deposito']);
// Route::get('/test-verificar/{qr_codigo}', [RaspberryController::class, 'verificarEstudiante']);

// Rutas para administración de logs de Raspberry (requiere autenticación web)
Route::middleware('auth:sanctum')->prefix('raspberry')->group(function () {
    Route::get('/eventos', [RaspberryController::class, 'getEventos']);
});
