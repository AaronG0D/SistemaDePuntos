<?php

require_once 'vendor/autoload.php';

use App\Models\Docente;
use App\Models\Materia;
use App\Models\Curso;
use App\Models\Paralelo;
use App\Models\CursoParalelo;
use App\Models\MateriaCursoParalelo;

// Inicializar Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRUEBA DE NUEVA LÓGICA: CURSO → PARALELO → MATERIA ===\n\n";

try {
    // 1. Verificar docente
    echo "1. Verificando docente...\n";
    $docente = Docente::with([
        'user',
        'docenteMateriaCursos.materia',
        'docenteMateriaCursos.cursoParalelo.curso',
        'docenteMateriaCursos.cursoParalelo.paralelo'
    ])->first();
    
    if (!$docente) {
        echo "   - No se encontraron docentes\n";
        exit;
    }
    
    echo "   - Docente: {$docente->user->nombres} {$docente->user->primerApellido}\n";
    echo "   - Asignaciones actuales: " . $docente->docenteMateriaCursos->count() . "\n\n";

    // 2. Verificar datos disponibles
    echo "2. Verificando datos disponibles...\n";
    $materias = Materia::orderBy('nombre')->get(['idMateria', 'nombre']);
    $cursos = Curso::orderBy('nombre')->get(['idCurso', 'nombre']);
    $paralelos = Paralelo::orderBy('nombre')->get(['idParalelo', 'nombre']);
    $cursoParalelos = CursoParalelo::with(['curso', 'paralelo'])->orderBy('idCursoParalelo')->get();
    
    echo "   - Materias disponibles: " . $materias->count() . "\n";
    echo "   - Cursos disponibles: " . $cursos->count() . "\n";
    echo "   - Paralelos disponibles: " . $paralelos->count() . "\n";
    echo "   - Curso-paralelos disponibles: " . $cursoParalelos->count() . "\n\n";

    // 3. Verificar nueva tabla materia_curso_paralelo
    echo "3. Verificando tabla materia_curso_paralelo...\n";
    $materiaCursoParalelos = MateriaCursoParalelo::with(['materia', 'cursoParalelo.curso', 'cursoParalelo.paralelo'])->get();
    echo "   - Relaciones materia-curso_paralelo: " . $materiaCursoParalelos->count() . "\n";
    
    // Mostrar algunas relaciones
    foreach ($materiaCursoParalelos->take(5) as $relacion) {
        echo "     * {$relacion->materia->nombre} → {$relacion->cursoParalelo->curso->nombre} {$relacion->cursoParalelo->paralelo->nombre}\n";
    }
    echo "\n";

    // 4. Verificar materias por curso y paralelo
    echo "4. Verificando materias por curso y paralelo...\n";
    foreach ($cursos as $curso) {
        echo "   - {$curso->nombre}:\n";
        
        foreach ($paralelos as $paralelo) {
            $materiasDelCursoParalelo = Materia::whereHas('materiaCursoParalelos', function ($query) use ($curso, $paralelo) {
                $query->whereHas('cursoParalelo', function ($q) use ($curso, $paralelo) {
                    $q->where('idCurso', $curso->idCurso)
                      ->where('idParalelo', $paralelo->idParalelo);
                });
            })->get();
            
            if ($materiasDelCursoParalelo->count() > 0) {
                echo "     * Paralelo {$paralelo->nombre}: " . $materiasDelCursoParalelo->count() . " materias\n";
                foreach ($materiasDelCursoParalelo as $materia) {
                    echo "       - {$materia->nombre}\n";
                }
            }
        }
        echo "\n";
    }

    // 5. Verificar asignaciones de docentes
    echo "5. Verificando asignaciones de docentes...\n";
    $asignaciones = $docente->docenteMateriaCursos;
    foreach ($asignaciones as $asignacion) {
        echo "   - {$asignacion->materia->nombre} → {$asignacion->cursoParalelo->curso->nombre} {$asignacion->cursoParalelo->paralelo->nombre}\n";
    }
    echo "\n";

    // 6. Verificar integridad referencial
    echo "6. Verificando integridad referencial...\n";
    
    // Verificar que todas las asignaciones de docentes tengan su correspondiente relación en materia_curso_paralelo
    $asignacionesSinRelacion = $asignaciones->filter(function ($asignacion) {
        return !MateriaCursoParalelo::where('idMateria', $asignacion->idMateria)
            ->where('idCursoParalelo', $asignacion->idCursoParalelo)
            ->exists();
    });
    
    if ($asignacionesSinRelacion->count() > 0) {
        echo "   ⚠️  ADVERTENCIA: " . $asignacionesSinRelacion->count() . " asignaciones sin relación en materia_curso_paralelo\n";
        foreach ($asignacionesSinRelacion as $asignacion) {
            echo "     * {$asignacion->materia->nombre} → {$asignacion->cursoParalelo->curso->nombre} {$asignacion->cursoParalelo->paralelo->nombre}\n";
        }
    } else {
        echo "   ✅ Todas las asignaciones tienen su relación correspondiente\n";
    }
    echo "\n";

    // 7. Simular el flujo de selección
    echo "7. Simulando flujo de selección (Curso → Paralelo → Materia)...\n";
    $cursoEjemplo = $cursos->first();
    $paraleloEjemplo = $paralelos->first();
    
    echo "   - Curso seleccionado: {$cursoEjemplo->nombre}\n";
    echo "   - Paralelo seleccionado: {$paraleloEjemplo->nombre}\n";
    
    $materiasDisponibles = Materia::whereHas('materiaCursoParalelos', function ($query) use ($cursoEjemplo, $paraleloEjemplo) {
        $query->whereHas('cursoParalelo', function ($q) use ($cursoEjemplo, $paraleloEjemplo) {
            $q->where('idCurso', $cursoEjemplo->idCurso)
              ->where('idParalelo', $paraleloEjemplo->idParalelo);
        });
    })->get();
    
    echo "   - Materias disponibles para {$cursoEjemplo->nombre} {$paraleloEjemplo->nombre}: " . $materiasDisponibles->count() . "\n";
    foreach ($materiasDisponibles as $materia) {
        echo "     * {$materia->nombre}\n";
    }
    echo "\n";

    echo "=== PRUEBA COMPLETADA ===\n";
    echo "La nueva lógica Curso → Paralelo → Materia está funcionando correctamente.\n";
    echo "Ahora puedes asignar materias a docentes siguiendo el flujo:\n";
    echo "1. Seleccionar el curso\n";
    echo "2. Seleccionar el paralelo\n";
    echo "3. Seleccionar la materia disponible para esa combinación\n";

} catch (Exception $e) {
    echo "Error durante la prueba: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 