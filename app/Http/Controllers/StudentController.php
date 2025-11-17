<?php

namespace App\Http\Controllers;

use App\Models\AsignacionPuntaje;
use App\Models\Deposito;
use App\Models\Estudiante;
use App\Models\PeriodoAcademico;
use App\Models\Puntaje;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function dashboard(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        // Obtener datos del estudiante con relaciones optimizadas
        $student = $this->getStudentData($user);
        $currentPeriod = $this->getCurrentPeriod();
        $year = $currentPeriod?->fecha_inicio?->year ?? Carbon::now()->year;
        
        // Obtener depósitos del estudiante
        $deposits = $this->getStudentDeposits($user, $year);
        
        // Calcular estadísticas usando la tabla Puntaje
        $totalPoints = $this->getTotalPointsFromPuntaje($user, $year);
        $currentBimesterPoints = $this->getCurrentBimesterPointsFromPuntaje($user, $currentPeriod);
        $ranking = $this->getStudentRanking($user, $year);
        $bimesterGoal = 100; // Meta por defecto del bimestre
        
        // Obtener top 3 del curso para mostrar en dashboard
        $courseTop3 = $this->getCourseRanking($user, $year)->take(3);
        
        // Obtener información sobre materias y docentes
        $subjectsInfo = $this->getSubjectsWithTeachers($user);
        
        // Obtener notas académicas recientes
        $academicGrades = $this->getAcademicGrades($user, $currentPeriod);

        return Inertia::render('Students/Dashboard', [
            'student' => $student,
            'deposits' => $this->formatDepositsForFrontend($deposits->take(10)), // Solo los últimos 10 para el dashboard
            'totalDepositsCount' => $deposits->count(), // Total real de depósitos
            'currentPeriod' => $currentPeriod ? [
                'id' => $currentPeriod->id,
                'nombre' => $currentPeriod->nombre,
            ] : null,
            'totalPoints' => $totalPoints,
            'currentBimesterPoints' => $currentBimesterPoints,
            'ranking' => $ranking,
            'bimesterGoal' => $bimesterGoal,
            'courseTop3' => $courseTop3,
            'subjectsInfo' => $subjectsInfo,
            'academicGrades' => $academicGrades,
        ]);
    }

    public function pointsHistory(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $student = $this->getStudentData($user);
        $currentPeriod = $this->getCurrentPeriod();
        $year = $currentPeriod?->fecha_inicio?->year ?? Carbon::now()->year;

        $deposits = $this->getStudentDepositsWithDetails($user, $year);
        $totalPoints = $this->getTotalPointsFromPuntaje($user, $year);
        $periods = $this->getPeriodsForYear($year);
        $pointsByPeriod = $this->getPointsByPeriod($user);

        return Inertia::render('Students/PointsHistory', [
            'student' => $student,
            'deposits' => $this->formatDepositsForFrontend($deposits, true), // Con bimestre
            'periods' => $periods->map(fn ($p) => [
                'id' => $p->idPeriodo, // Usar la clave primaria correcta
                'nombre' => $p->nombre,
            ])->values(),
            'totalPoints' => $totalPoints,
            'pointsByPeriod' => $pointsByPeriod,
        ]);
    }

    public function profile(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $student = $this->getStudentData($user);
        $currentPeriod = $this->getCurrentPeriod();
        $year = $currentPeriod?->fecha_inicio?->year ?? Carbon::now()->year;
        
        $deposits = $this->getStudentDeposits($user, $year);
        $totalPoints = $this->getTotalPointsFromPuntaje($user, $year);
        $ranking = $this->getStudentRanking($user, $year);
        $academicGrades = $this->getAcademicGrades($user, $currentPeriod);

        return Inertia::render('Students/Profile', [
            'student' => $student,
            'deposits' => $this->formatDepositsForFrontend($deposits, true), // Con bimestre
            'currentPeriod' => $currentPeriod ? [
                'id' => $currentPeriod->idPeriodo,
                'nombre' => $currentPeriod->nombre,
            ] : null,
            'totalPoints' => $totalPoints,
            'ranking' => $ranking,
            'academicGrades' => $academicGrades,
        ]);
    }

    public function ranking(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $student = $this->getStudentData($user);
        $currentPeriod = $this->getCurrentPeriod();
        $year = $currentPeriod?->fecha_inicio?->year ?? Carbon::now()->year;

        // Período seleccionado vía query (opcional)
        $selectedPeriodId = $request->integer('periodo_id');
        $selectedPeriod = $selectedPeriodId ? PeriodoAcademico::find($selectedPeriodId) : null;
        if ($selectedPeriod) {
            $year = $selectedPeriod->fecha_inicio?->year ?? $year;
        }

        // Períodos disponibles para el año
        $periods = $this->getPeriodsForYear($year);

        // Obtener ranking del curso-paralelo filtrando por período si se selecciona
        $ranking = $this->getCourseRanking($user, $year, $selectedPeriod?->idPeriodo);
        $myPosition = $this->getStudentRanking($user, $year, $selectedPeriod?->idPeriodo);
        $totalStudents = $ranking->count();

        return Inertia::render('Students/Ranking', [
            'student' => $student,
            'ranking' => $ranking,
            'currentPeriod' => $currentPeriod ? [
                'id' => $currentPeriod->idPeriodo,
                'nombre' => $currentPeriod->nombre,
            ] : null,
            'myPosition' => $myPosition,
            'totalStudents' => $totalStudents,
            'periods' => $periods->map(fn($p) => [
                'idPeriodo' => $p->idPeriodo,
                'nombre' => $p->nombre,
            ]),
            'selectedPeriodId' => $selectedPeriod?->idPeriodo,
        ]);
    }

    // ===== Métodos Helper Optimizados =====
    
    /**
     * Obtiene los datos del estudiante con relaciones optimizadas
     */
    private function getStudentData(User $user): array
    {
        $estudiante = $user->estudiante()->with([
            'cursoParalelo.curso',
            'cursoParalelo.paralelo'
        ])->first();

        $apellidos = trim(implode(' ', array_filter([
            $user->primerApellido, 
            $user->segundoApellido
        ])));

        return [
            'id' => $user->id,
            'nombres' => $user->nombres,
            'apellidos' => $apellidos,
            'codigo_estudiante' => $user->qr_codigo,
            'qr_codigo' => $user->qr_codigo, // Campo para verificar si el QR está activo
            'curso' => $estudiante?->cursoParalelo?->curso ? [
                'id' => $estudiante->cursoParalelo->curso->idCurso,
                'nombre' => $estudiante->cursoParalelo->curso->nombre,
            ] : null,
            'paralelo' => $estudiante?->cursoParalelo?->paralelo ? [
                'id' => $estudiante->cursoParalelo->paralelo->idParalelo,
                'nombre' => $estudiante->cursoParalelo->paralelo->nombre,
            ] : null,
        ];
    }

    /**
     * Obtiene el período académico actual
     */
    private function getCurrentPeriod(): ?PeriodoAcademico
    {
        return PeriodoAcademico::where('activo', true)
            ->orderBy('fecha_inicio', 'desc')
            ->first() ?: $this->findPeriodForDate(Carbon::now());
    }

    /**
     * Obtiene los depósitos del estudiante con relaciones optimizadas
     */
    private function getStudentDeposits(User $user, ?int $year = null)
    {
        $query = $user->depositos()
            ->join('tipoBasura as tb', 'deposito.idTipoBasura', '=', 'tb.idTipoBasura')
            // Evitar sobrescribir el campo 'puntos' del depósito: alias
            ->select('deposito.*', 'deposito.puntos as puntos_deposito',\DB::raw('tb.puntos as puntos_tipo'))

            // Orden principal por período (nulos al final), luego por fecha
            ->orderBy('deposito.fechaHora', 'desc');

        if ($year) {
            $query->whereYear('fechaHora', $year);
        }

        return $query->get();
    }

    /**
     * Formatea los depósitos para el frontend
     */
    private function formatDepositsForFrontend($deposits, bool $withBimester = false): array
    {
        if ($deposits->isEmpty()) {
            return [];
        }

        // Verificar si los datos vienen de la consulta con detalles o del modelo Eloquent
        $isDetailedQuery = $deposits->first() instanceof \stdClass;

        // Precargar períodos si necesitamos bimestres y no tenemos datos detallados
        $periods = null;
        if ($withBimester && !$isDetailedQuery && !$deposits->isEmpty()) {
            $year = $deposits->first()->fechaHora->year;
            $periods = $this->getPeriodsForYear($year);
        }

        return $deposits->map(function ($deposito) use ($withBimester, $periods, $isDetailedQuery) {
            if ($isDetailedQuery) {
                // Datos de consulta detallada (getStudentDepositsWithDetails)
                $data = [
                    'id' => $deposito->idDeposito,
                    'fecha_deposito' => $deposito->fechaHora,
                    // Preferir snapshot de puntos del depósito; fallback a puntos del tipo de basura
                    'puntaje_obtenido' => (int) ($deposito->puntos_deposito ?? $deposito->puntos_calculados ?? 0),
                    'periodo_id' => $deposito->idPeriodo,
                    'tipo_basura' => [
                        'id' => $deposito->idTipoBasura,
                        'nombre' => $deposito->tipo_basura_nombre,
                        'descripcion' => $deposito->tipo_basura_descripcion,
                        'puntos_base' => (int) $deposito->tipo_basura_puntos,
                    ],
                    'basurero' => [
                        'id' => $deposito->idBasurero,
                        'nombre' => $deposito->basurero_nombre,
                        'ubicacion' => $deposito->basurero_ubicacion,
                        'descripcion' => $deposito->basurero_descripcion,
                    ],
                    'periodo' => [
                        'nombre' => $deposito->periodo_nombre ?? 'Sin período',
                    ],
                ];

                if ($withBimester && $deposito->periodo_nombre) {
                    $data['bimestre'] = $this->mapBimesterNumber($deposito->periodo_nombre);
                }
            } else {
                // Datos del modelo Eloquent (método original)
                $data = [
                    'id' => $deposito->idDeposito,
                    'fecha_deposito' => $deposito->fechaHora->toDateTimeString(),
                    'cantidad' => (float) $deposito->peso,
                    // Preferir snapshot de puntos del depósito
                    'puntaje_obtenido' => (int) ($deposito->puntos_deposito ?? $deposito->puntos_tipo ?? 0),
                    'tipo_basura' => $deposito->tipoBasura ? [
                        'id' => $deposito->tipoBasura->idTipoBasura,
                        'nombre' => $deposito->tipoBasura->nombre,
                    ] : null,
                ];

                if ($withBimester) {
                    $period = $this->findPeriodForDate($deposito->fechaHora, $periods);
                    $data['bimestre'] = $period ? $this->mapBimesterNumber($period->nombre) : 1;
                }
            }

            return $data;
        })->values()->all();
    }

    /**
     * Calcula los puntos del bimestre actual
     */
    private function getCurrentBimesterPoints($deposits, ?PeriodoAcademico $currentPeriod): int
    {
        if (!$currentPeriod || $deposits->isEmpty()) {
            return 0;
        }

        return $deposits->filter(function ($deposito) use ($currentPeriod) {
            $depositoDate = Carbon::parse($deposito->fechaHora);
            return $depositoDate->between(
                $currentPeriod->fecha_inicio,
                $currentPeriod->fecha_fin
            );
        })->sum('puntos');
    }

    /**
     * Obtiene el ranking del estudiante en su curso
     */
    private function getStudentRanking(User $user, ?int $year = null, ?int $periodId = null): int
    {
        $estudiante = $user->estudiante()->first();
        if (!$estudiante) {
            return 0;
        }

        // Obtener IDs de estudiantes del mismo curso-paralelo
        $estudiantesIds = Estudiante::where('idCursoParalelo', $estudiante->idCursoParalelo)
            ->pluck('idUser');

        // Calcular puntos totales por estudiante usando la tabla Puntaje
        $query = Puntaje::select('idUser', DB::raw('COALESCE(SUM(puntos), 0) as total_puntos'))
            ->whereIn('idUser', $estudiantesIds);

        if ($periodId) {
            $query->where('idPeriodo', $periodId);
        } elseif ($year) {
            $query->whereHas('periodoAcademico', function($q) use ($year) {
                $q->whereYear('fecha_inicio', $year);
            });
        }

        $rankings = $query->groupBy('idUser')
            ->orderByDesc('total_puntos')
            ->get();

        // Encontrar la posición del estudiante
        $position = $rankings->search(function ($item) use ($user) {
            return $item->idUser == $user->id;
        });

        return $position !== false ? $position + 1 : $rankings->count() + 1;
    }

    /**
     * Obtiene períodos académicos por año
     */
    private function getPeriodsForYear(int $year)
    {
        return PeriodoAcademico::whereYear('fecha_inicio', $year)
            ->orderBy('fecha_inicio')
            ->get();
    }

    /**
     * Encuentra el período académico para una fecha específica
     */
    private function findPeriodForDate(Carbon $date, $periods = null): ?PeriodoAcademico
    {
        $periods = $periods ?: PeriodoAcademico::orderBy('fecha_inicio')->get();
        
        return $periods->first(function (PeriodoAcademico $period) use ($date) {
            return $date->between($period->fecha_inicio, $period->fecha_fin);
        });
    }

    /**
     * Mapea el nombre del período al número de bimestre
     */
    private function mapBimesterNumber(?string $nombre): int
    {
        if (!$nombre) return 1;
        
        $nombre = mb_strtolower($nombre);
        return match (true) {
            str_contains($nombre, 'primer') => 1,
            str_contains($nombre, 'segundo') => 2,
            str_contains($nombre, 'tercer') => 3,
            str_contains($nombre, 'cuarto') => 4,
            default => 1,
        };
    }

    /**
     * Obtiene el total de puntos del estudiante desde la tabla Puntaje
     */
    private function getTotalPointsFromPuntaje(User $user, ?int $year = null): int
    {
        // Como tienes un trigger que suma automáticamente, solo obtenemos el valor del campo puntos
        $puntaje = $user->puntajes();
        
        return  $puntaje->sum('puntos') ? $puntaje->sum('puntos') : 0;
    }   

    /**
     * Obtiene los puntos del bimestre actual desde la tabla Puntaje
     */
    private function getCurrentBimesterPointsFromPuntaje(User $user, ?PeriodoAcademico $currentPeriod): int
{
    if (!$currentPeriod) {
        return 0;
    }

    $puntaje = $user->puntajes()
        ->where('idPeriodo', $currentPeriod->idPeriodo)
        ->first();

    return $puntaje?->puntos ?? 0;
}


    /**
     * Obtiene el ranking completo del curso-paralelo
     */
    private function getCourseRanking(User $user, ?int $year = null, ?int $periodId = null)
    {
        $estudiante = $user->estudiante()->first();
        if (!$estudiante) {
            return collect();
        }

        // Obtener estudiantes del mismo curso-paralelo
        $estudiantesIds = Estudiante::where('idCursoParalelo', $estudiante->idCursoParalelo)
            ->pluck('idUser');

        // Calcular puntos totales por estudiante usando la tabla Puntaje
        $query = Puntaje::select('idUser', DB::raw('COALESCE(SUM(puntos), 0) as total_puntos'))
            ->whereIn('idUser', $estudiantesIds);

        if ($periodId) {
            $query->where('idPeriodo', $periodId);
        } elseif ($year) {
            $query->whereHas('periodoAcademico', function($q) use ($year) {
                $q->whereYear('fecha_inicio', $year);
            });
        }

        $puntajes = $query->groupBy('idUser')
            ->orderByDesc('total_puntos')
            ->get()
            ->keyBy('idUser');

        // Obtener información de los usuarios
        $usuarios = User::whereIn('id', $estudiantesIds)
            ->select('id', 'nombres', 'primerApellido', 'segundoApellido')
            ->get();

        // Combinar datos y crear ranking
        $ranking = $usuarios->map(function ($usuario) use ($puntajes) {
            $puntaje = $puntajes->get($usuario->id);
            $apellidos = trim(implode(' ', array_filter([
                $usuario->primerApellido, 
                $usuario->segundoApellido
            ])));

            return [
                'id' => $usuario->id,
                'nombres' => $usuario->nombres,
                'apellidos' => $apellidos,
                'puntaje' => $puntaje ? (int) $puntaje->total_puntos : 0,
            ];
        })
        ->sortByDesc('puntaje')
        ->values()
        ->map(function ($item, $index) {
            $item['posicion'] = $index + 1;
            return $item;
        });

        return $ranking;
    }

    /**
     * Obtiene información sobre materias que asignan puntos y sus docentes
     */
    private function getSubjectsWithTeachers(User $user): array
    {
        $estudiante = $user->estudiante()->first();
        if (!$estudiante) {
            return [];
        }

        $result = [];

        // 1. Obtener tipos de basura disponibles
        $tiposBasura = DB::table('tipoBasura')
            ->select('idTipoBasura', 'nombre', 'descripcion', 'puntos')
            ->where('estado', true)
            ->whereNull('deleted_at')
            ->orderBy('nombre')
            ->get();

        foreach ($tiposBasura as $tipo) {
            $result[] = [
                'id' => 'tipo_' . $tipo->idTipoBasura,
                'tipo' => 'tipo_basura',
                'materia' => $tipo->nombre,
                'puntos' => (int) $tipo->puntos,
                'descripcion' => $tipo->descripcion,// Los tipos de basura no tienen docentes asignados directamente
            ];
        }

        // 2. Obtener materias del curso del estudiante con sus docentes
        if ($estudiante->idCursoParalelo) {
            $materiasConDocentes = DB::table('materia')
                ->join('docente_materia_curso as dmc', 'materia.idMateria', '=', 'dmc.idMateria')
                ->join('docente', 'dmc.idDocente', '=', 'docente.idDocente')
                ->join('usuario', 'docente.idUser', '=', 'usuario.id')
                ->where('dmc.idCursoParalelo', $estudiante->idCursoParalelo)
                ->where('materia.estado', true)
                ->select(
                    'materia.idMateria',
                    'materia.nombre as materia_nombre',
                    'usuario.nombres as docente_nombres',
                    'usuario.primerApellido as docente_apellido1',
                    'usuario.segundoApellido as docente_apellido2'
                )
                ->orderBy('materia.nombre')
                ->get();

            // Agrupar docentes por materia
            $materiasPorId = [];
            foreach ($materiasConDocentes as $item) {
                $materiaId = $item->idMateria;
                
                if (!isset($materiasPorId[$materiaId])) {
                    $materiasPorId[$materiaId] = [
                        'id' => 'materia_' . $materiaId,
                        'tipo' => 'materia',
                        'materia' => $item->materia_nombre,
                        'puntos' => 0, // Las materias no tienen puntos fijos
                        'descripcion' => 'Materia académica del curso',
                        'docentes' => [],
                    ];
                }

                $apellidos = trim(implode(' ', array_filter([
                    $item->docente_apellido1, 
                    $item->docente_apellido2
                ])));

                $materiasPorId[$materiaId]['docentes'][] = [
                    'nombres' => $item->docente_nombres,
                    'apellidos' => $apellidos,
                    'nombre_completo' => trim($item->docente_nombres . ' ' . $apellidos),
                ];
            }

            $result = array_merge($result, array_values($materiasPorId));
        }

        return $result;
    }

    /**
     * Obtiene depósitos del estudiante con detalles completos del basurero y tipo
     * Como no hay peso/puntos en deposito, calculamos puntos basándose en el tipo de basura
     */
    private function getStudentDepositsWithDetails(User $user, ?int $year = null): SupportCollection
    {
        $query = DB::table('deposito')
            ->join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
            ->join('basurero', 'deposito.idBasurero', '=', 'basurero.idBasurero')
            ->leftJoin('periodos_academicos as pa', 'deposito.idPeriodo', '=', 'pa.idPeriodo')
            ->where('deposito.idUser', $user->id)
            // Excluir depósitos soft-deleted
            ->whereNull('deposito.deleted_at')
            ->select(
                'deposito.idDeposito',
                'deposito.fechaHora',
                'deposito.idPeriodo',
                'deposito.puntos as puntos_deposito',
                'tipoBasura.puntos as puntos_calculados', // Puntos del tipo de basura
                'tipoBasura.idTipoBasura',
                'tipoBasura.nombre as tipo_basura_nombre',
                'tipoBasura.descripcion as tipo_basura_descripcion',
                'tipoBasura.puntos as tipo_basura_puntos',
                'basurero.idBasurero',
                'basurero.descripcion as basurero_nombre',
                'basurero.ubicacion as basurero_ubicacion',
                'basurero.descripcion as basurero_descripcion',
                DB::raw('pa.nombre as periodo_nombre')
            );

        if ($year) {
            $query->whereYear('deposito.fechaHora', $year);
        }

        // Orden: primero por existencia de período (nulos al final), luego por período desc, luego por fecha desc
        $depositos = $query
            ->orderBy('deposito.fechaHora', 'desc')
            ->get();

        // Fallback: si algún depósito no trae nombre de período, calcularlo por fecha
        if ($depositos->isEmpty()) {
            return $depositos;
        }

        $periodos = $this->getPeriodsForYear($year ?? Carbon::now()->year);
        return $depositos->map(function($deposito) use ($periodos) {
            if (empty($deposito->periodo_nombre)) {
                $p = $this->findPeriodForDate(Carbon::parse($deposito->fechaHora), $periodos);
                // Mantener idPeriodo si ya existe; sino, asignar por fecha
                $deposito->idPeriodo = $deposito->idPeriodo ?? $p?->idPeriodo;
                $deposito->periodo_nombre = $p?->nombre ?? 'Sin período';
            }
            return $deposito;
        });
    }

    /**
     * Obtiene los puntos agrupados por período académico
     */
    private function getPointsByPeriod(User $user): array
    {
        $puntajes = $user->puntajes()
            ->with('periodoAcademico')
            ->get();

        $result = [];
        foreach ($puntajes as $puntaje) {
            $result[$puntaje->idPeriodo] = [
                'periodo_id' => $puntaje->idPeriodo,
                'periodo_nombre' => $puntaje->periodoAcademico->nombre ?? 'Sin período',
                'bimestre' => $this->mapBimesterNumber($puntaje->periodoAcademico->nombre ?? null),
                'puntos' => $puntaje->puntos,
                'fecha_asignacion' => $puntaje->fechaAsignacion?->format('Y-m-d H:i:s'),
                'comentario' => $puntaje->comentario,
            ];
        }

        return $result;
    }

    /**
     * Obtiene las notas académicas del estudiante agrupadas por materia con puntos desglosados
     */
    private function getAcademicGrades(User $user, ?PeriodoAcademico $currentPeriod = null): array
    {
        $periodoId = $currentPeriod?->idPeriodo;
        
        // Consulta desde asignaciones_puntaje que es donde está la materia
        $query = DB::table('asignaciones_puntaje as ap')
            ->join('puntaje as p', 'p.idPuntaje', '=', 'ap.idPuntaje')
            ->join('materia as m', 'm.idMateria', '=', 'ap.idMateria')
            ->leftJoin('docente as d', 'd.idDocente', '=', 'ap.idDocente')
            ->leftJoin('usuario as u', 'u.id', '=', 'd.idUser')
            ->where('p.idUser', $user->id);
        
        if ($periodoId) {
            $query->where('p.idPeriodo', $periodoId);
        }
        
        $puntajes = $query->select(
                'ap.idMateria',
                'm.nombre as materia',
                'p.idPeriodo',
                DB::raw("COALESCE(SUM(CASE WHEN p.tipo_puntaje = 'depositos' THEN ap.puntos ELSE 0 END), 0) as puntos_depositos"),
                DB::raw("COALESCE(SUM(CASE WHEN p.tipo_puntaje = 'extracurricular' THEN ap.puntos ELSE 0 END), 0) as puntos_extracurriculares"),
                DB::raw('COALESCE(SUM(ap.puntos), 0) as total'),
                DB::raw("GROUP_CONCAT(DISTINCT CONCAT(IFNULL(u.nombres, ''), ' ', IFNULL(u.primerApellido, ''), ' ', IFNULL(u.segundoApellido, '')) SEPARATOR ', ') as docentes"),
                DB::raw("MAX(ap.fecha_asignacion) as ultima_asignacion")
            )
            ->groupBy('ap.idMateria', 'm.nombre', 'p.idPeriodo')
            ->orderBy('m.nombre')
            ->get();
        
        return $puntajes->map(function ($puntaje) {
            return [
                'idMateria' => $puntaje->idMateria,
                'materia' => $puntaje->materia,
                'puntos_depositos' => (int) $puntaje->puntos_depositos,
                'puntos_extracurriculares' => (int) $puntaje->puntos_extracurriculares,
                'total' => (int) $puntaje->total,
                'docentes' => $puntaje->docentes ?? 'Sin asignación',
                'ultima_fecha' => $puntaje->ultima_asignacion ? 
                    Carbon::parse($puntaje->ultima_asignacion)->format('Y-m-d') : 
                    null,
            ];
        })->toArray();
    }

    /**
     * Obtiene los puntajes desglosados por tipo (depósitos y extracurricular) y materia
     */
    public function obtenerPuntajesPorTipo(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        
        $periodoId = $request->get('periodo_id');
        if (!$periodoId) {
            $periodoActivo = PeriodoAcademico::where('activo', true)->first();
            $periodoId = $periodoActivo?->idPeriodo;
        }

        // Consulta desde asignaciones_puntaje para obtener puntos desglosados por tipo y materia
        $puntajes = DB::table('asignaciones_puntaje as ap')
            ->join('puntaje as p', 'p.idPuntaje', '=', 'ap.idPuntaje')
            ->join('materia as m', 'm.idMateria', '=', 'ap.idMateria')
            ->where('p.idUser', $user->id)
            ->where('p.idPeriodo', $periodoId)
            ->select(
                'ap.idMateria',
                'm.nombre as materia',
                DB::raw("SUM(CASE WHEN p.tipo_puntaje = 'depositos' THEN ap.puntos ELSE 0 END) as puntos_depositos"),
                DB::raw("SUM(CASE WHEN p.tipo_puntaje = 'extracurricular' THEN ap.puntos ELSE 0 END) as puntos_extracurriculares"),
                DB::raw("SUM(ap.puntos) as total")
            )
            ->groupBy('ap.idMateria', 'm.nombre')
            ->orderBy('m.nombre')
            ->get();

        return response()->json([
            'puntajes' => $puntajes,
            'periodo_id' => $periodoId,
        ]);
    }

    /**
     * Obtiene las notas académicas por bimestre
     */
    public function getGradesByBimester(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        
        $student = $this->getStudentData($user);
        $currentPeriod = $this->getCurrentPeriod();
        $year = $currentPeriod?->fecha_inicio?->year ?? Carbon::now()->year;
        
        // Obtener todos los períodos del año
        $periods = $this->getPeriodsForYear($year);
        
        // Obtener notas agrupadas por período
        $gradesByPeriod = [];
        foreach ($periods as $period) {
            $grades = $this->getAcademicGrades($user, $period);
            if (!empty($grades)) {
                $gradesByPeriod[] = [
                    'periodo' => [
                        'id' => $period->idPeriodo,
                        'nombre' => $period->nombre,
                        'bimestre' => $this->mapBimesterNumber($period->nombre)
                    ],
                    'notas' => $grades,
                    'total_puntos' => array_sum(array_column($grades, 'total')),
                    'total_depositos' => array_sum(array_column($grades, 'puntos_depositos')),
                    'total_extracurriculares' => array_sum(array_column($grades, 'puntos_extracurriculares')),
                    'promedio_puntos' => !empty($grades) ? round(array_sum(array_column($grades, 'total')) / count($grades), 2) : 0
                ];
            }
        }
        
        return Inertia::render('Students/AcademicGrades', [
            'student' => $student,
            'currentPeriod' => $currentPeriod ? [
                'id' => $currentPeriod->idPeriodo,
                'nombre' => $currentPeriod->nombre,
            ] : null,
            'gradesByPeriod' => $gradesByPeriod,
            'totalPoints' => $this->getTotalPointsFromPuntaje($user, $year),
        ]);
    }
}