  -..-..-.--..--.-.-.-.----------------------------------------------------------------------------------------------------------------------------------------<?php

use App\Http\Controllers\API\DocenteController;
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
