<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Puntaje;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocenteDashboardController extends Controller
{
    public function index()
    {
        // Obtener el docente autenticado con relaciones necesarias
        $docente = Docente::with([
            'docenteMateriaCursos.materia',
            'docenteMateriaCursos.cursoParalelo.curso',
            'docenteMateriaCursos.cursoParalelo.paralelo',
            'docenteMateriaCursos.cursoParalelo.estudiantes.user.puntaje'
        ])->where('idUser', Auth::id())->first();

        if (!$docente) {
            abort(404, 'No se encontró el docente');
        }

        // Consolidar por curso-paralelo en un array plano para Inertia
        $cursosYMaterias = [];

        foreach ($docente->docenteMateriaCursos as $asignacion) {
            $cp = $asignacion->cursoParalelo;
            if (!$cp) {
                continue;
            }

            $key = (string) $cp->idCursoParalelo;

            if (!isset($cursosYMaterias[$key])) {
                $cursosYMaterias[$key] = [
                    'curso' => [
                        'nombre' => $cp->curso->nombre ?? '',
                        'paralelo' => $cp->paralelo->nombre ?? ''
                    ],
                    'materias' => [],
                    'estudiantes' => []
                ];

                // agregar estudiantes del curso-paralelo
                foreach ($cp->estudiantes ?? [] as $estudiante) {
                    $user = $estudiante->user ?? null;
                    $cursosYMaterias[$key]['estudiantes'][] = [
                        'id' => $estudiante->idUser,
                        'nombres' => $user->nombres ?? '',
                        'apellidos' => trim(($user->primerApellido ?? '') . ' ' . ($user->segundoApellido ?? '')),
                        'puntaje' => $user->puntaje->total ?? 0
                    ];
                }
            }

            // agregar materia (evitar duplicados usando clave)
            $materia = $asignacion->materia;
            if ($materia) {
                $cursosYMaterias[$key]['materias'][$materia->idMateria] = [
                    'idMateria' => $materia->idMateria,
                    'nombre' => $materia->nombre
                ];
            }
        }

        // normalizar materias a arrays indexados
        $final = [];
        foreach ($cursosYMaterias as $k => $v) {
            $v['materias'] = array_values($v['materias']);
            $v['estudiantes'] = array_values($v['estudiantes']);
            $v['idCursoParalelo'] = $k;
            $final[] = $v;
        }

        return Inertia::render('docente/Dashboard', [
            'cursosYMaterias' => $final
        ]);
    }

    public function estudiantesPorCurso(Request $request, $idCursoParalelo)
    {
        $docente = Docente::where('idUser', Auth::id())->first();
        
        // Verificar que el docente tenga acceso a este curso
        $tieneAcceso = $docente->docenteMateriaCursos()
            ->where('idCursoParalelo', $idCursoParalelo)
            ->exists();

        if (!$tieneAcceso) {
            abort(403, 'No tienes acceso a este curso');
        }

        $query = Estudiante::with(['user.puntaje'])
            ->where('idCursoParalelo', $idCursoParalelo);

        // Filtro por nombre
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('primerApellido', 'like', "%{$search}%")
                  ->orWhere('segundoApellido', 'like', "%{$search}%");
            });
        }

        $estudiantes = $query->paginate(10)
            ->through(function ($estudiante) {
                return [
                    'id' => $estudiante->idUser,
                    'nombres' => $estudiante->user->nombres,
                    'apellidos' => $estudiante->user->primerApellido . ' ' . $estudiante->user->segundoApellido,
                    'puntaje' => $estudiante->user->puntaje?->total ?? 0,
                    'depositos' => $estudiante->user->puntaje?->depositos ?? []
                ];
            });

        return response()->json($estudiantes);
    }

    public function reportePuntosPorCurso($idCursoParalelo)
    {
        $docente = Docente::where('idUser', Auth::id())->first();
        
        if (!$docente->docenteMateriaCursos()->where('idCursoParalelo', $idCursoParalelo)->exists()) {
            abort(403);
        }

        $estadisticas = DB::table('estudiante')
            ->join('usuario', 'estudiante.idUser', '=', 'usuario.id')
            ->leftJoin('puntaje', 'usuario.id', '=', 'puntaje.idUser')
            ->where('estudiante.idCursoParalelo', $idCursoParalelo)
            ->select(
                DB::raw('COUNT(DISTINCT estudiante.idUser) as total_estudiantes'),
                DB::raw('SUM(puntaje.total) as puntos_totales'),
                DB::raw('AVG(puntaje.total) as promedio_puntos'),
                DB::raw('MAX(puntaje.total) as maximo_puntos'),
                DB::raw('MIN(puntaje.total) as minimo_puntos')
            )
            ->first();

        return response()->json($estadisticas);
    }

    public function reportePuntosPorMateria($idCursoParalelo, $idMateria)
    {
        $docente = Docente::where('idUser', Auth::id())->first();
        
        if (!$docente->docenteMateriaCursos()
            ->where('idCursoParalelo', $idCursoParalelo)
            ->where('idMateria', $idMateria)
            ->exists()) {
            abort(403);
        }

        $estadisticas = DB::table('estudiante')
            ->join('usuario', 'estudiante.idUser', '=', 'usuario.id')
            ->leftJoin('puntaje', 'usuario.id', '=', 'puntaje.idUser')
            ->where('estudiante.idCursoParalelo', $idCursoParalelo)
            ->select(
                DB::raw('COUNT(DISTINCT estudiante.idUser) as total_estudiantes'),
                DB::raw('SUM(puntaje.total) as puntos_totales'),
                DB::raw('AVG(puntaje.total) as promedio_puntos'),
                DB::raw('MAX(puntaje.total) as maximo_puntos'),
                DB::raw('MIN(puntaje.total) as minimo_puntos')
            )
            ->first();

        return response()->json($estadisticas);
    }
}
