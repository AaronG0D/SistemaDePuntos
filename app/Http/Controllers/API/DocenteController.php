<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CursoParalelo;
use App\Models\Estudiante;
use App\Models\Docente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class DocenteController extends Controller
{
    public function getEstudiantes(Request $request, $cursoParalelo)
    {
        // Usar el modelo Estudiante y relaciones definidas en el proyecto
        $query = Estudiante::with('user.puntaje')
            ->where('idCursoParalelo', $cursoParalelo);

        if ($request->has('search') && strlen($request->get('search')) > 0) {
            $search = $request->get('search');
            $query->whereHas('user', function($q) use ($search) {
                $q->where('nombres', 'LIKE', "%{$search}%")
                  ->orWhere('primerApellido', 'LIKE', "%{$search}%")
                  ->orWhere('segundoApellido', 'LIKE', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('idUser')
            ->paginate(10);

        // Transformar la colección para entregar la forma que espera el frontend
        $paginator->getCollection()->transform(function ($estudiante) {
            return [
                'id' => $estudiante->idUser,
                'nombres' => $estudiante->user->nombres ?? '',
                'apellidos' => trim(($estudiante->user->primerApellido ?? '') . ' ' . ($estudiante->user->segundoApellido ?? '')),
                'puntaje' => $estudiante->user->puntaje->puntajeTotal ?? $estudiante->user->puntaje->total ?? 0,
                'depositos' => [],
            ];
        });

        return response()->json($paginator);
    }

    public function generarReportePuntos(Request $request)
    {
        // Validación básica
        $request->validate([
            'estudiante_id' => 'required',
            'curso_id' => 'required',
            'materias' => 'required|array|min:1'
        ]);

        // Verificar existencia del estudiante en ese curso
        $estudiante = Estudiante::where('idUser', $request->get('estudiante_id'))
            ->where('idCursoParalelo', $request->get('curso_id'))
            ->with('user.puntaje')
            ->first();

        if (!$estudiante) {
            return response()->json(['error' => 'Estudiante no encontrado en el curso especificado'], 404);
        }

        // (Aquí normalmente aplicarías la lógica de asignar/filtrar puntos por materia.)
        // Por ahora devolvemos una confirmación con la selección para que la UI lo confirme.

        return response()->json([
            'success' => true,
            'message' => 'Materias recibidas para el estudiante',
            'estudiante' => [
                'id' => $estudiante->idUser,
                'nombres' => $estudiante->user->nombres ?? '',
                'apellidos' => trim(($estudiante->user->primerApellido ?? '') . ' ' . ($estudiante->user->segundoApellido ?? '')),
                'puntaje' => $estudiante->user->puntaje->puntajeTotal ?? $estudiante->user->puntaje->total ?? 0,
            ],
            'materias' => $request->get('materias')
        ]);
    }

    /**
     * Generar reporte masivo para todos los estudiantes de un curso-paralelo
     * Este endpoint valida entrada, filtra estudiantes y devuelve un resumen JSON
     */
    public function generarReporteMasivo(Request $request)
    {
        $request->validate([
            'curso_id' => 'required',
            'materias' => 'required|array|min:1'
        ]);

        $cursoId = $request->get('curso_id');
        $materias = $request->get('materias');

        // Obtener estudiantes del curso
        $estudiantes = Estudiante::with('user.puntaje')
            ->where('idCursoParalelo', $cursoId)
            ->get();

        // Construir resumen simple: contar estudiantes y mapear algunos datos
        $summary = $estudiantes->map(function ($est) {
            return [
                'id' => $est->idUser,
                'nombres' => $est->user->nombres ?? '',
                'apellidos' => trim(($est->user->primerApellido ?? '') . ' ' . ($est->user->segundoApellido ?? '')),
                'puntaje' => $est->user->puntaje->puntajeTotal ?? $est->user->puntaje->total ?? 0,
            ];
        });

        // Nota: aquí se podrían generar PDFs, tareas en background, etc. Por ahora devolvemos resumen JSON.
        return response()->json([
            'success' => true,
            'message' => 'Reporte masivo encolado (simulado)',
            'total' => $estudiantes->count(),
            'materias' => $materias,
            'estudiantes' => $summary,
        ]);
    }
}
