<?php

namespace App\Http\Controllers;

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

        return Inertia::render('Students/Dashboard', [
            'student' => $student,
            'deposits' => $this->formatDepositsForFrontend($deposits->take(10)), // Solo los últimos 10 para el dashboard
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

        return Inertia::render('Students/Profile', [
            'student' => $student,
            'deposits' => $this->formatDepositsForFrontend($deposits, true), // Con bimestre
            'currentPeriod' => $currentPeriod ? [
                'id' => $currentPeriod->id,
                'nombre' => $currentPeriod->nombre,
            ] : null,
            'totalPoints' => $totalPoints,
            'ranking' => $ranking,
        ]);
    }

    public function ranking(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $student = $this->getStudentData($user);
        $currentPeriod = $this->getCurrentPeriod();
        $year = $currentPeriod?->fecha_inicio?->year ?? Carbon::now()->year;

        // Obtener ranking del curso-paralelo
        $ranking = $this->getCourseRanking($user, $year);
        $myPosition = $this->getStudentRanking($user, $year);
        $totalStudents = $ranking->count();

        return Inertia::render('Students/Ranking', [
            'student' => $student,
            'ranking' => $ranking,
            'currentPeriod' => $currentPeriod ? [
                'id' => $currentPeriod->id,
                'nombre' => $currentPeriod->nombre,
            ] : null,
            'myPosition' => $myPosition,
            'totalStudents' => $totalStudents,
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
            ->join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
            ->select('deposito.*', 'tipoBasura.puntos')
            ->orderBy('fechaHora', 'desc');

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
                    'cantidad' => 0, // No hay campo peso en deposito
                    'puntaje_obtenido' => (int) ($deposito->puntos_calculados ?? 0),
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
                    'puntaje_obtenido' => (int) $deposito->puntos,
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
    private function getStudentRanking(User $user, ?int $year = null): int
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

        if ($year) {
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
    private function getCourseRanking(User $user, ?int $year = null)
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

        if ($year) {
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
            ->orderBy('nombre')
            ->get();

        foreach ($tiposBasura as $tipo) {
            $result[] = [
                'id' => 'tipo_' . $tipo->idTipoBasura,
                'tipo' => 'tipo_basura',
                'materia' => $tipo->nombre,
                'puntos' => (int) $tipo->puntos,
                'descripcion' => $tipo->descripcion,
                'docentes' => [], // Los tipos de basura no tienen docentes asignados directamente
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
            ->where('deposito.idUser', $user->id)
            ->select(
                'deposito.idDeposito',
                'deposito.fechaHora',
                'tipoBasura.puntos as puntos_calculados', // Puntos del tipo de basura
                'tipoBasura.idTipoBasura',
                'tipoBasura.nombre as tipo_basura_nombre',
                'tipoBasura.descripcion as tipo_basura_descripcion',
                'tipoBasura.puntos as tipo_basura_puntos',
                'basurero.idBasurero',
                'basurero.descripcion as basurero_nombre',
                'basurero.ubicacion as basurero_ubicacion',
                'basurero.descripcion as basurero_descripcion'
            );

        if ($year) {
            $query->whereYear('deposito.fechaHora', $year);
        }

        $depositos = $query->orderBy('deposito.fechaHora', 'desc')->get();
        
        // Obtener períodos para determinar a cuál pertenece cada depósito
        $periodos = $this->getPeriodsForYear($year ?? Carbon::now()->year);
        
        // Agregar información del período a cada depósito
        return $depositos->map(function($deposito) use ($periodos) {
            $periodo = $this->findPeriodForDate(Carbon::parse($deposito->fechaHora), $periodos);
            $deposito->idPeriodo = $periodo?->idPeriodo;
            $deposito->periodo_nombre = $periodo?->nombre ?? 'Sin período';
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
                'puntos' => $puntaje->puntos,
                'fecha_asignacion' => $puntaje->fechaAsignacion?->format('Y-m-d H:i:s'),
                'comentario' => $puntaje->comentario,
            ];
        }

        return $result;
    }
}