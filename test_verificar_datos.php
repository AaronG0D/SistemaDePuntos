<?php

require_once 'vendor/autoload.php';

use App\Models\Curso;
use App\Models\Paralelo;
use App\Models\CursoParalelo;
use App\Models\Materia;

// Inicializar Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN DE DATOS: CURSOS Y PARALELOS ===\n\n";

try {
    // 1. Verificar datos básicos
    echo "1. Datos básicos:\n";
    $cursos = Curso::all();
    $paralelos = Paralelo::all();
    $cursoParalelos = CursoParalelo::all();
    
    echo "   - Cursos: " . $cursos->count() . "\n";
    echo "   - Paralelos: " . $paralelos->count() . "\n";
    echo "   - Curso-paralelos: " . $cursoParalelos->count() . "\n\n";

    // 2. Verificar cada curso y sus paralelos
    echo "2. Cursos y sus paralelos:\n";
    foreach ($cursos as $curso) {
        echo "   - {$curso->nombre}:\n";
        $paralelosDelCurso = CursoParalelo::where('idCurso', $curso->idCurso)->get();
        echo "     * Total paralelos: " . $paralelosDelCurso->count() . "\n";
        
        foreach ($paralelosDelCurso as $cursoParalelo) {
            $paralelo = Paralelo::find($cursoParalelo->idParalelo);
            echo "       - Paralelo: {$paralelo->nombre}\n";
        }
        echo "\n";
    }

    // 3. Verificar carga con relaciones (como lo hace el controlador)
    echo "3. Carga con relaciones (controlador):\n";
    $cursosConRelaciones = Curso::with(['cursoParalelos.paralelo', 'cursoParalelos.materias'])->get();
    
    foreach ($cursosConRelaciones as $curso) {
        echo "   - {$curso->nombre}:\n";
        echo "     * Paralelos cargados: " . $curso->cursoParalelos->count() . "\n";
        
        foreach ($curso->cursoParalelos as $cursoParalelo) {
            echo "       - Paralelo {$cursoParalelo->paralelo->nombre}: " . $cursoParalelo->materias->count() . " materias\n";
        }
        echo "\n";
    }

    // 4. Verificar si hay inconsistencias
    echo "4. Verificando inconsistencias:\n";
    
    // Verificar si todos los cursos tienen curso-paralelos
    $cursosSinParalelos = 0;
    foreach ($cursos as $curso) {
        $paralelosDelCurso = CursoParalelo::where('idCurso', $curso->idCurso)->count();
        if ($paralelosDelCurso === 0) {
            echo "   ⚠️  Curso '{$curso->nombre}' no tiene paralelos\n";
            $cursosSinParalelos++;
        }
    }
    
    if ($cursosSinParalelos === 0) {
        echo "   ✅ Todos los cursos tienen paralelos\n";
    }
    echo "\n";

    // 5. Verificar paralelos disponibles
    echo "5. Paralelos disponibles:\n";
    foreach ($paralelos as $paralelo) {
        echo "   - {$paralelo->nombre}\n";
    }
    echo "\n";

    echo "=== VERIFICACIÓN COMPLETADA ===\n";
    echo "Si hay problemas, verifica:\n";
    echo "1. Que todos los cursos tengan curso-paralelos\n";
    echo "2. Que las relaciones estén bien definidas en los modelos\n";
    echo "3. Que el controlador esté cargando las relaciones correctamente\n";

} catch (Exception $e) {
    echo "Error durante la verificación: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 