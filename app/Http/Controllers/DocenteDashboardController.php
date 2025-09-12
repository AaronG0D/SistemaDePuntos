<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Puntaje;
use App\Exports\EstudiantesMateriaExport;
use App\Exports\PlantillaEstudiantesExport;
use App\Imports\EstudiantesImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

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

        // Determinar período actual (para mostrar conteos en las tarjetas)
        $hoy = now()->toDateString();
        $anioActual = now()->year;
        $periodosHoy = \App\Models\PeriodoAcademico::whereYear('fecha_inicio', $anioActual)
            ->orWhereYear('fecha_fin', $anioActual)
            ->orderBy('fecha_inicio', 'desc')
            ->get(['idPeriodo','nombre','codigo','fecha_inicio','fecha_fin','activo']);
        $periodoPorFecha = $periodosHoy->first(function($p) use ($hoy) {
            return ($p->fecha_inicio <= $hoy) && ($p->fecha_fin >= $hoy);
        });
        // Priorizar el período ACTIVO explícito
        $periodoActivoModelo = \App\Models\PeriodoAcademico::where('activo', true)->first();
        $periodoActualId = optional($periodoActivoModelo ?? $periodoPorFecha ?? $periodosHoy->first())->idPeriodo;

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
                    'estudiantes' => [],
                    'bimestres' => []
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
                // Conteo de estudiantes con atribución en esta materia y período actual por este docente
                $conteoAsignados = 0;
                if ($periodoActualId) {
                    $conteoAsignados = DB::table('asignaciones_puntaje')
                        ->join('puntaje', 'asignaciones_puntaje.idPuntaje', '=', 'puntaje.idPuntaje')
                        ->join('estudiante', 'puntaje.idUser', '=', 'estudiante.idUser')
                        ->where('estudiante.idCursoParalelo', $cp->idCursoParalelo)
                        ->where('asignaciones_puntaje.idPeriodo', $periodoActualId)
                        ->where('asignaciones_puntaje.idDocente', $docente->idDocente)
                        ->where('asignaciones_puntaje.idMateria', $materia->idMateria)
                        ->distinct()
                        ->count('estudiante.idUser');
                }
                $totalEstudiantes = count($cursosYMaterias[$key]['estudiantes'] ?? []);

                $cursosYMaterias[$key]['materias'][$materia->idMateria] = [
                    'idMateria' => $materia->idMateria,
                    'nombre' => $materia->nombre,
                    'asignadosPeriodoActual' => $conteoAsignados,
                    'totalEstudiantes' => $totalEstudiantes,
                ];
            }
        }

        // normalizar materias a arrays indexados
        $final = [];
        foreach ($cursosYMaterias as $k => $v) {
            // Construir bimestres compactos con chips por materia (solo año actual)
            $bimestres = [];
            // Solo el bimestre (período) activo en la tarjeta
            $periodosCard = $periodoActualId ? $periodosHoy->where('idPeriodo', $periodoActualId) : collect();
            foreach ($periodosCard as $p) {
                // Para cada materia del curso, contar asignados en este periodo por este docente
                $materiasChips = [];
                foreach ($v['materias'] as $m) {
                    $conteo = DB::table('asignaciones_puntaje')
                        ->join('puntaje', 'asignaciones_puntaje.idPuntaje', '=', 'puntaje.idPuntaje')
                        ->join('estudiante', 'puntaje.idUser', '=', 'estudiante.idUser')
                        ->where('estudiante.idCursoParalelo', $k)
                        ->where('asignaciones_puntaje.idPeriodo', $p->idPeriodo)
                        ->where('asignaciones_puntaje.idDocente', $docente->idDocente)
                        ->where('asignaciones_puntaje.idMateria', $m['idMateria'])
                        ->distinct()
                        ->count('estudiante.idUser');
                    $materiasChips[] = [
                        'idMateria' => $m['idMateria'],
                        'nombre' => $m['nombre'],
                        'asignados' => $conteo,
                        'totalEstudiantes' => count($v['estudiantes'] ?? []),
                    ];
                }
                $bimestres[] = [
                    'idPeriodo' => $p->idPeriodo,
                    'nombre' => $p->nombre,
                    'codigo' => $p->codigo,
                    'materias' => $materiasChips,
                ];
            }
            $v['bimestres'] = $bimestres;
            $v['materias'] = array_values($v['materias']);
            $v['estudiantes'] = array_values($v['estudiantes']);
            $v['idCursoParalelo'] = $k;
            $final[] = $v;
        }

        return Inertia::render('docente/Dashboard', [
            'cursosYMaterias' => $final
        ]);
    }

    public function cursoDetalle(Request $request, $idCursoParalelo)
    {
        $docente = Docente::where('idUser', Auth::id())->firstOrFail();
        // Seguridad: verificar acceso al curso-paralelo
        if (!$docente->docenteMateriaCursos()->where('idCursoParalelo', $idCursoParalelo)->exists()) {
            abort(403, 'No tienes acceso a este curso');
        }

        // Periodos del año actual y por defecto el que cubre la fecha de hoy
        $hoy = now()->toDateString();
        $anioActual = now()->year;
        $periodos = \App\Models\PeriodoAcademico::whereYear('fecha_inicio', $anioActual)
            ->orWhereYear('fecha_fin', $anioActual)
            ->orderBy('fecha_inicio', 'desc')
            ->get(['idPeriodo','nombre','codigo','fecha_inicio','fecha_fin','activo']);

        // Elegir por defecto el período que cubre hoy; si no hay, usar activo, si no hay, el más reciente
        $periodoPorFecha = $periodos->first(function($p) use ($hoy) {
            return ($p->fecha_inicio <= $hoy) && ($p->fecha_fin >= $hoy);
        });
        $periodoActivo = $periodoPorFecha ?? $periodos->firstWhere('activo', true) ?? $periodos->first();
        $periodoId = (int) ($request->query('periodo_id') ?? optional($periodoActivo)->idPeriodo);

        // Info de curso y materias
        $asignaciones = $docente->docenteMateriaCursos()
            ->with(['materia','cursoParalelo.curso','cursoParalelo.paralelo'])
            ->where('idCursoParalelo', $idCursoParalelo)
            ->get();

        if ($asignaciones->isEmpty()) {
            abort(404);
        }

        $cp = $asignaciones->first()->cursoParalelo;
        $cursoInfo = [
            'idCursoParalelo' => (string)$idCursoParalelo,
            'curso' => [
                'nombre' => $cp->curso->nombre ?? '',
                'paralelo' => $cp->paralelo->nombre ?? ''
            ],
            'materias' => $asignaciones->map(fn($a) => [
                'idMateria' => $a->materia->idMateria,
                'nombre' => $a->materia->nombre,
            ])->values(),
        ];

        // Mapa de atribuciones por materia: materiaId => [idUser,...] para el período seleccionado por este docente
        $atribuidosPorMateria = [];
        foreach ($asignaciones as $a) {
            $ids = DB::table('asignaciones_puntaje')
                ->join('puntaje', 'asignaciones_puntaje.idPuntaje', '=', 'puntaje.idPuntaje')
                ->join('estudiante', 'puntaje.idUser', '=', 'estudiante.idUser')
                ->where('estudiante.idCursoParalelo', $idCursoParalelo)
                ->where('asignaciones_puntaje.idPeriodo', $periodoId)
                ->where('asignaciones_puntaje.idDocente', $docente->idDocente)
                ->where('asignaciones_puntaje.idMateria', $a->materia->idMateria)
                ->pluck('estudiante.idUser')
                ->unique()
                ->values()
                ->all();
            $atribuidosPorMateria[$a->materia->idMateria] = $ids;
        }

        // Estudiantes con total de puntos por periodo seleccionado
        $estudiantes = DB::table('estudiante')
            ->join('usuario', 'estudiante.idUser', '=', 'usuario.id')
            ->leftJoin('puntaje', function($join) use ($periodoId) {
                $join->on('usuario.id', '=', 'puntaje.idUser');
                if ($periodoId) {
                    $join->where('puntaje.idPeriodo', '=', $periodoId);
                }
            })
            ->where('estudiante.idCursoParalelo', $idCursoParalelo)
            ->groupBy('estudiante.idUser','usuario.nombres','usuario.primerApellido','usuario.segundoApellido')
            ->select(
                'estudiante.idUser as id',
                'usuario.nombres',
                DB::raw("CONCAT(IFNULL(usuario.primerApellido,''),' ',IFNULL(usuario.segundoApellido,'')) as apellidos"),
                DB::raw('COALESCE(SUM(puntaje.puntajeTotal),0) as puntaje')
            )
            ->orderBy('usuario.primerApellido')
            ->orderBy('usuario.segundoApellido')
            ->orderBy('usuario.nombres')
            ->paginate(10);

        // Periodo activo para controlar UI
        $periodoActivo = \App\Models\PeriodoAcademico::where('activo', true)->first();

        return Inertia::render('docente/CursoDetalle', [
            'curso' => $cursoInfo,
            'periodos' => $periodos,
            'periodoSeleccionado' => $periodoId,
            'estudiantes' => $estudiantes,
            'atribuidosPorMateria' => $atribuidosPorMateria,
            'periodoActivoId' => $periodoActivo?->idPeriodo,
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

        $periodoId = $request->query('periodo_id');

        $query = DB::table('estudiante')
            ->join('usuario', 'estudiante.idUser', '=', 'usuario.id')
            ->leftJoin('puntaje', function($join) use ($periodoId) {
                $join->on('usuario.id', '=', 'puntaje.idUser');
                if ($periodoId) {
                    $join->where('puntaje.idPeriodo', '=', $periodoId);
                }
            })
            ->where('estudiante.idCursoParalelo', $idCursoParalelo)
            ->select(
                'estudiante.idUser',
                'usuario.nombres',
                'usuario.primerApellido',
                'usuario.segundoApellido',
                DB::raw('COALESCE(SUM(puntaje.puntajeTotal),0) as total_puntos')
            )
            ->groupBy('estudiante.idUser','usuario.nombres','usuario.primerApellido','usuario.segundoApellido');

        // Filtro por nombre
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('usuario.nombres', 'like', "%{$search}%")
                  ->orWhere('usuario.primerApellido', 'like', "%{$search}%")
                  ->orWhere('usuario.segundoApellido', 'like', "%{$search}%");
            });
        }

        $estudiantes = $query
            ->orderBy('usuario.primerApellido')
            ->orderBy('usuario.segundoApellido')
            ->orderBy('usuario.nombres')
            ->paginate(10);

        $estudiantes->getCollection()->transform(function ($row) {
            return [
                'id' => $row->idUser,
                'nombres' => $row->nombres,
                'apellidos' => trim(($row->primerApellido ?? '') . ' ' . ($row->segundoApellido ?? '')),
                'puntaje' => (int) $row->total_puntos,
            ];
        });

        return response()->json($estudiantes);
    }

    public function reportePuntosPorCurso(Request $request, $idCursoParalelo)
    {
        $docente = Docente::where('idUser', Auth::id())->first();
        
        if (!$docente->docenteMateriaCursos()->where('idCursoParalelo', $idCursoParalelo)->exists()) {
            abort(403);
        }

        $periodoId = $request->query('periodo_id');

        $estadisticas = DB::table('estudiante')
            ->join('usuario', 'estudiante.idUser', '=', 'usuario.id')
            ->leftJoin('puntaje', function($join) use ($periodoId) {
                $join->on('usuario.id', '=', 'puntaje.idUser');
                if ($periodoId) {
                    $join->where('puntaje.idPeriodo', '=', $periodoId);
                }
            })
            ->where('estudiante.idCursoParalelo', $idCursoParalelo)
            ->select(
                DB::raw('COUNT(DISTINCT estudiante.idUser) as total_estudiantes'),
                DB::raw('COALESCE(SUM(puntaje.puntajeTotal),0) as puntos_totales'),
                DB::raw('COALESCE(AVG(puntaje.puntajeTotal),0) as promedio_puntos'),
                DB::raw('COALESCE(MAX(puntaje.puntajeTotal),0) as maximo_puntos'),
                DB::raw('MIN(puntaje.puntajeTotal) as minimo_puntos')
            )
            ->first();

        return response()->json($estadisticas);
    }

    /**
     * Exportar a Excel la lista de estudiantes con puntos atribuidos por materia para un curso y período.
     */
    public function exportarMateriaExcel(Request $request, $idCursoParalelo, $idMateria)
    {
        $docente = Docente::where('idUser', Auth::id())->firstOrFail();

        // Seguridad: el docente debe tener esa materia en ese curso
        if (!$docente->docenteMateriaCursos()
            ->where('idCursoParalelo', $idCursoParalelo)
            ->where('idMateria', $idMateria)
            ->exists()) {
            abort(403, 'No tienes acceso a esta materia en este curso');
        }

        // Período: activo por defecto, o idPeriodo solicitado
        $idPeriodo = (int) $request->query('idPeriodo', 0);
        $periodo = null;
        if ($idPeriodo > 0) {
            $periodo = \App\Models\PeriodoAcademico::where('idPeriodo', $idPeriodo)->first();
        }
        if (!$periodo) {
            $periodo = \App\Models\PeriodoAcademico::where('activo', true)->first();
        }
        if (!$periodo) {
            return response()->json(['error' => 'No hay período activo ni válido para exportar'], 422);
        }

        // Query: estudiantes del curso con atribuciones en esa materia y período, sumando puntos
        $estudiantes = DB::table('asignaciones_puntaje as ap')
            ->join('puntaje as p', 'ap.idPuntaje', '=', 'p.idPuntaje')
            ->join('usuario as u', 'p.idUser', '=', 'u.id')
            ->join('estudiante as e', 'u.id', '=', 'e.idUser')
            ->where('e.idCursoParalelo', $idCursoParalelo)
            ->where('ap.idPeriodo', $periodo->idPeriodo)
            ->where('ap.idDocente', $docente->idDocente)
            ->where('ap.idMateria', $idMateria)
            ->groupBy('u.id', 'u.nombres', 'u.primerApellido', 'u.segundoApellido')
            ->select(
                'u.id as idUser',
                'u.nombres',
                'u.primerApellido',
                'u.segundoApellido',
                DB::raw('COUNT(ap.idAsignacion) as registros'),
                DB::raw('COALESCE(SUM(ap.puntos),0) as puntos_atribuidos')
            )
            ->orderBy('u.primerApellido')
            ->orderBy('u.segundoApellido')
            ->orderBy('u.nombres')
            ->get();

        // Obtener información del curso
        $curso = DB::table('curso_paralelo as cp')
            ->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
            ->join('paralelo as pa', 'cp.idParalelo', '=', 'pa.idParalelo')
            ->where('cp.idCursoParalelo', $idCursoParalelo)
            ->select('c.nombre as curso', 'pa.nombre as paralelo')
            ->first();

        $materia = DB::table('materia')->where('idMateria', $idMateria)->value('nombre');

        // Calcular estadísticas
        $estadisticas = [
            'total_puntos' => $estudiantes->sum('puntos_atribuidos'),
            'promedio' => $estudiantes->count() > 0 ? $estudiantes->avg('puntos_atribuidos') : 0,
            'maximo' => $estudiantes->max('puntos_atribuidos') ?? 0,
            'minimo' => $estudiantes->min('puntos_atribuidos') ?? 0,
        ];

        // Obtener información del docente
        $docenteInfo = DB::table('docente as d')
            ->join('usuario as u', 'd.idUser', '=', 'u.id')
            ->where('d.idDocente', $docente->idDocente)
            ->select('u.nombres', 'u.primerApellido', 'u.segundoApellido')
            ->first();

        $docenteCompleto = (object) [
            'nombres' => trim(($docenteInfo->nombres ?? '') . ' ' . ($docenteInfo->primerApellido ?? '') . ' ' . ($docenteInfo->segundoApellido ?? ''))
        ];

        // Crear el export y generar Excel hermoso
        $export = new EstudiantesMateriaExport($estudiantes, $curso, $materia, $periodo, $docenteCompleto, $estadisticas);
        $spreadsheet = $export->generateExcel();

        $filename = 'Reporte_Estudiantes_' . str_replace(' ', '_', $materia) . '_' . str_replace(' ', '_', $curso->curso ?? 'Curso') . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Crear writer y generar el archivo
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ];

        $callback = function() use ($writer) {
            $writer->save('php://output');
        };

        return response()->stream($callback, 200, $headers);
    }

    public function asignarPuntos(Request $request, $idCursoParalelo)
    {
        $docente = Docente::where('idUser', Auth::id())->firstOrFail();
        // Seguridad: verificar acceso del docente al curso-paralelo
        if (!$docente->docenteMateriaCursos()->where('idCursoParalelo', $idCursoParalelo)->exists()) {
            abort(403, 'No tienes acceso a este curso');
        }

        $data = $request->validate([
            'estudiantes' => 'sometimes|array',
            'estudiantes.*' => 'integer|exists:estudiante,idUser',
            'idMateria' => 'required|integer|exists:materia,idMateria',
            'comentario' => 'nullable|string|max:500',
            'idPeriodo' => 'nullable|integer|exists:periodos_academicos,idPeriodo',
            'select_all' => 'sometimes|boolean',
            'search' => 'sometimes|string|nullable',
        ]);

        // Validar que la materia pertenece al docente en este curso
        $tieneMateria = $docente->docenteMateriaCursos()
            ->where('idCursoParalelo', $idCursoParalelo)
            ->where('idMateria', $data['idMateria'])
            ->exists();
        if (!$tieneMateria) {
            return response()->json(['error' => 'No puedes atribuir puntos a esta materia en este curso'], 403);
        }

        // Solo se puede asignar al período ACTIVO
        $periodoActivo = \App\Models\PeriodoAcademico::where('activo', true)->first();
        if (!$periodoActivo) {
            return response()->json(['error' => 'No hay un período académico activo para asignar.'], 422);
        }
        // Si el cliente mandó un idPeriodo distinto, rechazar para evitar confusiones
        if (!empty($data['idPeriodo']) && (int)$data['idPeriodo'] !== (int)$periodoActivo->idPeriodo) {
            return response()->json(['error' => 'Solo se permite asignar al período activo.'], 422);
        }
        $periodoId = (int) $periodoActivo->idPeriodo;

        // Resolver estudiantes destino
        $ids = $data['estudiantes'] ?? [];
        if (($data['select_all'] ?? false) === true && empty($ids)) {
            $q = DB::table('estudiante')
                ->join('usuario', 'estudiante.idUser', '=', 'usuario.id')
                ->where('estudiante.idCursoParalelo', $idCursoParalelo)
                ->select('estudiante.idUser');
            if (!empty($data['search'])) {
                $s = $data['search'];
                $q->where(function($qq) use ($s) {
                    $qq->where('usuario.nombres', 'like', "%{$s}%")
                       ->orWhere('usuario.primerApellido', 'like', "%{$s}%")
                       ->orWhere('usuario.segundoApellido', 'like', "%{$s}%");
                });
            }
            $ids = $q->pluck('estudiante.idUser')->unique()->values()->all();
        }

        if (empty($ids)) {
            return response()->json(['error' => 'Debes seleccionar al menos un estudiante o activar "seleccionar todos" con un filtro.'], 422);
        }

        // Atribuir puntajes existentes del período a la materia (una fila por puntaje)
        $now = now();
        $inserted = 0; $skipped = 0; $estudiantesAfectados = 0;
        foreach ($ids as $idUser) {
            $puntajes = DB::table('puntaje')
                ->where('idUser', $idUser)
                ->where('idPeriodo', $periodoId)
                ->get(['idPuntaje','puntajeTotal']);
            $tuvoInsercion = false;
            foreach ($puntajes as $p) {
                try {
                    DB::table('asignaciones_puntaje')->insert([
                        'idPuntaje' => $p->idPuntaje,
                        'idPeriodo' => $periodoId,
                        'idDocente' => $docente->idDocente,
                        'idMateria' => (int) $data['idMateria'],
                        'fecha_asignacion' => $now,
                        'porcentaje' => 100,
                        'puntos' => (int) $p->puntajeTotal,
                        'comentario' => $data['comentario'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $inserted++;
                    $tuvoInsercion = true;
                } catch (\Throwable $e) {
                    $skipped++;
                }
            }
            if ($tuvoInsercion) { $estudiantesAfectados++; }
        }

        return response()->json([
            'message' => 'Atribución realizada',
            'insertados' => $inserted,
            'omitidos' => $skipped,
            'estudiantes_afectados' => $estudiantesAfectados,
        ], 201);
    }

    public function reportePuntosPorMateria(Request $request, $idCursoParalelo, $idMateria)
    {
        $docente = Docente::where('idUser', Auth::id())->first();
        
        if (!$docente->docenteMateriaCursos()
            ->where('idCursoParalelo', $idCursoParalelo)
            ->where('idMateria', $idMateria)
            ->exists()) {
            abort(403);
        }

        $periodoId = $request->get('periodo_id');

        // Obtener puntos específicamente asignados por el docente para esta materia y período
        $estudiantesConPuntos = DB::table('estudiante')
            ->join('usuario', 'estudiante.idUser', '=', 'usuario.id')
            ->leftJoin('asignaciones_puntaje', function($join) use ($idMateria, $periodoId) {
                $join->on('usuario.id', '=', 'asignaciones_puntaje.idUser')
                     ->where('asignaciones_puntaje.idMateria', $idMateria);
                if ($periodoId) {
                    $join->where('asignaciones_puntaje.idPeriodo', $periodoId);
                }
            })
            ->where('estudiante.idCursoParalelo', $idCursoParalelo)
            ->select(
                'usuario.id',
                'usuario.nombres',
                'usuario.primerApellido',
                'usuario.segundoApellido',
                DB::raw('COALESCE(SUM(asignaciones_puntaje.puntos), 0) as puntos_asignados')
            )
            ->groupBy('usuario.id', 'usuario.nombres', 'usuario.primerApellido', 'usuario.segundoApellido')
            ->get();

        // Calcular estadísticas basadas en puntos asignados
        $totalEstudiantes = $estudiantesConPuntos->count();
        $estudiantesConPuntosAsignados = $estudiantesConPuntos->where('puntos_asignados', '>', 0)->count();
        $puntosAsignadosTotal = $estudiantesConPuntos->sum('puntos_asignados');
        $promedioAsignados = $totalEstudiantes > 0 ? $puntosAsignadosTotal / $totalEstudiantes : 0;

        $estadisticas = [
            'total_estudiantes' => $totalEstudiantes,
            'estudiantes_con_puntos' => $estudiantesConPuntosAsignados,
            'puntos_asignados_total' => $puntosAsignadosTotal,
            'promedio_asignados' => round($promedioAsignados, 2),
            'estudiantes' => $estudiantesConPuntos
        ];

        return response()->json($estadisticas);
    }

    /**
     * Descargar plantilla Excel para importar estudiantes
     */
    public function descargarPlantillaEstudiantes(Request $request)
    {
        $export = new \App\Exports\PlantillaEstudiantesExport();
        return $export->download('Plantilla_Estudiantes_' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Importar estudiantes desde Excel
     */
    public function importarEstudiantes(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240' // 10MB máximo
        ]);

        try {
            $file = $request->file('archivo');
            
            // Verificar que el archivo se subió correctamente
            if (!$file || !$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: Archivo no válido o no se pudo subir'
                ], 422);
            }

            // Crear directorio temp si no existe
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Guardar archivo con nombre único
            $fileName = 'import_' . time() . '_' . $file->getClientOriginalName();
            $fullPath = $tempDir . '/' . $fileName;
            
            if (!$file->move($tempDir, $fileName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: No se pudo guardar el archivo temporalmente'
                ], 422);
            }

            try {
                // Importar estudiantes usando la clase personalizada
                $import = new EstudiantesImport($fullPath);
                $results = $import->import();
                
                // Limpiar archivo temporal
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Importación completada exitosamente',
                    'data' => $results
                ]);
                
            } catch (\Exception $e) {
                // Limpiar archivo temporal en caso de error
                if (isset($fullPath) && file_exists($fullPath)) {
                    unlink($fullPath);
                }
                
                \Log::error('Error en importación de estudiantes: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al importar estudiantes: ' . $e->getMessage()
                ], 500);
            }    
            
        } catch (\Exception $e) {
            // Limpiar archivo temporal si existe
            if (isset($fullPath) && file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            \Log::error('Error en importación de estudiantes:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error durante la importación: ' . $e->getMessage()
            ], 500);
        }
    }
}
