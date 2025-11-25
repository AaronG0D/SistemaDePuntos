<?php

namespace App\Http\Controllers;

use App\Models\Basurero;
use App\Models\Deposito;
use App\Models\TipoBasura;
use App\Models\User;
use App\Models\PeriodoAcademico;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    public function index()
    {
        $estadisticas = [
            'total_depositos' => Deposito::count(),
            'total_puntos' => Deposito::join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
                ->sum('tipoBasura.puntos'),
            'total_tipos_residuos' => TipoBasura::where('estado', true)->count(),
            'total_basureros' => Basurero::where('estado', true)->count(),
        ];

        $tiposResiduos = TipoBasura::select('idTipoBasura as id', 'nombre')->get();
        $basureros = Basurero::select('idBasurero as id', 'ubicacion')->get();
        
        // Obtener períodos académicos disponibles
        $periodosAcademicos = PeriodoAcademico::select('idPeriodo', 'nombre', 'codigo', 'fecha_inicio', 'fecha_fin')
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        // Datos para gráficos
        $depositosPorTipo = Deposito::join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
            ->select('tipoBasura.nombre', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(tipoBasura.puntos) as puntos_totales'))
            ->groupBy('tipoBasura.idTipoBasura', 'tipoBasura.nombre')
            ->orderByDesc('cantidad')
            ->get();

        $depositosPorMes = Deposito::select(
            DB::raw('DATE_FORMAT(fechaHora, "%Y-%m") as mes'),
            DB::raw('COUNT(*) as cantidad')
        )
        ->where('fechaHora', '>=', now()->subMonths(6))
        ->groupBy('mes')
        ->orderBy('mes')
        ->get();

        $topUsuarios = DB::table('usuario')
            ->select('usuario.nombres', 'usuario.primerApellido', DB::raw('SUM(tb.puntos) as total_puntos'))
            ->join('deposito', 'usuario.id', '=', 'deposito.idUser')
            ->join('tipoBasura as tb', 'deposito.idTipoBasura', '=', 'tb.idTipoBasura')
            ->groupBy('usuario.id', 'usuario.nombres', 'usuario.primerApellido')
            ->orderByDesc('total_puntos')
            ->limit(10)
            ->get();

        // Obtener lista de estudiantes para el selector con información de curso/paralelo
        $estudiantes = DB::table('usuario')
            ->leftJoin('estudiante', 'usuario.id', '=', 'estudiante.idUser')
            ->leftJoin('curso_paralelo', 'estudiante.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
            ->leftJoin('curso', 'curso_paralelo.idCurso', '=', 'curso.idCurso')
            ->leftJoin('paralelo', 'curso_paralelo.idParalelo', '=', 'paralelo.idParalelo')
            ->where('usuario.rol', 'estudiante')
            ->where('usuario.deleted_at', null)
            ->select(
                'usuario.id',
                'usuario.nombres',
                'usuario.primerApellido',
                'usuario.segundoApellido',
                'curso.idCurso as curso_id',
                'curso.nombre as curso_nombre',
                'paralelo.idParalelo as paralelo_id',
                'paralelo.nombre as paralelo_nombre'
            )
            ->orderBy('usuario.primerApellido')
            ->orderBy('usuario.nombres')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'nombre_completo' => $user->nombres . ' ' . $user->primerApellido . ' ' . ($user->segundoApellido ?? ''),
                    'curso_id' => $user->curso_id,
                    'curso_nombre' => $user->curso_nombre ?? 'Sin curso',
                    'paralelo_id' => $user->paralelo_id,
                    'paralelo_nombre' => $user->paralelo_nombre ?? 'Sin paralelo'
                ];
            });

        $datosGraficos = [
            'porTipo' => [
                'labels' => $depositosPorTipo->pluck('nombre')->toArray(),
                'datasets' => [
                    [
                        'label' => 'Cantidad de Depósitos',
                        'data' => $depositosPorTipo->pluck('cantidad')->toArray(),
                        'backgroundColor' => [
                            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
                            '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6366F1'
                        ],
                        'borderColor' => '#1F2937',
                        'borderWidth' => 1
                    ]
                ]
            ],
            'porMes' => [
                'labels' => $depositosPorMes->pluck('mes')->toArray(),
                'datasets' => [
                    [
                        'label' => 'Depósitos por Mes',
                        'data' => $depositosPorMes->pluck('cantidad')->toArray(),
                        'borderColor' => '#3B82F6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'borderWidth' => 2,
                        'fill' => true
                    ]
                ]
            ],
            'topUsuarios' => [
                'labels' => $topUsuarios->map(fn($u) => $u->nombres . ' ' . $u->primerApellido)->toArray(),
                'datasets' => [
                    [
                        'label' => 'Puntos Totales',
                        'data' => $topUsuarios->pluck('total_puntos')->toArray(),
                        'backgroundColor' => [
                            '#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6',
                            '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6366F1'
                        ],
                        'borderColor' => '#1F2937',
                        'borderWidth' => 1
                    ]
                ]
            ]
        ];

        // Obtener cursos y paralelos para filtros de estudiante
        $cursos = DB::table('curso')
            ->where('estado', '1')
            ->select('idCurso as id', 'nombre')
            ->orderBy('nombre')
            ->get();

        $paralelos = DB::table('paralelo')
            ->where('estado', '1')
            ->select('idParalelo as id', 'nombre')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('admin/reportes/Index', [
            'estadisticas' => $estadisticas,
            'tiposResiduos' => $tiposResiduos,
            'basureros' => $basureros,
            'periodosAcademicos' => $periodosAcademicos,
            'estudiantes' => $estudiantes,
            'cursos' => $cursos,
            'paralelos' => $paralelos,
            'datosGraficos' => $datosGraficos
        ]);
    }

    public function depositos(Request $request)
    {
        // Validar solo si tipo_residuo_id no es 'todos'
        $validationRules = [
            'periodo_id' => 'nullable|exists:periodos_academicos,idPeriodo',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
        ];
        
        if ($request->filled('tipo_residuo_id') && $request->tipo_residuo_id !== 'todos') {
            if (is_array($request->tipo_residuo_id)) {
                $validationRules['tipo_residuo_id'] = 'array';
                $validationRules['tipo_residuo_id.*'] = 'exists:tipoBasura,idTipoBasura';
            } else {
                $validationRules['tipo_residuo_id'] = 'exists:tipoBasura,idTipoBasura';
            }
        }

        $request->validate($validationRules, [
            'tipo_residuo_id.exists' => 'El tipo de residuo seleccionado no es válido.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ]);

        // Validar rango de fechas (máximo 3 meses) solo si no se usa periodo y se enviaron fechas
        if (!$request->filled('periodo_id') && $request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $this->validateDateRange($request->fecha_inicio, $request->fecha_fin);
        }
        
        $query = $this->construirConsultaDepositos($request);
        
        // Calcular estadísticas generales (sobre todos los datos, no solo la página actual)
        $queryStats = clone $query;
        
        // Obtener datos paginados
        $depositos = $query->paginate(15);
        
        $allDepositos = $queryStats->get();
        
        // Calcular total_puntos sumando los puntos de los depósitos filtrados
        $total_puntos = $allDepositos->sum(function($d) {
            return $d->puntos ?? $d->tipoBasura->puntos ?? 0;
        });
        
        $estadisticas = [
            'total_depositos' => $allDepositos->count(),
            'total_puntos' => $total_puntos,
            'usuarios_unicos' => $allDepositos->pluck('idUser')->unique()->count(),
            'por_tipo' => $allDepositos->groupBy('tipoBasura.nombre')->map(function($items, $nombre) {
                return [
                    'nombre' => $nombre,
                    'cantidad' => $items->count(),
                    'puntos' => $items->sum(function($d) {
                        return $d->puntos ?? $d->tipoBasura->puntos ?? 0;
                    })
                ];
            })->values()
        ];

        return response()->json([
            'depositos' => $depositos->items(),
            'pagination' => [
                'current_page' => $depositos->currentPage(),
                'last_page' => $depositos->lastPage(),
                'per_page' => $depositos->perPage(),
                'total' => $depositos->total(),
                'from' => $depositos->firstItem(),
                'to' => $depositos->lastItem(),
            ],
            'estadisticas' => $estadisticas,
            'usando_rango_defecto' => !$request->filled('periodo_id') && !$request->filled('fecha_inicio') && !$request->filled('fecha_fin')
        ]);
    }

    public function ranking(Request $request)
    {
        $tipo = $request->get('tipo', 'estudiantes'); // 'estudiantes' o 'cursos'
        
        if ($tipo === 'cursos') {
            return $this->rankingCursos($request);
        }
        
        // Ranking de estudiantes - USAR TABLA PUNTAJE
        $query = User::where('rol', 'estudiante')
            ->join('puntaje', 'usuario.id', '=', 'puntaje.idUser')
            ->leftJoin('estudiante', 'usuario.id', '=', 'estudiante.idUser')
            ->leftJoin('curso_paralelo', 'estudiante.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
            ->leftJoin('curso', 'curso_paralelo.idCurso', '=', 'curso.idCurso')
            ->leftJoin('paralelo', 'curso_paralelo.idParalelo', '=', 'paralelo.idParalelo')
            ->select(
                'usuario.id',
                'usuario.nombres',
                'usuario.primerApellido',
                'usuario.segundoApellido',
                DB::raw('SUM(puntaje.puntos) as total_puntos')
            );

        // Aplicar filtros de periodo para PUNTAJE
        $this->aplicarFiltrosPeriodoPuntaje($query, $request);

        // Contar depósitos por separado
        $ranking = $query->groupBy('usuario.id', 'usuario.nombres', 'usuario.primerApellido', 'usuario.segundoApellido')
            ->orderByDesc('total_puntos')
            ->limit(10)
            ->get();

        // Agregar total_depositos para cada usuario
        foreach ($ranking as $user) {
            $depositosQuery = DB::table('deposito')->where('idUser', $user->id);
            $this->aplicarFiltrosPeriodoDepositos($depositosQuery, $request);
            $user->total_depositos = $depositosQuery->count();
        }

        return response()->json($ranking);
    }

    // Método privado para ranking de cursos - USAR TABLA PUNTAJE
    private function rankingCursos(Request $request)
    {
        // Primero obtener puntos por curso desde la tabla puntaje
        $query = DB::table('curso')
            ->join('curso_paralelo', 'curso.idCurso', '=', 'curso_paralelo.idCurso')
            ->join('estudiante', 'curso_paralelo.idCursoParalelo', '=', 'estudiante.idCursoParalelo')
            ->join('usuario', 'estudiante.idUser', '=', 'usuario.id')
            ->join('puntaje', 'usuario.id', '=', 'puntaje.idUser')
            ->select(
                'curso.idCurso',
                'curso.nombre as curso_nombre',
                'curso_paralelo.idParalelo',
                'curso_paralelo.idCursoParalelo',
                DB::raw('(SELECT nombre FROM paralelo WHERE idParalelo = curso_paralelo.idParalelo) as paralelo_nombre'),
                DB::raw('SUM(puntaje.puntos) as total_puntos'),
                DB::raw('COUNT(DISTINCT usuario.id) as cantidad_estudiantes')
            );

        // Aplicar filtros de periodo para PUNTAJE
        $this->aplicarFiltrosPeriodoPuntaje($query, $request);

        $ranking = $query->groupBy('curso.idCurso', 'curso.nombre', 'curso_paralelo.idParalelo', 'curso_paralelo.idCursoParalelo')
            ->orderByDesc('total_puntos')
            ->get();
            
        // Agregar total_depositos para cada curso
        foreach ($ranking as $curso) {
            $depositosQuery = DB::table('deposito')
                ->join('usuario', 'deposito.idUser', '=', 'usuario.id')
                ->join('estudiante', 'usuario.id', '=', 'estudiante.idUser')
                ->join('curso_paralelo', 'estudiante.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
                ->where('curso_paralelo.idCurso', $curso->idCurso)
                ->where('curso_paralelo.idParalelo', $curso->idParalelo);
            
            $this->aplicarFiltrosPeriodoDepositos($depositosQuery, $request);
            $curso->total_depositos = $depositosQuery->count();
        }
            
        // Formatear nombre completo del curso
        $ranking = $ranking->map(function($item) {
            $item->nombre_completo = $item->curso_nombre . ' ' . $item->paralelo_nombre;
            $item->promedio_por_estudiante = $item->cantidad_estudiantes > 0 ? round($item->total_puntos / $item->cantidad_estudiantes, 2) : 0;
            return $item;
        });

        return response()->json($ranking);
    }
    
    // Método helper para aplicar filtros de periodo (reutilizable)
    private function aplicarFiltrosPeriodo($query, $request, $tablaPrefijo = 'deposito')
    {
        $campoFechaHora = $tablaPrefijo . '.fechaHora';
        $campoIdPeriodo = $tablaPrefijo . '.idPeriodo';
        
        // Prioridad 1: periodo_id explícito
        if ($request->filled('periodo_id') && $request->periodo_id !== 'custom') {
            $periodo = PeriodoAcademico::find($request->periodo_id);
            if ($periodo) {
                // Incluir depósitos que tengan el idPeriodo O que estén en el rango de fechas del periodo
                $query->where(function($q) use ($request, $periodo, $campoIdPeriodo, $campoFechaHora) {
                    $q->where($campoIdPeriodo, $request->periodo_id)
                      ->orWhereBetween($campoFechaHora, [$periodo->fecha_inicio, $periodo->fecha_fin]);
                });
            } else {
                $query->where($campoIdPeriodo, $request->periodo_id);
            }
        }
        // Prioridad 2: filtro como ID de periodo (para filtros rápidos que usan periodo)
        elseif ($request->filled('filtro') && is_numeric($request->filtro)) {
            $periodo = PeriodoAcademico::find($request->filtro);
            if ($periodo) {
                $query->where(function($q) use ($request, $periodo, $campoIdPeriodo, $campoFechaHora) {
                    $q->where($campoIdPeriodo, $request->filtro)
                      ->orWhereBetween($campoFechaHora, [$periodo->fecha_inicio, $periodo->fecha_fin]);
                });
            } else {
                $query->where($campoIdPeriodo, $request->filtro);
            }
        }
        // Prioridad 3: Rango de fechas explícito
        elseif ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereDate($campoFechaHora, '>=', $request->fecha_inicio)
                  ->whereDate($campoFechaHora, '<=', $request->fecha_fin);
        }
        // Prioridad 4: Filtros rápidos (semana, mes, año)
        elseif ($request->filtro && in_array($request->filtro, ['semana', 'mes', 'anio'])) {
            $query->whereRaw($this->getSqlPeriodo($request->filtro, $campoFechaHora));
        }
        // Por defecto: Últimos 3 meses
        else {
            $fechaInicio = now()->subMonths(3)->format('Y-m-d');
            $query->whereDate($campoFechaHora, '>=', $fechaInicio);
        }
    }
    
    // Método helper para aplicar filtros de periodo a PUNTAJE (usa idPeriodo directamente)
    private function aplicarFiltrosPeriodoPuntaje($query, $request)
    {
        // Prioridad 1: periodo_id explícito
        if ($request->filled('periodo_id') && $request->periodo_id !== 'custom') {
            $query->where('puntaje.idPeriodo', $request->periodo_id);
        }
        // Prioridad 2: filtro como ID de periodo
        elseif ($request->filled('filtro') && is_numeric($request->filtro)) {
            $query->where('puntaje.idPeriodo', $request->filtro);
        }
        // Prioridad 3: Rango de fechas - usar fechaAsignacion
        elseif ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereDate('puntaje.fechaAsignacion', '>=', $request->fecha_inicio)
                  ->whereDate('puntaje.fechaAsignacion', '<=', $request->fecha_fin);
        }
        // Prioridad 4: Filtros rápidos (semana, mes, año)
        elseif ($request->filtro && in_array($request->filtro, ['semana', 'mes', 'anio'])) {
            $query->whereRaw($this->getSqlPeriodo($request->filtro, 'puntaje.fechaAsignacion'));
        }
        // Por defecto: Últimos 3 meses
        else {
            $fechaInicio = now()->subMonths(3)->format('Y-m-d');
            $query->whereDate('puntaje.fechaAsignacion', '>=', $fechaInicio);
        }
    }
    
    // Método helper para aplicar filtros de periodo a DEPOSITOS (excluye otros periodos)
    private function aplicarFiltrosPeriodoDepositos($query, $request)
    {
        // Prioridad 1: periodo_id explícito
        if ($request->filled('periodo_id') && $request->periodo_id !== 'custom') {
            $periodo = PeriodoAcademico::find($request->periodo_id);
            if ($periodo) {
                // Incluir: depósitos con idPeriodo del periodo seleccionado O sin idPeriodo pero en rango de fechas
                // Excluir: depósitos con idPeriodo diferente
                $query->where(function($q) use ($request, $periodo) {
                    $q->where('deposito.idPeriodo', $request->periodo_id)
                      ->orWhere(function($q2) use ($periodo) {
                          $q2->whereNull('deposito.idPeriodo')
                             ->whereBetween('deposito.fechaHora', [$periodo->fecha_inicio, $periodo->fecha_fin]);
                      });
                });
            } else {
                $query->where('deposito.idPeriodo', $request->periodo_id);
            }
        }
        // Prioridad 2: filtro como ID de periodo
        elseif ($request->filled('filtro') && is_numeric($request->filtro)) {
            $periodo = PeriodoAcademico::find($request->filtro);
            if ($periodo) {
                $query->where(function($q) use ($request, $periodo) {
                    $q->where('deposito.idPeriodo', $request->filtro)
                      ->orWhere(function($q2) use ($periodo) {
                          $q2->whereNull('deposito.idPeriodo')
                             ->whereBetween('deposito.fechaHora', [$periodo->fecha_inicio, $periodo->fecha_fin]);
                      });
                });
            } else {
                $query->where('deposito.idPeriodo', $request->filtro);
            }
        }
        // Prioridad 3: Rango de fechas explícito
        elseif ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereDate('deposito.fechaHora', '>=', $request->fecha_inicio)
                  ->whereDate('deposito.fechaHora', '<=', $request->fecha_fin);
        }
        // Prioridad 4: Filtros rápidos (semana, mes, año)
        elseif ($request->filtro && in_array($request->filtro, ['semana', 'mes', 'anio'])) {
            $query->whereRaw($this->getSqlPeriodo($request->filtro, 'deposito.fechaHora'));
        }
        // Por defecto: Últimos 3 meses
        else {
            $fechaInicio = now()->subMonths(3)->format('Y-m-d');
            $query->whereDate('deposito.fechaHora', '>=', $fechaInicio);
        }
    }            
        

    public function tendencias(Request $request)
    {
        $request->validate([
            'agrupacion' => 'required|in:dia,semana,mes',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
        ],[
            'agrupacion.required' => 'El campo agrupación es obligatorio.',
            'agrupacion.in' => 'La agrupación seleccionada no es válida.',
            'fecha_inicio.required' => 'El campo fecha de inicio es obligatorio.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.required' => 'El campo fecha de fin es obligatorio.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',    
        ]);

        // Validar rango de fechas (máximo 3 meses)
        $this->validateDateRange($request->fecha_inicio, $request->fecha_fin);

        $format = $request->agrupacion === 'dia' ? '%Y-%m-%d' : 
                 ($request->agrupacion === 'semana' ? '%Y-%u' : '%Y-%m');

        $tendencias = Deposito::selectRaw("
                DATE_FORMAT(fechaHora, '{$format}') as periodo,
                COUNT(*) as total_depositos,
                SUM(tipoBasura.puntos) as total_puntos
            ")
            ->join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
            ->whereDate('fechaHora', '>=', $request->fecha_inicio)
            ->whereDate('fechaHora', '<=', $request->fecha_fin)
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        return response()->json([
            'tendencias' => $tendencias
        ]);
    }



    // --- MÉTODO PARA EXPORTAR PDF DE DEPÓSITOS ---
    public function exportarPDF(Request $request)
    {
        // Aumentar límites para reportes grandes
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        // Validar solo si tipo_residuo_id no es 'todos'
        $validationRules = [
            'periodo_id' => 'nullable|exists:periodos_academicos,idPeriodo',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
        ];
        
        if ($request->filled('tipo_residuo_id') && $request->tipo_residuo_id !== 'todos') {
            if (is_array($request->tipo_residuo_id)) {
                $validationRules['tipo_residuo_id'] = 'array';
                $validationRules['tipo_residuo_id.*'] = 'exists:tipoBasura,idTipoBasura';
            } else {
                $validationRules['tipo_residuo_id'] = 'exists:tipoBasura,idTipoBasura';
            }
        }

        $request->validate($validationRules, [
            'tipo_residuo_id.exists' => 'El tipo de residuo seleccionado no es válido.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',    
        ]);

        // Validar rango de fechas (máximo 3 meses) solo si no se usa periodo
        if (!$request->filled('periodo_id') && $request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $this->validateDateRange($request->fecha_inicio, $request->fecha_fin);
        }
        
        $query = $this->construirConsultaDepositos($request);

        $depositos = $query->get();
        $total = $depositos->count();
        $total_puntos = $depositos->sum(function($d) {
            return $d->puntos ?? $d->tipoBasura->puntos ?? 0;
        });
        $fecha_generacion = now()->format('d/m/Y H:i');

        // Calcular estadísticas generales para el PDF
        $usuarios_unicos = $depositos->pluck('user.id')->unique()->count();
        $estadisticas = [
            'total_depositos' => $total,
            'total_puntos' => $total_puntos,
            'usuarios_unicos' => $usuarios_unicos,
        ];

        // Calcular desglose por tipo de basura
        $porTipoBasura = $depositos->groupBy('tipoBasura.nombre')->map(function($items, $nombre) {
            return [
                'nombre' => $nombre,
                'cantidad' => $items->count(),
                'puntos_totales' => $items->sum(function($d) {
                    return $d->puntos ?? $d->tipoBasura->puntos ?? 0;
                }),
            ];
        })->values()->all();

        // Obtener nombres de filtros para el encabezado
        $filtros_nombres = [
            'periodo' => 'Todos',
            'tipo_residuo' => 'Todos',
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
        ];

        if ($request->filled('periodo_id')) {
            $periodo = PeriodoAcademico::find($request->periodo_id);
            if ($periodo) {
                $filtros_nombres['periodo'] = $periodo->nombre;
            }
        }

        if ($request->filled('tipo_residuo_id') && $request->tipo_residuo_id !== 'todos') {
            if (is_array($request->tipo_residuo_id)) {
                $nombres = \App\Models\TipoBasura::whereIn('idTipoBasura', $request->tipo_residuo_id)->pluck('nombre')->toArray();
                $filtros_nombres['tipo_residuo'] = implode(', ', $nombres);
            } else {
                $tipoResiduo = \App\Models\TipoBasura::find($request->tipo_residuo_id);
                if ($tipoResiduo) {
                    $filtros_nombres['tipo_residuo'] = $tipoResiduo->nombre;
                }
            }
        }

        $pdf = Pdf::loadView('reportes.depositos-pdf', [
            'depositos' => $depositos,
            'total' => $total,
            'total_puntos' => $total_puntos,
            'filtros' => $filtros_nombres,
            'fecha_generacion' => $fecha_generacion,
            'estadisticas' => $estadisticas,
            'porTipoBasura' => $porTipoBasura,
        ]);
        $pdf->setOption(['isPhpEnabled' => true]);
        return $pdf->download('reporte_depositos.pdf');
    }

    // --- PDF: Ranking por periodo (unificado) ---
    public function exportarRankingPDF(Request $request)
    {
        // Aumentar límites para reportes grandes
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $tipo = $request->get('tipo', 'estudiantes');
        
        // Determinar nombre del período para el encabezado
        $nombre_periodo = '';
        if ($request->filled('filtro')) {
            $filtro = $request->filtro;
            if (is_numeric($filtro)) {
                $periodo = PeriodoAcademico::find($filtro);
                $nombre_periodo = $periodo ? $periodo->nombre : 'Período Seleccionado';
            } else {
                $nombre_periodo = match($filtro) {
                    'semana' => 'Semana Actual (' . now()->startOfWeek()->format('d/m') . ' - ' . now()->endOfWeek()->format('d/m') . ')',
                    'mes'    => 'Mes Actual (' . now()->format('F Y') . ')',
                    'anio'   => 'Año Actual (' . now()->format('Y') . ')',
                    default  => 'Todo el Tiempo (Últimos 3 meses)'
                };
            }
        } elseif ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $nombre_periodo = 'Rango: ' . \Carbon\Carbon::parse($request->fecha_inicio)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($request->fecha_fin)->format('d/m/Y');
        } else {
            $nombre_periodo = 'Últimos 3 meses';
        }

        // Usar exactamente la misma lógica que ranking() y rankingCursos() - CON TABLA PUNTAJE
        if ($tipo === 'cursos') {
            $query = DB::table('curso')
                ->join('curso_paralelo', 'curso.idCurso', '=', 'curso_paralelo.idCurso')
                ->join('estudiante', 'curso_paralelo.idCursoParalelo', '=', 'estudiante.idCursoParalelo')
                ->join('usuario', 'estudiante.idUser', '=', 'usuario.id')
                ->join('puntaje', 'usuario.id', '=', 'puntaje.idUser')
                ->select(
                    'curso.idCurso',
                    'curso.nombre as curso_nombre',
                    'curso_paralelo.idParalelo',
                    'curso_paralelo.idCursoParalelo',
                    DB::raw('(SELECT nombre FROM paralelo WHERE idParalelo = curso_paralelo.idParalelo) as paralelo_nombre'),
                    DB::raw('SUM(puntaje.puntos) as total_puntos'),
                    DB::raw('COUNT(DISTINCT usuario.id) as cantidad_estudiantes')
                );

            // Aplicar filtros de periodo para PUNTAJE
            $this->aplicarFiltrosPeriodoPuntaje($query, $request);

            $usuarios = $query->groupBy('curso.idCurso', 'curso.nombre', 'curso_paralelo.idParalelo', 'curso_paralelo.idCursoParalelo')
                ->orderByDesc('total_puntos')
                ->limit(15)
                ->get();
                
            // Agregar total_depositos para cada curso
            foreach ($usuarios as $curso) {
                $depositosQuery = DB::table('deposito')
                    ->join('usuario', 'deposito.idUser', '=', 'usuario.id')
                    ->join('estudiante', 'usuario.id', '=', 'estudiante.idUser')
                    ->join('curso_paralelo', 'estudiante.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
                    ->where('curso_paralelo.idCurso', $curso->idCurso)
                    ->where('curso_paralelo.idParalelo', $curso->idParalelo);
                
                $this->aplicarFiltrosPeriodoDepositos($depositosQuery, $request);
                $curso->total_depositos = $depositosQuery->count();
            }
                
            // Formatear nombre completo del curso
            $usuarios = $usuarios->map(function($item) {
                $item->nombre_completo = $item->curso_nombre . ' ' . $item->paralelo_nombre;
                return $item;
            });
        } else {
            // Ranking de estudiantes - USAR TABLA PUNTAJE
            $query = User::where('rol', 'estudiante')
                ->join('puntaje', 'usuario.id', '=', 'puntaje.idUser')
                ->leftJoin('estudiante', 'usuario.id', '=', 'estudiante.idUser')
                ->leftJoin('curso_paralelo', 'estudiante.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
                ->leftJoin('curso', 'curso_paralelo.idCurso', '=', 'curso.idCurso')
                ->leftJoin('paralelo', 'curso_paralelo.idParalelo', '=', 'paralelo.idParalelo')
                ->select(
                    'usuario.id',
                    'usuario.nombres',
                    'usuario.primerApellido',
                    'usuario.segundoApellido',
                    DB::raw("CONCAT(usuario.nombres, ' ', usuario.primerApellido, ' ', COALESCE(usuario.segundoApellido, '')) as nombre_completo"),
                    DB::raw("CONCAT(COALESCE(curso.nombre, 'N/A'), ' - ', COALESCE(paralelo.nombre, 'N/A')) as curso_paralelo"),
                    DB::raw('SUM(puntaje.puntos) as total_puntos')
                );

            // Aplicar filtros de periodo para PUNTAJE
            $this->aplicarFiltrosPeriodoPuntaje($query, $request);

            $usuarios = $query->groupBy('usuario.id', 'usuario.nombres', 'usuario.primerApellido', 'usuario.segundoApellido', 'curso.nombre', 'paralelo.nombre')
                ->orderByDesc('total_puntos')
                ->limit(15)
                ->get();
                
            // Agregar total_depositos para cada usuario
            foreach ($usuarios as $user) {
                $depositosQuery = DB::table('deposito')->where('idUser', $user->id);
                $this->aplicarFiltrosPeriodoDepositos($depositosQuery, $request);
                $user->total_depositos = $depositosQuery->count();
            }
        }

        $fecha_generacion = now()->format('d/m/Y H:i');
        
        // Calcular estadísticas
        $estadisticas = [
            'total_usuarios' => $usuarios->count(),
            'total_puntos' => $usuarios->sum('total_puntos')
        ];

        // Metadatos para el encabezado
        $filtros_nombres = [
            'periodo' => $nombre_periodo,
            'tipo_ranking' => $tipo === 'cursos' ? 'Cursos' : 'Estudiantes',
        ];

        $pdf = Pdf::loadView('reportes.ranking-pdf', compact('usuarios', 'filtros_nombres', 'fecha_generacion', 'estadisticas'));
        $pdf->setOption(['isPhpEnabled' => true]);
        return $pdf->download('reporte_ranking.pdf');
    }

    // --- PDF: Depósitos por basurero ---


    // --- PDF: Depósitos por fecha ---
    public function exportarDepositosPorFechaPDF(Request $request)
    {
        // Aumentar límites para reportes grandes
        set_time_limit(300);
        ini_set('memory_limit', '1024M');


        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
        ],[
            'fecha_inicio.required' => 'El campo fecha de inicio es obligatorio.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.required' => 'El campo fecha de fin es obligatorio.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',    
        ]);

        // Validar rango de fechas (máximo 3 meses)
        $this->validateDateRange($request->fecha_inicio, $request->fecha_fin);

        $query = \App\Models\Deposito::with(['user', 'tipoBasura', 'basurero'])
                
            ->whereDate('fechaHora', '>=', $request->fecha_inicio)
            ->whereDate('fechaHora', '<=', $request->fecha_fin)
            ->orderBy('fechaHora');

        $depositos = $query->get();
        $fecha_generacion = now()->format('d/m/Y H:i');
        $total = $depositos->count();
        $total_puntos = $depositos->sum(function($d) {
            return $d->tipoBasura->puntos ?? 0;
        });
        $usuarios_unicos = $depositos->pluck('user.id')->unique()->count();
        $estadisticas = [
            'total_depositos' => $total,
            'total_puntos' => $total_puntos,
            'usuarios_unicos' => $usuarios_unicos,
        ];
        $porTipoBasura = $depositos->groupBy('tipoBasura.nombre')->map(function($items, $nombre) {
            return [
                'nombre' => $nombre,
                'cantidad' => $items->count(),
                'puntos_totales' => $items->sum(function($d) {
                    return $d->tipoBasura->puntos ?? 0;
                }),
            ];
        })->values()->all();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.deposito-fecha-pdf', [
            'depositos' => $depositos,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'fecha_generacion' => $fecha_generacion,
            'estadisticas' => $estadisticas,
            'porTipoBasura' => $porTipoBasura,
        ]);
        return $pdf->download('reporte_depositos_fecha.pdf');
    }

    // --- EXCEL: Exportar depósitos a Excel ---
    public function exportarExcel(Request $request)
    {
        // Validar solo si tipo_residuo_id no es 'todos'
        $validationRules = [
            'periodo_id' => 'nullable|exists:periodos_academicos,idPeriodo',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
        ];
        
        if ($request->filled('tipo_residuo_id') && $request->tipo_residuo_id !== 'todos') {
            if (is_array($request->tipo_residuo_id)) {
                $validationRules['tipo_residuo_id'] = 'array';
                $validationRules['tipo_residuo_id.*'] = 'exists:tipoBasura,idTipoBasura';
            } else {
                $validationRules['tipo_residuo_id'] = 'exists:tipoBasura,idTipoBasura';
            }
        }

        $request->validate($validationRules, [
            'tipo_residuo_id.exists' => 'El tipo de residuo seleccionado no es válido.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',    
        ]);

        $query = $this->construirConsultaDepositos($request);

        $depositos = $query->get();

        // Crear archivo CSV (equivalente a Excel para este caso)
        $filename = 'reporte_depositos_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($depositos) {
            $file = fopen('php://output', 'w');
            
            // Encabezados
            fputcsv($file, [
                'ID', 'Usuario', 'Tipo de Basura', 'Basurero', 'Fecha y Hora', 'Puntos'
            ]);

            // Datos
            foreach ($depositos as $deposito) {
                fputcsv($file, [
                    $deposito->idDeposito,
                    $deposito->user ? $deposito->user->nombres . ' ' . $deposito->user->primerApellido : 'N/A',
                    $deposito->tipoBasura ? $deposito->tipoBasura->nombre : 'N/A',
                    $deposito->basurero ? $deposito->basurero->ubicacion : 'N/A',
                    $deposito->fechaHora,
                    $deposito->puntos ?? ($deposito->tipoBasura ? $deposito->tipoBasura->puntos : 0)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function aplicarFiltroPeriodo($query, $periodo)
    {
        $ahora = Carbon::now();
        
        switch ($periodo) {
            case 'semana':
                $query->whereBetween('fechaHora', [
                    $ahora->copy()->startOfWeek(),
                    $ahora->copy()->endOfWeek()
                ]);
                break;
            case 'mes':
                $query->whereBetween('fechaHora', [
                    $ahora->copy()->startOfMonth(),
                    $ahora->copy()->endOfMonth()
                ]);
                break;
            case 'anio':
                $query->whereBetween('fechaHora', [
                    $ahora->copy()->startOfYear(),
                    $ahora->copy()->endOfYear()
                ]);
                break;
        }
    }
    private function getSqlPeriodo($periodo, $campo, $anio = null)
    {
        $year = $anio ?? now()->year;
        
        switch ($periodo) {
            case 'semana':
                return "$campo BETWEEN '" . now()->startOfWeek()->toDateString() . " 00:00:00' 
                        AND '" . now()->endOfWeek()->toDateString() . " 23:59:59'";

            case 'mes':
                return "$campo BETWEEN '" . now()->startOfMonth()->toDateString() . " 00:00:00' 
                        AND '" . now()->endOfMonth()->toDateString() . " 23:59:59'";

            case 'anio':
                return "$campo BETWEEN '" . now()->startOfYear()->toDateString() . " 00:00:00' 
                        AND '" . now()->endOfYear()->toDateString() . " 23:59:59'";

            case 'trimestre_1':
                // Enero - Abril (4 meses)
                return "$campo BETWEEN '{$year}-01-01 00:00:00' AND '{$year}-04-30 23:59:59'";

            case 'trimestre_3':
                // Septiembre - Diciembre (4 meses)
                return "$campo BETWEEN '{$year}-09-01 00:00:00' AND '{$year}-12-31 23:59:59'";

            case 'todo':
            default:
                return "1=1"; // sin filtro
        }
    }

    // Método privado unificado para construir la consulta de depósitos
    private function construirConsultaDepositos(Request $request)
    {
        $query = Deposito::with(['user', 'tipoBasura', 'basurero'])
            ->when($request->filled('tipo_residuo_id') && $request->tipo_residuo_id !== '' && $request->tipo_residuo_id !== 'todos', function($q) use ($request) {
                if (is_array($request->tipo_residuo_id)) {
                    $q->whereIn('idTipoBasura', $request->tipo_residuo_id);
                } else {
                    $q->where('idTipoBasura', $request->tipo_residuo_id);
                }
            });

        // Usar el método mejorado de filtrado de depósitos
        $this->aplicarFiltrosPeriodoDepositos($query, $request);

        // Ordenamiento unificado: Descendente (más recientes primero)
        $query->orderBy('fechaHora', 'desc');

        return $query;
    }

    /**
     * Valida que el rango de fechas no exceda 3 meses
     */
    private function validateDateRange($fechaInicio, $fechaFin)
    {
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);
        
        $diffInMonths = $inicio->diffInMonths($fin);
        
        if ($diffInMonths > 4) {
            throw new \Illuminate\Validation\ValidationException(
                \Illuminate\Support\Facades\Validator::make([], [])
                    ->errors()
                    ->add('fecha_fin', 'El rango de fechas no puede exceder 4 meses.')
            );
        }
    }

    public function evolucionEstudiante(Request $request)
    {
        $request->validate([
            'estudiante_id' => 'required|exists:usuario,id',
            'periodo_id' => 'nullable|exists:periodos_academicos,idPeriodo',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
        ]);

        // Validar rango de fechas solo si no se usa periodo y se enviaron fechas
        if (!$request->filled('periodo_id') && $request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $this->validateDateRange($request->fecha_inicio, $request->fecha_fin);
        }

        $estudiante = User::find($request->estudiante_id);
        $nombre_completo = $estudiante->nombres . ' ' . $estudiante->primerApellido . ' ' . ($estudiante->segundoApellido ?? '');

        // Obtener depósitos con filtrado mejorado
        $query = Deposito::with(['tipoBasura', 'basurero'])
            ->where('idUser', $request->estudiante_id);

        // Aplicar filtrado mejorado de depósitos
        $this->aplicarFiltrosPeriodoDepositos($query, $request);

        $depositos = $query->orderBy('fechaHora', 'asc')->get();

        // Calcular total_puntos sumando los puntos de los depósitos filtrados
        $total_puntos = $depositos->sum(function($d) {
            return $d->puntos ?? $d->tipoBasura->puntos ?? 0;
        });

        return response()->json([
            'estudiante' => [
                'id' => $estudiante->id,
                'nombre' => $nombre_completo
            ],
            'depositos' => $depositos,
            'total_puntos' => $total_puntos,
            'usando_rango_defecto' => !$request->filled('periodo_id') && !$request->filled('fecha_inicio') && !$request->filled('fecha_fin')
        ]);
    }

    public function exportarEvolucionEstudiantePDF(Request $request)
    {
        try {
            $request->validate([
                'estudiante_id' => 'required|exists:usuario,id',
                'periodo_id' => 'nullable', // Quitamos validación estricta temporalmente para descartar problemas de DB
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
            ]);

            if (!$request->filled('periodo_id') && $request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
                $this->validateDateRange($request->fecha_inicio, $request->fecha_fin);
            }

            $estudiante = User::find($request->estudiante_id);
            if (!$estudiante) {
                return response()->json(['error' => 'Estudiante no encontrado'], 404);
            }
            
            $nombre_completo = $estudiante->nombres . ' ' . $estudiante->primerApellido . ' ' . ($estudiante->segundoApellido ?? '');
            
            $curso_info = 'N/A';
            try {
                if ($estudiante->estudiante && $estudiante->estudiante->cursoParalelo) {
                    $curso = $estudiante->estudiante->cursoParalelo->curso->nombre ?? '';
                    $paralelo = $estudiante->estudiante->cursoParalelo->paralelo->nombre ?? '';
                    if ($curso && $paralelo) {
                        $curso_info = "$curso - $paralelo";
                    }
                }
            } catch (\Exception $e) {
                // Mantener N/A
            }

            $query = Deposito::with(['tipoBasura', 'basurero'])
                ->where('idUser', $request->estudiante_id);

            $rango_fechas = '';

            // Aplicar filtrado mejorado de depósitos
            $this->aplicarFiltrosPeriodoDepositos($query, $request);
            
            // Determinar nombre del rango para el PDF
            if ($request->filled('periodo_id')) {
                $periodo = PeriodoAcademico::find($request->periodo_id);
                $rango_fechas = $periodo ? $periodo->nombre : 'Periodo ID: ' . $request->periodo_id;
            } else {
                $fechaInicio = $request->fecha_inicio ?? now()->subMonths(3)->format('Y-m-d');
                $fechaFin = $request->fecha_fin ?? now()->format('Y-m-d');
                $rango_fechas = Carbon::parse($fechaInicio)->format('d/m/Y') . ' - ' . Carbon::parse($fechaFin)->format('d/m/Y');
            }

            $depositos = $query->orderBy('fechaHora', 'desc')->get();

            // Calcular total_puntos sumando los puntos de los depósitos filtrados
            $total_puntos = $depositos->sum(function($d) {
                return $d->puntos ?? $d->tipoBasura->puntos ?? 0;
            });

            $fecha_generacion = now()->format('d/m/Y H:i');

            $pdf = Pdf::loadView('reportes.evolucion-estudiante-pdf', compact(
                'estudiante', 'nombre_completo', 'curso_info', 'depositos', 'total_puntos', 'fecha_generacion', 'rango_fechas'
            ));
            
            return $pdf->download('reporte_estudiante.pdf');

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar PDF',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

} 