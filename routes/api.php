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
});

// Rutas para administración de logs de Raspberry (requiere autenticación web)
Route::middleware('auth:sanctum')->prefix('raspberry')->group(function () {
    Route::get('/eventos', [RaspberryController::class, 'getEventos']);
});
