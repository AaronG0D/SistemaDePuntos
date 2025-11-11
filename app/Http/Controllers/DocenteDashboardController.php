<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Puntaje;
use App\Exports\EstudiantesMateriaExport;
use App\Exports\PlantillaEstudiantesExport;
use App\Exports\ReporteDocenteExport;
use App\Imports\EstudiantesImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DocenteDashboardController extends Controller
{
    public function index(Request $request)
    {
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

        // Obtener el docente autenticado con relaciones necesarias
        $docente = Docente::with([
            'docenteMateriaCursos.materia',
            'docenteMateriaCursos.cursoParalelo.curso',
            'docenteMateriaCursos.cursoParalelo.paralelo',
            'docenteMateriaCursos.cursoParalelo.estudiantes.user.puntajes' => function ($query) use ($periodoActualId) {
                if ($periodoActualId) {
                    $query->where('puntaje.idPeriodo', $periodoActualId);
                }
            }
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
                    'estudiantes' => [],
                    'bimestres' => []
                ];

                // agregar estudiantes del curso-paralelo
                foreach ($cp->estudiantes ?? [] as $estudiante) {
                    $user = $estudiante->user ?? null;
                    if ($user) {
                        $cursosYMaterias[$key]['estudiantes'][] = [
                            'id' => $estudiante->idUser,
                            'nombres' => $user->nombres ?? '',
                            'apellidos' => trim(($user->primerApellido ?? '') . ' ' . ($user->segundoApellido ?? '')),
                            'puntaje' => $user->puntajes->first()->puntos ?? 0
                        ];
                    }
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

        // normalizar materias a arrays indexados y ordenar estudiantes
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
            
            // Ordenar estudiantes por puntaje descendente y tomar el top 10, guardando el total real
            $estudiantes = collect($v['estudiantes'])->sortByDesc('puntaje')->values()->all();
            $v['estudiantesTotal'] = count($estudiantes);
            $v['estudiantes'] = array_slice($estudiantes, 0, 10);

            $v['idCursoParalelo'] = $k;
            $final[] = $v;
        }

        // Obtener datos para las vistas avanzadas
        $reportesData = $this->getReportesData($request);
        $asignacionData = $this->getAsignacionData($request);
        $estadisticasData = $this->getEstadisticasData($request);
        $gestionData = $this->getGestionData($request);

        return Inertia::render('docente/Dashboard', [
            'docente' => [
                'id' => $docente->idDocente,
                'nombres' => $docente->user->nombres,
                'apellidos' => trim(($docente->user->primerApellido ?? '') . ' ' . ($docente->user->segundoApellido ?? ''))
            ],
            'cursosYMaterias' => $final,
            'periodoActualId' => $periodoActualId,
            'periodos' => $periodosHoy,
            // Datos para las vistas avanzadas integradas
            'reportesData' => $reportesData,
            'asignacionData' => $asignacionData,
            'estadisticasData' => $estadisticasData,
            'gestionData' => $gestionData,
            'activeView' => $request->get('view', 'overview') // Vista activa por defecto
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

        // Estudiantes con total de puntos por periodo seleccionado (desglosados por tipo)
        $estudiantesQuery = DB::table('estudiante')
            ->join('usuario', 'estudiante.idUser', '=', 'usuario.id')
            ->leftJoin('puntaje', function($join) use ($periodoId) {
                $join->on('usuario.id', '=', 'puntaje.idUser');
                if ($periodoId) {
                    $join->where('puntaje.idPeriodo', '=', $periodoId);
                }
            })
            ->where('estudiante.idCursoParalelo', $idCursoParalelo)
            ->groupBy('estudiante.idUser', 'usuario.nombres', 'usuario.primerApellido', 'usuario.segundoApellido')
            ->select(
                'estudiante.idUser as id',
                'usuario.nombres',
                'usuario.primerApellido',
                'usuario.segundoApellido',
                DB::raw("CONCAT(IFNULL(usuario.primerApellido,''),' ',IFNULL(usuario.segundoApellido,'')) as apellidos"),
                DB::raw("COALESCE(SUM(CASE WHEN puntaje.tipo_puntaje = 'depositos' THEN puntaje.puntos ELSE 0 END), 0) as puntos_depositos"),
                DB::raw("COALESCE(SUM(CASE WHEN puntaje.tipo_puntaje = 'extracurricular' THEN puntaje.puntos ELSE 0 END), 0) as puntos_extracurriculares"),
                DB::raw('COALESCE(SUM(puntaje.puntos), 0) as puntaje')
            );

        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $estudiantesQuery->where(function($q) use ($search) {
                $q->where('usuario.nombres', 'like', "%{$search}%")
                  ->orWhere('usuario.primerApellido', 'like', "%{$search}%")
                  ->orWhere('usuario.segundoApellido', 'like', "%{$search}%");
            });
        }

        $estudiantes = $estudiantesQuery->orderBy('usuario.primerApellido')
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
                DB::raw('COALESCE(SUM(puntaje.puntos),0) as total_puntos')
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
                DB::raw('COALESCE(SUM(puntaje.puntos),0) as puntos_totales'),
                DB::raw('COALESCE(AVG(puntaje.puntos),0) as promedio_puntos'),
                DB::raw('COALESCE(MAX(puntaje.puntos),0) as maximo_puntos'),
                DB::raw('MIN(puntaje.puntos) as minimo_puntos')
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
        $inserted = 0; $updated = 0; $skipped = 0; $estudiantesAfectados = 0;
        
        DB::beginTransaction();
        try {
            foreach ($ids as $idUser) {
                $puntajes = DB::table('puntaje')
                    ->where('idUser', $idUser)
                    ->where('idPeriodo', $periodoId)
                    ->get(['idPuntaje','puntos']);
                
                $tuvoInsercion = false;
                foreach ($puntajes as $p) {
                    // Verificar si ya existe la asignación
                    $asignacionExistente = DB::table('asignaciones_puntaje')
                        ->where('idPuntaje', $p->idPuntaje)
                        ->where('idDocente', $docente->idDocente)
                        ->where('idMateria', (int) $data['idMateria'])
                        ->first();
                    
                    if ($asignacionExistente) {
                        // Si existe, actualizar puntos sumando
                        DB::table('asignaciones_puntaje')
                            ->where('idAsignacion', $asignacionExistente->idAsignacion)
                            ->update([
                                'puntos' => DB::raw('puntos + ' . (int) $p->puntos),
                                'comentario' => $data['comentario'] ?? $asignacionExistente->comentario,
                                'updated_at' => $now,
                            ]);
                        $updated++;
                        $tuvoInsercion = true;
                    } else {
                        // Si no existe, crear nueva asignación
                        DB::table('asignaciones_puntaje')->insert([
                            'idPuntaje' => $p->idPuntaje,
                            'idPeriodo' => $periodoId,
                            'idDocente' => $docente->idDocente,
                            'idMateria' => (int) $data['idMateria'],
                            'fecha_asignacion' => $now,
                            'puntos' => (int) $p->puntos,
                            'comentario' => $data['comentario'] ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                        $inserted++;
                        $tuvoInsercion = true;
                    }
                }
                if ($tuvoInsercion) { $estudiantesAfectados++; }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al asignar puntos: ' . $e->getMessage());
            return response()->json(['error' => 'Error al asignar puntos: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Atribución realizada exitosamente',
            'insertados' => $inserted,
            'actualizados' => $updated,
            'estudiantes_afectados' => $estudiantesAfectados,
        ], 201);
    }

    /**
     * Asignar puntos extracurriculares manuales (campañas, actividades, concursos, eventos)
     */
    public function asignarPuntosExtracurriculares(Request $request, $idCursoParalelo)
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
            'idPeriodo' => 'nullable|integer|exists:periodos_academicos,idPeriodo',
            'puntos' => 'required|integer|min:1',
            'comentario' => 'nullable|string|max:500',
            'select_all' => 'sometimes|boolean',
            'search' => 'sometimes|string|nullable',
        ]);

        // Validar que la materia pertenece al docente en este curso
        $tieneMateria = $docente->docenteMateriaCursos()
            ->where('idCursoParalelo', $idCursoParalelo)
            ->where('idMateria', $data['idMateria'])
            ->exists();
        
        if (!$tieneMateria) {
            return response()->json(['error' => 'No puedes asignar puntos a esta materia en este curso'], 403);
        }

        // Usar el período seleccionado o el período activo
        if (!empty($data['idPeriodo'])) {
            $periodoId = (int) $data['idPeriodo'];
        } else {
            $periodoActivo = \App\Models\PeriodoAcademico::where('activo', true)->first();
            if (!$periodoActivo) {
                return response()->json(['error' => 'No hay un período académico activo para asignar.'], 422);
            }
            $periodoId = $periodoActivo->idPeriodo;
        }
        
        $puntosNuevos = (int) $data['puntos'];

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
            return response()->json(['error' => 'Debes seleccionar al menos un estudiante.'], 422);
        }

        // Asignar puntos extracurriculares
        $estudiantesAfectados = 0;
        $now = now();

        DB::beginTransaction();
        try {
            foreach ($ids as $idUser) {
                // Paso 1: Buscar si existe un puntaje extracurricular para este estudiante en este periodo
                $puntajeExtracurricular = DB::table('puntaje')
                    ->where('idUser', $idUser)
                    ->where('idPeriodo', $periodoId)
                    ->where('tipo_puntaje', 'extracurricular')
                    ->first();

                if ($puntajeExtracurricular) {
                    // Ya existe un puntaje extracurricular, usar ese
                    $idPuntaje = $puntajeExtracurricular->idPuntaje;
                    
                    // Sumar puntos en la tabla puntaje
                    DB::table('puntaje')
                        ->where('idPuntaje', $idPuntaje)
                        ->update([
                            'puntos' => DB::raw('puntos + ' . $puntosNuevos),
                            'comentario' => $data['comentario'] ?? DB::raw('comentario'),
                            'updated_at' => $now,
                        ]);
                    
                    // Paso 2: Verificar si ya existe asignación para esta materia y docente
                    $asignacionExistente = DB::table('asignaciones_puntaje')
                        ->where('idPuntaje', $idPuntaje)
                        ->where('idMateria', $data['idMateria'])
                        ->where('idDocente', $docente->idDocente)
                        ->first();
                    
                    if ($asignacionExistente) {
                        // Ya existe la asignación, solo sumar puntos
                        DB::table('asignaciones_puntaje')
                            ->where('idAsignacion', $asignacionExistente->idAsignacion)
                            ->update([
                                'puntos' => DB::raw('puntos + ' . $puntosNuevos),
                                'comentario' => $data['comentario'] ?? DB::raw('comentario'),
                                'updated_at' => $now,
                            ]);
                    } else {
                        // No existe asignación para esta materia/docente, crear nueva
                        DB::table('asignaciones_puntaje')->insert([
                            'idPuntaje' => $idPuntaje,
                            'idPeriodo' => $periodoId,
                            'idDocente' => $docente->idDocente,
                            'idMateria' => (int) $data['idMateria'],
                            'fecha_asignacion' => $now,
                            'puntos' => $puntosNuevos,
                            'comentario' => $data['comentario'] ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                } else {
                    // No existe puntaje extracurricular, crear nuevo
                    $puntaje = Puntaje::create([
                        'idUser' => $idUser,
                        'idPeriodo' => $periodoId,
                        'puntos' => $puntosNuevos,
                        'tipo_puntaje' => 'extracurricular',
                        'comentario' => $data['comentario'] ?? null,
                        'fechaAsignacion' => $now,
                        'estado' => 'activo',
                    ]);

                    // Crear registro en asignaciones_puntaje
                    DB::table('asignaciones_puntaje')->insert([
                        'idPuntaje' => $puntaje->idPuntaje,
                        'idPeriodo' => $periodoId,
                        'idDocente' => $docente->idDocente,
                        'idMateria' => (int) $data['idMateria'],
                        'fecha_asignacion' => $now,
                        'puntos' => $puntosNuevos,
                        'comentario' => $data['comentario'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $estudiantesAfectados++;
            }

            DB::commit();

           return back()->with('success', 'Puntos asignados correctamente a ' . $estudiantesAfectados . ' estudiantes');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al asignar puntos extracurriculares: ' . $e->getMessage());
            return back()->with('error', 'Error al asignar puntos: ' . $e->getMessage());
        }
    }

    /**
     * Obtener puntajes desglosados por tipo (depósitos y extracurricular) para una materia
     */
    public function obtenerPuntajesPorTipo(Request $request, $idCursoParalelo, $idMateria)
    {
        $docente = Docente::where('idUser', Auth::id())->firstOrFail();
        
        // Verificar acceso
        if (!$docente->docenteMateriaCursos()
            ->where('idCursoParalelo', $idCursoParalelo)
            ->where('idMateria', $idMateria)
            ->exists()) {
            abort(403, 'No tienes acceso a esta materia en este curso');
        }

        $periodoId = $request->get('periodo_id');
        if (!$periodoId) {
            $periodoActivo = \App\Models\PeriodoAcademico::where('activo', true)->first();
            $periodoId = $periodoActivo?->idPeriodo;
        }

        // Consulta desde asignaciones_puntaje para obtener puntos desglosados por tipo
        $puntajes = DB::table('asignaciones_puntaje as ap')
            ->join('puntaje as p', 'p.idPuntaje', '=', 'ap.idPuntaje')
            ->join('usuario as u', 'u.id', '=', 'p.idUser')
            ->join('estudiante as e', 'e.idUser', '=', 'u.id')
            ->where('e.idCursoParalelo', $idCursoParalelo)
            ->where('ap.idMateria', $idMateria)
            ->where('p.idPeriodo', $periodoId)
            ->select(
                'u.id as estudiante_id',
                'u.nombres',
                'u.primerApellido',
                'u.segundoApellido',
                DB::raw("SUM(CASE WHEN p.tipo_puntaje = 'depositos' THEN ap.puntos ELSE 0 END) as puntos_depositos"),
                DB::raw("SUM(CASE WHEN p.tipo_puntaje = 'extracurricular' THEN ap.puntos ELSE 0 END) as puntos_extracurriculares"),
                DB::raw("SUM(ap.puntos) as total")
            )
            ->groupBy('u.id', 'u.nombres', 'u.primerApellido', 'u.segundoApellido')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'puntajes' => $puntajes,
            'periodo_id' => $periodoId,
        ]);
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

        // Obtener el total REAL de estudiantes en el curso
        $totalEstudiantesReales = Estudiante::where('idCursoParalelo', $idCursoParalelo)->count();

        // Obtener estadísticas de puntos ASIGNADOS para esta materia específica
        $estadisticasMateria = DB::table('asignaciones_puntaje as ap')
            ->join('puntaje as p', 'ap.idPuntaje', '=', 'p.idPuntaje')
            ->where('ap.idMateria', $idMateria)
            ->when($periodoId, function($query) use ($periodoId) {
                return $query->where('ap.idPeriodo', $periodoId);
            })
            ->select(
                DB::raw('COUNT(DISTINCT ap.idUser) as estudiantes_con_puntos'),
                DB::raw("COALESCE(SUM(CASE WHEN p.tipo_puntaje = 'depositos' THEN ap.puntos ELSE 0 END), 0) as puntos_depositos_total"),
                DB::raw("COALESCE(SUM(CASE WHEN p.tipo_puntaje = 'extracurricular' THEN ap.puntos ELSE 0 END), 0) as puntos_extracurriculares_total"),
                DB::raw('COALESCE(SUM(ap.puntos), 0) as puntos_asignados_total')
            )
            ->first();

        $estudiantesConPuntos = $estadisticasMateria->estudiantes_con_puntos ?? 0;
        $puntosAsignadosTotal = $estadisticasMateria->puntos_asignados_total ?? 0;
        $puntosDepositosTotal = $estadisticasMateria->puntos_depositos_total ?? 0;
        $puntosExtracurricularesTotal = $estadisticasMateria->puntos_extracurriculares_total ?? 0;
        $promedioAsignados = $totalEstudiantesReales > 0 ? $puntosAsignadosTotal / $totalEstudiantesReales : 0;

        $estadisticas = [
            'total_estudiantes' => $totalEstudiantesReales,
            'estudiantes_con_puntos' => $estudiantesConPuntos,
            'puntos_asignados_total' => $puntosAsignadosTotal,
            'puntos_depositos_total' => $puntosDepositosTotal,
            'puntos_extracurriculares_total' => $puntosExtracurricularesTotal,
            'puntos_disponibles_total' => 0,
            'puntos_sin_asignar' => 0,
            'promedio_asignados' => round($promedioAsignados, 2),
        ];

        return response()->json($estadisticas);
    }


    
     public function rankingCursos(Request $request)
    {
        $docente = Docente::where('idUser', Auth::id())->firstOrFail();

        // Obtener todos los cursos paralelos del docente
        $cursosParalelos = $docente->docenteMateriaCursos()
            ->with(['cursoParalelo.curso', 'cursoParalelo.paralelo'])
            ->get()
            ->pluck('cursoParalelo')
            ->unique('idCursoParalelo');

        // Periodo activo
        $periodoActivo = \App\Models\PeriodoAcademico::where('activo', true)->first();
        
        $ranking = [];
        foreach ($cursosParalelos as $cp) {
            if ($cp && $cp->curso && $cp->paralelo) {  // Verificar que existan las relaciones
                // Obtener la suma de puntos de todos los estudiantes del curso-paralelo
                // El campo 'puntos' ya está calculado por el trigger, no necesitamos SUM()
                $puntajeTotal = DB::table('estudiante')
                    ->join('puntaje', 'estudiante.idUser', '=', 'puntaje.idUser')
                    ->where('estudiante.idCursoParalelo', $cp->idCursoParalelo)
                    ->where('puntaje.idPeriodo', $periodoActivo->idPeriodo)
                    ->sum('puntaje.puntos'); // Sumar los puntos ya calculados por el trigger

                $ranking[] = [
                    'idCursoParalelo' => $cp->idCursoParalelo,
                    'nombreCurso' => $cp->curso->nombre . ' "' . $cp->paralelo->nombre . '"',
                    'puntajeTotal' => (int) $puntajeTotal,
                ];
            }
        }

        // Ordenar por puntaje descendente
        $ranking = collect($ranking)->sortByDesc('puntajeTotal')->values()->all();

        return Inertia::render('docente/CursosRanking', [
            'ranking' => $ranking,
            'periodoActivo' => $periodoActivo,
        ]);
    }

    public function cursoRanking(Request $request, $idCursoParalelo)
	{
		$docente = Docente::where('idUser', Auth::id())->firstOrFail();

		// Seguridad: verificar acceso al curso-paralelo
		if (!$docente->docenteMateriaCursos()->where('idCursoParalelo', $idCursoParalelo)->exists()) {
			abort(403, 'No tienes acceso a este curso');
		}

		// Obtener asignaciones para construir información del curso y materias
		$asignaciones = $docente->docenteMateriaCursos()
			->with(['materia', 'cursoParalelo.curso', 'cursoParalelo.paralelo'])
			->where('idCursoParalelo', $idCursoParalelo)
			->get();

		if ($asignaciones->isEmpty()) {
			abort(404, 'Curso no encontrado');
		}

		$cp = $asignaciones->first()->cursoParalelo;
		// Garantizar que los datos de curso y paralelo estén definidos
		$cursoInfo = [
			'idCursoParalelo' => (string) $idCursoParalelo,
			'curso' => [
				'nombre' => $cp->curso->nombre ?? 'Curso no definido',
				'paralelo' => $cp->paralelo->nombre ?? 'Paralelo no definido'
			],
			'materias' => $asignaciones->map(fn($a) => [
				'idMateria' => $a->materia->idMateria,
				'nombre' => $a->materia->nombre,
			])->values()->all(),
		];

		// Periodos del año actual y por defecto el que cubre la fecha de hoy
		$hoy = now()->toDateString();
		$anioActual = now()->year;
		$periodos = \App\Models\PeriodoAcademico::whereYear('fecha_inicio', $anioActual)
			->orWhereYear('fecha_fin', $anioActual)
			->orderBy('fecha_inicio', 'desc')
			->get(['idPeriodo','nombre','codigo','fecha_inicio','fecha_fin','activo']);

		$periodoPorFecha = $periodos->first(function($p) use ($hoy) {
			return ($p->fecha_inicio <= $hoy) && ($p->fecha_fin >= $hoy);
		});
		$periodoActivoModelo = \App\Models\PeriodoAcademico::where('activo', true)->first();
		$periodoDefault = $periodoPorFecha ?? $periodoActivoModelo ?? $periodos->first();
		$periodoId = (int) ($request->query('periodo_id') ?? optional($periodoDefault)->idPeriodo);

		// Query: estudiantes con total de puntos por periodo seleccionado
		$estudiantesQuery = DB::table('estudiante')
			->join('usuario', 'estudiante.idUser', '=', 'usuario.id')
			->leftJoin('puntaje', function($join) use ($periodoId) {
				$join->on('usuario.id', '=', 'puntaje.idUser');
				if ($periodoId) {
					$join->where('puntaje.idPeriodo', '=', $periodoId);
				}
			})
			->where('estudiante.idCursoParalelo', $idCursoParalelo)
			->groupBy('estudiante.idUser','usuario.nombres','usuario.primerApellido','usuario.segundoApellido','usuario.email')
			->select(
				'estudiante.idUser as id',
				'usuario.nombres',
				'usuario.primerApellido',
				'usuario.segundoApellido',
				'usuario.email',
				DB::raw("CONCAT(IFNULL(usuario.primerApellido,''),' ',IFNULL(usuario.segundoApellido,'')) as apellidos"),
				DB::raw('COALESCE(SUM(puntaje.puntos),0) as puntaje')
			);

		// Filtro por búsqueda
		if ($request->has('search') && $request->input('search')) {
			$search = $request->input('search');
			$estudiantesQuery->where(function($q) use ($search) {
				$q->where('usuario.nombres', 'like', "%{$search}%")
				  ->orWhere('usuario.primerApellido', 'like', "%{$search}%")
				  ->orWhere('usuario.segundoApellido', 'like', "%{$search}%");
			});
		}

		$estudiantes = $estudiantesQuery
			->orderBy('usuario.primerApellido')
			->orderBy('usuario.segundoApellido')
			->orderBy('usuario.nombres')
			->paginate(10);

		// Transformar colección para la vista
		$estudiantes->getCollection()->transform(function ($row) {
			return [
				'id' => $row->id,
				'nombres' => $row->nombres,
				'apellidos' => trim(($row->primerApellido ?? '') . ' ' . ($row->segundoApellido ?? '')),
				'email' => $row->email ?? null,
				'puntaje' => (int) $row->puntaje,
			];
		});

		$periodoActivoId = $periodoActivoModelo?->idPeriodo;

		return Inertia::render('docente/CursosRanking', [
			'curso' => $cursoInfo,
			'periodos' => $periodos,
			'periodoSeleccionado' => $periodoId,
			'estudiantes' => $estudiantes,
			'periodoActivoId' => $periodoActivoId,
		]);
	}

	// ===== NUEVOS MÉTODOS PARA VISTAS AVANZADAS =====

	/**
	 * Vista de Reportes por Materia
	 */
	public function reportesPorMateria(Request $request)
	{
		$docente = Docente::where('idUser', Auth::id())->first();
		
		if (!$docente) {
			abort(404, 'No se encontró el docente');
		}

		// Obtener cursos-paralelos del docente
		$coursesParallels = DB::table('docente_materia_curso as dmc')
			->join('curso_paralelo as cp', 'dmc.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
			->where('dmc.idDocente', $docente->idDocente)
			->select(
				'cp.idCursoParalelo as id',
				'c.idCurso',
				'c.nombre as curso_nombre',
				'p.idParalelo',
				'p.nombre as paralelo_nombre',
				DB::raw("CONCAT(c.nombre, ' - ', p.nombre) as nombre_completo")
			)
			->distinct()
			->get();

		// Obtener TODAS las materias del docente con su curso_paralelo_id
		$subjects = DB::table('docente_materia_curso as dmc')
			->join('materia as m', 'dmc.idMateria', '=', 'm.idMateria')
			->where('dmc.idDocente', $docente->idDocente)
			->select(
				'm.idMateria as id',
				'm.nombre',
				'dmc.idCursoParalelo as curso_paralelo_id'
			)
			->get();

		// Obtener períodos académicos del año actual
		$anioActual = now()->year;
		$periods = \App\Models\PeriodoAcademico::select('idPeriodo as id', 'nombre')
			->whereYear('fecha_inicio', $anioActual)
			->orWhereYear('fecha_fin', $anioActual)
			->orderBy('fecha_inicio', 'desc')
			->get();

		// Obtener las materias y cursos-paralelos que el docente enseña
		$docenteMateriaCurso = DB::table('docente_materia_curso as dmc')
			->where('dmc.idDocente', $docente->idDocente)
			->select('dmc.idMateria', 'dmc.idCursoParalelo')
			->get();

		// Crear combinaciones válidas de materia-curso-paralelo
		$combinacionesValidas = $docenteMateriaCurso->map(function($item) {
			return $item->idMateria . '-' . $item->idCursoParalelo;
		})->toArray();

		// Obtener asignaciones de puntos del docente con filtros
		$assignmentsQuery = DB::table('asignaciones_puntaje as ap')
			->join('puntaje as pt', 'ap.idPuntaje', '=', 'pt.idPuntaje')
			->join('usuario as u', 'pt.idUser', '=', 'u.id')
			->join('estudiante as e', 'u.id', '=', 'e.idUser')
			->join('curso_paralelo as cp', 'e.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
			->join('materia as m', 'ap.idMateria', '=', 'm.idMateria')
			->join('periodos_academicos as pa', 'ap.idPeriodo', '=', 'pa.idPeriodo')
			->where('ap.idDocente', $docente->idDocente)
			->whereRaw("CONCAT(ap.idMateria, '-', cp.idCursoParalelo) IN ('" . implode("','", $combinacionesValidas) . "')"); // Solo combinaciones válidas

		// Aplicar filtros
		if ($request->has('curso_paralelo_id') && $request->curso_paralelo_id) {
			$assignmentsQuery->where('cp.idCursoParalelo', $request->curso_paralelo_id);
		}
		if ($request->has('materia_id') && $request->materia_id) {
			$assignmentsQuery->where('ap.idMateria', $request->materia_id);
		}
		if ($request->has('periodo_id') && $request->periodo_id) {
			$assignmentsQuery->where('ap.idPeriodo', $request->periodo_id);
		}

		$assignments = $assignmentsQuery
			->select([
				'ap.idAsignacion as id',
				'ap.puntos',
				'ap.fecha_asignacion',
				'ap.comentario',
				'u.nombres as estudiante_nombres',
				'u.primerApellido as estudiante_primer_apellido',
				'u.segundoApellido as estudiante_segundo_apellido',
				'c.nombre as curso_nombre',
				'p.nombre as paralelo_nombre',
				'm.nombre as materia_nombre',
				'pa.nombre as periodo_nombre',
				'm.idMateria',
				'pa.idPeriodo',
				'cp.idCursoParalelo'
			])
			->orderBy('c.nombre')
            ->orderBy('p.nombre')
            ->orderBy('m.nombre')
            ->orderBy('u.primerApellido')
            ->orderBy('u.segundoApellido')
            ->orderBy('u.nombres')
			->orderBy('ap.fecha_asignacion', 'desc')
			->get()
			->map(function ($assignment) {
				return [
					'id' => $assignment->id,
					'puntos' => $assignment->puntos,
					'fecha_asignacion' => $assignment->fecha_asignacion,
					'comentario' => $assignment->comentario,
					'estudiante' => [
						'nombres' => $assignment->estudiante_nombres ?? '',
						'apellidos' => trim(($assignment->estudiante_primer_apellido ?? '') . ' ' . ($assignment->estudiante_segundo_apellido ?? '')),
						'curso' => ['nombre' => $assignment->curso_nombre],
						'paralelo' => ['nombre' => $assignment->paralelo_nombre]
					],
					'materia' => [
						'id' => $assignment->idMateria,
						'nombre' => $assignment->materia_nombre
					],
					'periodo' => [
						'id' => $assignment->idPeriodo,
						'nombre' => $assignment->periodo_nombre
					]
				];
			});

		// Estadísticas para el dashboard
		$stats = [
			'total_assignments' => $assignments->count(),
			'total_points' => $assignments->sum('puntos'),
			'average_points' => $assignments->count() > 0 ? round($assignments->avg('puntos'), 1) : 0,
			'unique_students' => $assignments->unique(function($item) {
				return $item['estudiante']['nombres'] . ' ' . $item['estudiante']['apellidos'];
			})->count()
		];

		return Inertia::render('docente/ReportesPorMateria', [
			'teacher' => [
				'id' => $docente->idDocente,
				'nombres' => $docente->user->nombres,
				'apellidos' => trim(($docente->user->primerApellido ?? '') . ' ' . ($docente->user->segundoApellido ?? ''))
			],
			'coursesParallels' => $coursesParallels,
			'subjects' => $subjects,
			'periods' => $periods,
			'assignments' => $assignments,
			'stats' => $stats,
			'filters' => [
				'curso_paralelo_id' => $request->curso_paralelo_id,
				'materia_id' => $request->materia_id,
				'periodo_id' => $request->periodo_id
			]
		]);
	}

	/**
	 * Vista de Asignación de Puntos
	 */
	public function asignacionPuntos(Request $request)
	{
		$docente = Docente::where('idUser', Auth::id())->first();
		
		if (!$docente) {
			abort(404, 'No se encontró el docente');
		}

		// Obtener cursos-paralelos del docente
		$coursesParallels = DB::table('docente_materia_curso as dmc')
			->join('curso_paralelo as cp', 'dmc.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
			->where('dmc.idDocente', $docente->idDocente)
			->select(
				'cp.idCursoParalelo as id',
				'c.idCurso',
				'c.nombre as curso_nombre',
				'p.idParalelo',
				'p.nombre as paralelo_nombre',
				DB::raw("CONCAT(c.nombre, ' - ', p.nombre) as nombre_completo")
			)
			->distinct()
			->get();

		// Obtener TODAS las materias del docente con su curso_paralelo_id
		$subjects = DB::table('docente_materia_curso as dmc')
			->join('materia as m', 'dmc.idMateria', '=', 'm.idMateria')
			->where('dmc.idDocente', $docente->idDocente)
			->select(
				'm.idMateria as id',
				'm.nombre',
				'dmc.idCursoParalelo as curso_paralelo_id'
			)
			->get();

		$anioActual = now()->year;
		$periods = \App\Models\PeriodoAcademico::select('idPeriodo as id', 'nombre')
			->whereYear('fecha_inicio', $anioActual)
			->orWhereYear('fecha_fin', $anioActual)
			->orderBy('fecha_inicio', 'desc')
			->get();


		// Obtener TODOS los estudiantes del docente con su curso_paralelo_id
		$students = DB::table('estudiante as e')
			->join('usuario as u', 'e.idUser', '=', 'u.id')
			->join('curso_paralelo as cp', 'e.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
			->whereIn('cp.idCursoParalelo', function($query) use ($docente) {
				$query->select('idCursoParalelo')
					->from('docente_materia_curso')
					->where('idDocente', $docente->idDocente);
			})
			->select([
				'u.id',
				'u.nombres',
				'u.primerApellido',
				'u.segundoApellido',
				'c.idCurso as curso_id',
				'c.nombre as curso_nombre',
				'p.nombre as paralelo_nombre',
				'cp.idCursoParalelo as curso_paralelo_id'
			])
			->get()
			->map(function ($student) {
				return [
					'id' => $student->id,
					'nombres' => $student->nombres,
					'apellidos' => trim(($student->primerApellido ?? '') . ' ' . ($student->segundoApellido ?? '')),
					'curso_id' => $student->curso_id,
					'curso_paralelo_id' => $student->curso_paralelo_id,
					'curso' => ['nombre' => $student->curso_nombre],
					'paralelo' => ['nombre' => $student->paralelo_nombre]
				];
			});

		// Obtener las materias y cursos actuales del docente
		$docenteMateriaCurso = DB::table('docente_materia_curso as dmc')
			->where('dmc.idDocente', $docente->idDocente)
			->select('dmc.idMateria', 'dmc.idCursoParalelo')
			->get();

		$materiasDocente = $docenteMateriaCurso->pluck('idMateria')->unique()->toArray();
		$cursosParalelosDocente = $docenteMateriaCurso->pluck('idCursoParalelo')->unique()->toArray();

		// Obtener asignaciones recientes
		$recentAssignments = DB::table('asignaciones_puntaje as ap')
			->join('puntaje as pt', 'ap.idPuntaje', '=', 'pt.idPuntaje')
			->join('usuario as u', 'pt.idUser', '=', 'u.id')
			->join('estudiante as e', 'u.id', '=', 'e.idUser')
			->join('curso_paralelo as cp', 'e.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('materia as m', 'ap.idMateria', '=', 'm.idMateria')
			->join('periodos_academicos as pa', 'ap.idPeriodo', '=', 'pa.idPeriodo')
			->where('ap.idDocente', $docente->idDocente)
			->whereIn('ap.idMateria', $materiasDocente)
			->whereIn('cp.idCursoParalelo', $cursosParalelosDocente)
			->select([
				'ap.idAsignacion as id',
				'ap.puntos',
				'ap.fecha_asignacion',
				'u.nombres as estudiante_nombres',
				'u.primerApellido as estudiante_primer_apellido',
				'u.segundoApellido as estudiante_segundo_apellido',
				'm.nombre as materia_nombre',
				'pa.nombre as periodo_nombre',
				'm.idMateria',
				'pa.idPeriodo'
			])
			->orderBy('ap.fecha_asignacion', 'desc')
			->limit(10)
			->get()
			->map(function ($assignment) {
				return [
					'id' => $assignment->id,
					'puntos' => $assignment->puntos,
					'fecha_asignacion' => $assignment->fecha_asignacion,
					'estudiante' => [
						'nombres' => $assignment->estudiante_nombres ?? '',
						'apellidos' => trim(($assignment->estudiante_primer_apellido ?? '') . ' ' . ($assignment->estudiante_segundo_apellido ?? ''))
					],
					'materia' => [
						'id' => $assignment->idMateria,
						'nombre' => $assignment->materia_nombre
					],
					'periodo' => [
						'id' => $assignment->idPeriodo,
						'nombre' => $assignment->periodo_nombre
					]
				];
			});

		return Inertia::render('docente/AsignacionPuntos', [
			'teacher' => [
				'id' => $docente->idDocente,
				'nombres' => $docente->user->nombres,
				'apellidos' => trim(($docente->user->primerApellido ?? '') . ' ' . ($docente->user->segundoApellido ?? ''))
			],
			'coursesParallels' => $coursesParallels,
			'subjects' => $subjects,
			'periods' => $periods,
			'students' => $students,
			'recentAssignments' => $recentAssignments,
			'filters' => [
				'curso_paralelo_id' => $request->curso_paralelo_id
			]
		]);
	}

	/**
	 * Vista de Estadísticas Avanzadas
	 */
	public function estadisticasAvanzadas(Request $request)
	{
		$docente = Docente::where('idUser', Auth::id())->first();
		
		if (!$docente) {
			abort(404, 'No se encontró el docente');
		}

		// Obtener cursos-paralelos del docente
		$coursesParallels = DB::table('docente_materia_curso as dmc')
			->join('curso_paralelo as cp', 'dmc.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
			->where('dmc.idDocente', $docente->idDocente)
			->select(
				'cp.idCursoParalelo as id',
				'c.idCurso',
				'c.nombre as curso_nombre',
				'p.idParalelo',
				'p.nombre as paralelo_nombre',
				DB::raw("CONCAT(c.nombre, ' - ', p.nombre) as nombre_completo")
			)
			->distinct()
			->get();

		// Obtener materias del docente filtradas por curso si se especifica
		$subjectsQuery = DB::table('docente_materia_curso as dmc')
			->join('materia as m', 'dmc.idMateria', '=', 'm.idMateria')
			->where('dmc.idDocente', $docente->idDocente);
		
		// Filtrar por curso si se especifica
		if ($request->has('curso_paralelo_id') && $request->curso_paralelo_id) {
			$subjectsQuery->where('dmc.idCursoParalelo', $request->curso_paralelo_id);
		}
		
		$subjects = $subjectsQuery
			->select('m.idMateria as id', 'm.nombre')
			->distinct()
			->get();

		// Obtener TODOS los cursos del docente (nombres únicos)
		$courses = DB::table('docente_materia_curso as dmc')
			->join('curso_paralelo as cp', 'dmc.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->where('dmc.idDocente', $docente->idDocente)
			->select('c.idCurso as id', 'c.nombre')
			->distinct()
			->get();

		// Obtener períodos académicos del año actual
		$anioActual = now()->year;
		$periods = \App\Models\PeriodoAcademico::select('idPeriodo as id', 'nombre')
			->whereYear('fecha_inicio', $anioActual)
			->orWhereYear('fecha_fin', $anioActual)
			->orderBy('fecha_inicio', 'desc')
			->get();

		// Obtener TODAS las materias y cursos-paralelos que el docente enseña
		$docenteMateriaCurso = DB::table('docente_materia_curso as dmc')
			->where('dmc.idDocente', $docente->idDocente)
			->select('dmc.idMateria', 'dmc.idCursoParalelo')
			->get();

		// Crear combinaciones válidas de materia-curso-paralelo
		$combinacionesValidas = $docenteMateriaCurso->map(function($item) {
			return $item->idMateria . '-' . $item->idCursoParalelo;
		})->toArray();

		// Obtener todas las asignaciones del docente para análisis con filtros
		$assignmentsQuery = DB::table('asignaciones_puntaje as ap')
			->join('puntaje as pt', 'ap.idPuntaje', '=', 'pt.idPuntaje')
			->join('usuario as u', 'pt.idUser', '=', 'u.id')
			->join('estudiante as e', 'u.id', '=', 'e.idUser')
			->join('curso_paralelo as cp', 'e.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
			->join('materia as m', 'ap.idMateria', '=', 'm.idMateria')
			->join('periodos_academicos as pa', 'ap.idPeriodo', '=', 'pa.idPeriodo')
			->where('ap.idDocente', $docente->idDocente);
		
		// Solo aplicar filtro de combinaciones válidas si hay combinaciones
		if (!empty($combinacionesValidas)) {
			$assignmentsQuery->whereRaw("CONCAT(ap.idMateria, '-', cp.idCursoParalelo) IN ('" . implode("','", $combinacionesValidas) . "')");
		}

		// Aplicar filtros
		if ($request->has('curso_paralelo_id') && $request->curso_paralelo_id) {
			$assignmentsQuery->where('cp.idCursoParalelo', $request->curso_paralelo_id);
		}
		if ($request->has('materia_id') && $request->materia_id) {
			$assignmentsQuery->where('ap.idMateria', $request->materia_id);
		}
		if ($request->has('periodo_id') && $request->periodo_id) {
			$assignmentsQuery->where('ap.idPeriodo', $request->periodo_id);
		}

		$assignments = $assignmentsQuery
			->select([
				'ap.idAsignacion as id',
				'ap.puntos',
				'ap.fecha_asignacion',
				'u.id as estudiante_id',
				'u.nombres as estudiante_nombres',
				'u.primerApellido as estudiante_primer_apellido',
				'u.segundoApellido as estudiante_segundo_apellido',
				'c.nombre as curso_nombre',
				'p.nombre as paralelo_nombre',
				'm.nombre as materia_nombre',
				'pa.nombre as periodo_nombre',
				'm.idMateria',
				'pa.idPeriodo'
			])
			->get()
			->map(function ($assignment) {
				return [
					'id' => $assignment->id,
					'puntos' => $assignment->puntos,
					'fecha_asignacion' => $assignment->fecha_asignacion,
					'estudiante' => [
						'id' => $assignment->estudiante_id,
						'nombres' => $assignment->estudiante_nombres ?? '',
						'apellidos' => trim(($assignment->estudiante_primer_apellido ?? '') . ' ' . ($assignment->estudiante_segundo_apellido ?? '')),
						'curso' => ['nombre' => $assignment->curso_nombre],
						'paralelo' => ['nombre' => $assignment->paralelo_nombre]
					],
					'materia' => [
						'id' => $assignment->idMateria,
						'nombre' => $assignment->materia_nombre
					],
					'periodo' => [
						'id' => $assignment->idPeriodo,
						'nombre' => $assignment->periodo_nombre
					]
				];
			});

		// Calcular métricas de calidad basadas en máximo 100 puntos
		$qualityMetrics = [
			'excelencia' => $assignments->filter(function($item) { return $item['puntos'] >= 90; })->count(),
			'bueno' => $assignments->filter(function($item) { return $item['puntos'] >= 70 && $item['puntos'] < 90; })->count(),
			'regular' => $assignments->filter(function($item) { return $item['puntos'] >= 50 && $item['puntos'] < 70; })->count(),
			'bajo' => $assignments->filter(function($item) { return $item['puntos'] < 50; })->count(),
			'total' => $assignments->count(),
			'promedio_general' => $assignments->count() > 0 ? round($assignments->avg('puntos'), 1) : 0
		];

		// Estadísticas por materia
		$statsBySubject = $assignments->groupBy(function($item) {
			return $item['materia']['nombre'];
		})->map(function($group, $materia) {
			return [
				'materia' => $materia,
				'total_asignaciones' => $group->count(),
				'promedio' => round($group->avg('puntos'), 1),
				'total_puntos' => $group->sum('puntos'),
				'estudiantes_unicos' => $group->unique(function($item) {
					return $item['estudiante']['id'];
				})->count()
			];
		})->values();

		return Inertia::render('docente/EstadisticasAvanzadas', [
			'teacher' => [
				'id' => $docente->idDocente,
				'nombres' => $docente->user->nombres,
				'apellidos' => trim(($docente->user->primerApellido ?? '') . ' ' . ($docente->user->segundoApellido ?? ''))
			],
			'coursesParallels' => $coursesParallels,
			'courses' => $courses,
			'subjects' => $subjects,
			'periods' => $periods,
			'assignments' => $assignments,
			'qualityMetrics' => $qualityMetrics,
			'statsBySubject' => $statsBySubject,
			'filters' => [
				'curso_paralelo_id' => $request->curso_paralelo_id,
				'materia_id' => $request->materia_id,
				'periodo_id' => $request->periodo_id
			]
		]);
	}

	/**
	 * Vista de Gestión de Estudiantes
	 */
	public function gestionEstudiantes(Request $request)
	{
		$docente = Docente::where('idUser', Auth::id())->first();
		
		if (!$docente) {
			abort(404, 'No se encontró el docente');
		}

		// Obtener cursos-paralelos del docente usando consulta directa
		$coursesParallels = DB::table('docente_materia_curso as dmc')
			->join('curso_paralelo as cp', 'dmc.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
			->where('dmc.idDocente', $docente->idDocente)
			->select(
				'cp.idCursoParalelo as id',
				'c.nombre as curso_nombre',
				'p.nombre as paralelo_nombre',
				DB::raw("CONCAT(c.nombre, ' - ', p.nombre) as nombre_completo")
			)
			->distinct()
			->get();

		// Obtener materias del docente usando consulta directa
		$subjectsQuery = DB::table('docente_materia_curso as dmc')
			->join('materia as m', 'dmc.idMateria', '=', 'm.idMateria')
			->where('dmc.idDocente', $docente->idDocente);
		
		// Filtrar por curso si se especifica
		if ($request->has('curso_paralelo_id') && $request->curso_paralelo_id) {
			$subjectsQuery->where('dmc.idCursoParalelo', $request->curso_paralelo_id);
		}
		
		$subjects = $subjectsQuery
			->select(
				'm.idMateria as id',
				'm.nombre',
				'dmc.idCursoParalelo as curso_paralelo_id'
			)
			->distinct()
			->get();

		// Obtener períodos académicos del año actual
		$anioActual = now()->year;
		$periods = \App\Models\PeriodoAcademico::select('idPeriodo as id', 'nombre')
			->whereYear('fecha_inicio', $anioActual)
			->orWhereYear('fecha_fin', $anioActual)
			->orderBy('fecha_inicio', 'desc')
			->get();

		// Obtener IDs de cursos-paralelos del docente usando consulta directa
		$cursoParaleloIds = DB::table('docente_materia_curso')
			->where('idDocente', $docente->idDocente)
			->pluck('idCursoParalelo')
			->toArray();

		// Obtener estudiantes usando relaciones
		$studentsQuery = Estudiante::with(['user', 'cursoParalelo.curso', 'cursoParalelo.paralelo'])
			->whereIn('idCursoParalelo', $cursoParaleloIds);

		// Aplicar filtros
		if ($request->has('curso_paralelo_id') && $request->curso_paralelo_id) {
			$studentsQuery->where('idCursoParalelo', $request->curso_paralelo_id);
		}

		$students = $studentsQuery->get()->map(function ($estudiante) use ($docente, $request) {
			// Puntos asignados por el docente
			$puntosAsignados = $estudiante->user->puntajes()
				->whereHas('asignacionesPuntaje', function($query) use ($docente) {
					$query->where('idDocente', $docente->idDocente);
				})
				->when($request->has('materia_id') && $request->materia_id, function($query) use ($request) {
					$query->whereHas('asignacionesPuntaje', function($q) use ($request) {
						$q->where('idMateria', $request->materia_id);
					});
				})
				->when($request->has('periodo_id') && $request->periodo_id, function($query) use ($request) {
					$query->where('idPeriodo', $request->periodo_id);
				})
				->sum('puntos');

			$totalAsignaciones = $estudiante->user->puntajes()
				->whereHas('asignacionesPuntaje', function($query) use ($docente) {
					$query->where('idDocente', $docente->idDocente);
				})
				->when($request->has('materia_id') && $request->materia_id, function($query) use ($request) {
					$query->whereHas('asignacionesPuntaje', function($q) use ($request) {
						$q->where('idMateria', $request->materia_id);
					});
				})
				->when($request->has('periodo_id') && $request->periodo_id, function($query) use ($request) {
					$query->where('idPeriodo', $request->periodo_id);
				})
				->count();

			// Puntos de depósitos
			$puntosDepositos = $estudiante->user->puntajes()
				->when($request->has('periodo_id') && $request->periodo_id, function($query) use ($request) {
					$query->where('idPeriodo', $request->periodo_id);
				})
				->sum('puntos');

			$totalDepositos = $estudiante->user->depositos()
				->count();

			// Última actividad
			$ultimaAsignacion = $estudiante->user->puntajes()
				->whereHas('asignacionesPuntaje', function($query) use ($docente) {
					$query->where('idDocente', $docente->idDocente);
				})
				->max('fechaAsignacion');

			$ultimoDeposito = $estudiante->user->depositos()
				->max('fechaHora');

			$totalPuntos = $puntosAsignados + $puntosDepositos;
			$promedio = $totalAsignaciones > 0 ? $puntosAsignados / $totalAsignaciones : 0;
			$rendimientoPercent = $totalAsignaciones > 0 ? round(($promedio / 100) * 100, 1) : 0;

			return [
				'id' => $estudiante->user->id,
				'nombres' => $estudiante->user->nombres,
				'apellidos' => trim(($estudiante->user->primerApellido ?? '') . ' ' . ($estudiante->user->segundoApellido ?? '')),
				'curso_paralelo_id' => $estudiante->idCursoParalelo,
				'total_puntos_asignados' => (int) $puntosAsignados,
				'total_puntos_depositos' => (int) $puntosDepositos,
				'total_puntos' => (int) $totalPuntos,
				'total_asignaciones' => (int) $totalAsignaciones,
				'total_depositos' => (int) $totalDepositos,
				'promedio' => round($promedio, 1),
				'rendimiento_percent' => $rendimientoPercent,
				'ultima_asignacion' => $ultimaAsignacion ? $ultimaAsignacion : 'Sin asignaciones',
				'ultimo_deposito' => $ultimoDeposito ? $ultimoDeposito : 'Sin depósitos',
				'curso' => ['nombre' => $estudiante->cursoParalelo->curso->nombre],
				'paralelo' => ['nombre' => $estudiante->cursoParalelo->paralelo->nombre]
			];
		});

		return Inertia::render('docente/GestionEstudiantes', [
			'teacher' => [
				'id' => $docente->idDocente,
				'nombres' => $docente->user->nombres,
				'apellidos' => trim(($docente->user->primerApellido ?? '') . ' ' . ($docente->user->segundoApellido ?? ''))
			],
			'coursesParallels' => $coursesParallels,
			'subjects' => $subjects,
			'periods' => $periods,
			'students' => $students,
			'filters' => [
				'curso_paralelo_id' => $request->curso_paralelo_id,
				'materia_id' => $request->materia_id,
				'periodo_id' => $request->periodo_id
			]
		]);
	}

	// ❌ MÉTODOS ELIMINADOS: storeAsignacion y storeBulkAsignacion
	// ✅ USAR: asignarPuntosExtracurriculares() para asignación manual individual/masiva

	/**
	 * Descargar reporte PDF
	 */
	public function downloadReportePDF(Request $request)
	{
		$reportesData = $this->getReportesData($request, false); // false = sin límite, obtener todos
		
		// Preparar filtros aplicados
		$filters = [];
		if ($request->has('curso_paralelo_id') && $request->curso_paralelo_id) {
			$curso = $reportesData['coursesParallels']->firstWhere('id', $request->curso_paralelo_id);
			if ($curso) {
				$filters['curso'] = $curso->nombre_completo;
			}
		}
		if ($request->has('materia_id') && $request->materia_id) {
			$materia = $reportesData['subjects']->firstWhere('id', $request->materia_id);
			if ($materia) {
				$filters['materia'] = $materia->nombre;
			}
		}
		if ($request->has('periodo_id') && $request->periodo_id) {
			$periodo = $reportesData['periods']->firstWhere('id', $request->periodo_id);
			if ($periodo) {
				$filters['periodo'] = $periodo->nombre;
			}
		}
		
		$reportesData['filters'] = $filters;
		
		$html = view('pdf.reporte-docente', $reportesData)->render();
		
		// Configurar headers para descarga
		$filename = 'reporte-docente-' . now()->format('Y-m-d-His') . '.html';
		
		return response($html)
			->header('Content-Type', 'text/html')
			->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
	}

	/**
	 * Exportar a Excel
	 */
	public function exportarReporteExcel(Request $request)
	{
		$reportesData = $this->getReportesData($request, false); // false = sin límite, obtener todos
		
		// Preparar filtros aplicados
		$filters = [];
		if ($request->has('curso_paralelo_id') && $request->curso_paralelo_id) {
			$curso = $reportesData['coursesParallels']->firstWhere('id', $request->curso_paralelo_id);
			if ($curso) {
				$filters['curso'] = $curso->nombre_completo;
			}
		}
		if ($request->has('materia_id') && $request->materia_id) {
			$materia = $reportesData['subjects']->firstWhere('id', $request->materia_id);
			if ($materia) {
				$filters['materia'] = $materia->nombre;
			}
		}
		if ($request->has('periodo_id') && $request->periodo_id) {
			$periodo = $reportesData['periods']->firstWhere('id', $request->periodo_id);
			if ($periodo) {
				$filters['periodo'] = $periodo->nombre;
			}
		}
		
		$filename = 'reporte-docente-' . now()->format('Y-m-d-His') . '.xlsx';
		
		return Excel::download(
			new ReporteDocenteExport($reportesData, $reportesData['teacher'], $filters),
			$filename
		);
	}

	// ===== MÉTODOS AUXILIARES PARA DATOS SIN RENDERIZAR =====

	/**
	 * Obtener datos de reportes (solo datos, sin renderizar)
	 * @param Request $request
	 * @param bool $limitResults Si es true, limita los resultados para dashboard. Si es false, obtiene todos para exportación.
	 */
	private function getReportesData(Request $request, $limitResults = true)
	{
		$docente = Docente::where('idUser', Auth::id())->first();
		
		if (!$docente) {
			return [];
		}

		// Obtener cursos-paralelos del docente
		$coursesParallels = DB::table('docente_materia_curso as dmc')
			->join('curso_paralelo as cp', 'dmc.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
			->where('dmc.idDocente', $docente->idDocente)
			->select(
				'cp.idCursoParalelo as id',
				'c.idCurso',
				'c.nombre as curso_nombre',
				'p.idParalelo',
				'p.nombre as paralelo_nombre',
				DB::raw("CONCAT(c.nombre, ' - ', p.nombre) as nombre_completo")
			)
			->distinct()
			->get();

		// Obtener materias por curso-paralelo seleccionado
		$subjects = collect();
		if ($request->has('curso_paralelo_id')) {
			$subjects = DB::table('docente_materia_curso as dmc')
				->join('materia as m', 'dmc.idMateria', '=', 'm.idMateria')
				->where('dmc.idDocente', $docente->idDocente)
				->where('dmc.idCursoParalelo', $request->curso_paralelo_id)
				->select('m.idMateria as id', 'm.nombre')
				->distinct()
				->get();
		}

		// Obtener períodos académicos
		$periods = \App\Models\PeriodoAcademico::select('idPeriodo as id', 'nombre')->get();

		// Obtener asignaciones con filtros (limitadas para dashboard)
		$assignmentsQuery = DB::table('asignaciones_puntaje as ap')
			->join('puntaje as pt', 'ap.idPuntaje', '=', 'pt.idPuntaje')
			->join('usuario as u', 'pt.idUser', '=', 'u.id')
			->join('estudiante as e', 'u.id', '=', 'e.idUser')
			->join('curso_paralelo as cp', 'e.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
			->join('materia as m', 'ap.idMateria', '=', 'm.idMateria')
			->join('periodos_academicos as pa', 'ap.idPeriodo', '=', 'pa.idPeriodo')
			->where('ap.idDocente', $docente->idDocente);

		// Aplicar filtros
		if ($request->has('curso_paralelo_id') && $request->curso_paralelo_id) {
			$assignmentsQuery->where('cp.idCursoParalelo', $request->curso_paralelo_id);
		}
		if ($request->has('materia_id') && $request->materia_id) {
			$assignmentsQuery->where('ap.idMateria', $request->materia_id);
		}
		if ($request->has('periodo_id') && $request->periodo_id) {
			$assignmentsQuery->where('ap.idPeriodo', $request->periodo_id);
		}

		$assignments = $assignmentsQuery->orderBy('c.nombre')
            ->orderBy('p.nombre')
            ->orderBy('m.nombre')
            ->orderBy('u.primerApellido')
            ->orderBy('u.segundoApellido')
            ->orderBy('u.nombres')
			->orderBy('ap.fecha_asignacion', 'desc')
			->select([
				'ap.idAsignacion as id',
				'ap.puntos',
				'ap.fecha_asignacion',
				'ap.comentario',
				'u.nombres as estudiante_nombres',
				'u.primerApellido as estudiante_primer_apellido',
				'u.segundoApellido as estudiante_segundo_apellido',
				'c.nombre as curso_nombre',
				'p.nombre as paralelo_nombre',
				'm.nombre as materia_nombre',
				'pa.nombre as periodo_nombre',
				'm.idMateria',
				'pa.idPeriodo',
				'cp.idCursoParalelo'
			])
			->get()
			->map(function ($assignment) {
				return [
					'id' => $assignment->id,
					'puntos' => $assignment->puntos,
					'fecha_asignacion' => $assignment->fecha_asignacion,
					'comentario' => $assignment->comentario,
					'estudiante' => [
						'id' => $assignment->id ?? 0,
						'nombres' => $assignment->estudiante_nombres ?? '',
						'apellidos' => trim(($assignment->estudiante_primer_apellido ?? '') . ' ' . ($assignment->estudiante_segundo_apellido ?? '')),
						'curso' => ['nombre' => $assignment->curso_nombre ?? ''],
						'paralelo' => ['nombre' => $assignment->paralelo_nombre ?? '']
					],
					'materia' => [
						'id' => $assignment->idMateria,
						'nombre' => $assignment->materia_nombre ?? ''
					],
					'periodo' => [
						'id' => $assignment->idPeriodo,
						'nombre' => $assignment->periodo_nombre ?? ''
					]
				];
			});

		// Estadísticas básicas
		$statsQuery = DB::table('asignaciones_puntaje as ap')
			->join('puntaje as pt', 'ap.idPuntaje', '=', 'pt.idPuntaje')
			->join('usuario as u', 'pt.idUser', '=', 'u.id')
			->join('estudiante as e', 'u.id', '=', 'e.idUser')
			->join('curso_paralelo as cp', 'e.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
			->join('materia as m', 'ap.idMateria', '=', 'm.idMateria')
			->join('periodos_academicos as pa', 'ap.idPeriodo', '=', 'pa.idPeriodo')
			->where('ap.idDocente', $docente->idDocente);

		// Aplicar los mismos filtros a las estadísticas
		if ($request->has('curso_paralelo_id') && $request->curso_paralelo_id) {
			$statsQuery->where('cp.idCursoParalelo', $request->curso_paralelo_id);
		}
		if ($request->has('materia_id') && $request->materia_id) {
			$statsQuery->where('ap.idMateria', $request->materia_id);
		}
		if ($request->has('periodo_id') && $request->periodo_id) {
			$statsQuery->where('ap.idPeriodo', $request->periodo_id);
		}
		
		$statsData = $statsQuery->get();
		
		return [
			'teacher' => [
				'id' => $docente->idDocente,
				'nombres' => $docente->user->nombres,
				'apellidos' => trim(($docente->user->primerApellido ?? '') . ' ' . ($docente->user->segundoApellido ?? ''))
			],
			'coursesParallels' => $coursesParallels,
			'subjects' => $subjects,
			'periods' => $periods,
			'assignments' => $assignments,
			'stats' => [
				'total_assignments' => $statsData->count(),
				'total_points' => $statsQuery->sum('ap.puntos'),
				'average_points' => $statsData->count() > 0 ? round($statsQuery->avg('ap.puntos'), 1) : 0,
				'unique_students' => $assignments->pluck('estudiante.id')->unique()->count(),
			]
		];
	}

	/**
	 * Obtener datos de asignación (solo datos, sin renderizar)
	 */
	private function getAsignacionData(Request $request)
	{
		$docente = Docente::where('idUser', Auth::id())->first();
		
		if (!$docente) {
			return [];
		}

		// Obtener cursos-paralelos del docente
		$coursesParallels = DB::table('docente_materia_curso as dmc')
			->join('curso_paralelo as cp', 'dmc.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
			->where('dmc.idDocente', $docente->idDocente)
			->select(
				'cp.idCursoParalelo as id',
				DB::raw("CONCAT(c.nombre, ' - ', p.nombre) as nombre_completo")
			)
			->distinct()
			->get();

		// Obtener períodos académicos
		$periods = \App\Models\PeriodoAcademico::select('idPeriodo as id', 'nombre')->get();

		return [
			'teacher' => [
				'id' => $docente->idDocente,
				'nombres' => $docente->user->nombres,
				'apellidos' => trim(($docente->user->primerApellido ?? '') . ' ' . ($docente->user->segundoApellido ?? ''))
			],
			'coursesParallels' => $coursesParallels,
			'periods' => $periods
		];
	}

	/**
	 * Obtener datos de estadísticas (solo datos, sin renderizar)
	 */
	private function getEstadisticasData(Request $request)
	{
		$docente = Docente::where('idUser', Auth::id())->first();
		
		if (!$docente) {
			return [];
		}

		// Obtener métricas básicas
		$totalAssignments = DB::table('asignaciones_puntaje')
			->where('idDocente', $docente->idDocente)
			->count();

		$totalPoints = DB::table('asignaciones_puntaje')
			->where('idDocente', $docente->idDocente)
			->sum('puntos');

		$averagePoints = $totalAssignments > 0 ? 
			round(DB::table('asignaciones_puntaje')->where('idDocente', $docente->idDocente)->avg('puntos'), 1) : 0;

		return [
			'teacher' => [
				'id' => $docente->idDocente,
				'nombres' => $docente->user->nombres,
				'apellidos' => trim(($docente->user->primerApellido ?? '') . ' ' . ($docente->user->segundoApellido ?? ''))
			],
			'totalAssignments' => $totalAssignments,
			'totalPoints' => $totalPoints,
			'averagePoints' => $averagePoints
		];
	}

	/**
	 * Obtener datos de gestión (solo datos, sin renderizar)
	 */
	private function getGestionData(Request $request)
	{
		$docente = Docente::where('idUser', Auth::id())->first();
		
		if (!$docente) {
			return [];
		}

		// Obtener cursos-paralelos del docente
		$coursesParallels = DB::table('docente_materia_curso as dmc')
			->join('curso_paralelo as cp', 'dmc.idCursoParalelo', '=', 'cp.idCursoParalelo')
			->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
			->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
			->where('dmc.idDocente', $docente->idDocente)
			->select(
				'cp.idCursoParalelo as id',
				DB::raw("CONCAT(c.nombre, ' - ', p.nombre) as nombre_completo")
			)
			->distinct()
			->get();

		return [
			'teacher' => [
				'id' => $docente->idDocente,
				'nombres' => $docente->user->nombres,
				'apellidos' => trim(($docente->user->primerApellido ?? '') . ' ' . ($docente->user->segundoApellido ?? ''))
			],
			'coursesParallels' => $coursesParallels
		];
	}
}
	

