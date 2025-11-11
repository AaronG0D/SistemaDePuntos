<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Curso;
use App\Models\Paralelo;
use App\Models\Deposito;
use App\Models\TipoBasura;
use App\Models\Basurero;
use App\Models\User;
use App\Models\Puntaje;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class WelcomeController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Obtener los top 6 estudiantes con más puntos usando consulta directa
            $baseQuery = DB::table('estudiante as e')
                ->join('usuario as u', 'e.idUser', '=', 'u.id')
                ->leftJoin('curso_paralelo as cp', 'e.idCursoParalelo', '=', 'cp.idCursoParalelo')
                ->leftJoin('curso as c', 'cp.idCurso', '=', 'c.idCurso')
                ->leftJoin('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
                ->leftJoin('puntaje as pt', 'u.id', '=', 'pt.idUser')
                ->where('u.rol', 'estudiante')
                ->whereNull('u.deleted_at')
                ->whereNull('e.deleted_at')
                ->select(
                    'u.id as idUser',
                    'u.nombres',
                    'u.primerApellido',
                    'u.segundoApellido',
                    'u.email',
                    'c.nombre as curso_nombre',
                    'p.nombre as paralelo_nombre',
                    'cp.idCursoParalelo',
                    DB::raw('COALESCE(SUM(pt.puntos), 0) as total_puntos')
                )
                ->groupBy('u.id', 'u.nombres', 'u.primerApellido', 'u.segundoApellido', 'u.email', 'c.nombre', 'p.nombre', 'cp.idCursoParalelo')
                ->orderBy('total_puntos', 'desc')
                ->limit(6);

            // Aplicar filtros si existen
            $filtered = (clone $baseQuery)
                ->when($request->filled('curso') && $request->input('curso') !== 'all', function($query) use ($request) {
                    return $query->where('c.idCurso', $request->input('curso'));
                })
                ->when($request->filled('paralelo') && $request->input('paralelo') !== 'all', function($query) use ($request) {
                    return $query->where('p.idParalelo', $request->input('paralelo'));
                })
                ->get();

            // Si con filtros no hay resultados, caer al top global (sin filtros)
            $rows = $filtered->isEmpty() ? $baseQuery->get() : $filtered;

            $topEstudiantes = $rows
                ->map(function($estudiante) {
                    return (object) [
                        'idUser' => $estudiante->idUser,
                        'user' => (object) [
                            'nombres' => $estudiante->nombres,
                            'primerApellido' => $estudiante->primerApellido,
                            'segundoApellido' => $estudiante->segundoApellido,
                            'email' => $estudiante->email,
                            'puntaje' => (object) [
                                'puntajeTotal' => $estudiante->total_puntos
                            ]
                        ],
                        'curso_paralelo' => (object) [
                            'idCursoParalelo' => $estudiante->idCursoParalelo,
                            'curso' => (object) [
                                'nombre' => $estudiante->curso_nombre ?: 'Sin curso'
                            ],
                            'paralelo' => (object) [
                                'nombre' => $estudiante->paralelo_nombre ?: 'Sin paralelo'
                            ]
                        ]
                    ];
                });

            // Debug detallado de los resultados
            \Log::info('Top estudiantes obtenidos:', [
                'count' => $topEstudiantes->count(),
                'estudiantes' => $topEstudiantes->map(function($estudiante) {
                    return [
                        'nombre' => $estudiante->user->nombres ?? 'Sin nombre',
                        'apellido' => $estudiante->user->primerApellido ?? 'Sin apellido',
                        'curso' => $estudiante->cursoParalelo->curso->nombre ?? 'Sin curso',
                        'paralelo' => $estudiante->cursoParalelo->paralelo->nombre ?? 'Sin paralelo',
                        'puntos' => $estudiante->user->puntaje->puntajeTotal ?? 0
                    ];
                })->toArray()
            ]);

            // Debug detallado
            \Log::info('Top estudiantes:', [
                'count' => $topEstudiantes->count(),
                'estudiantes' => $topEstudiantes->map(function($estudiante) {
                    return [
                        'nombre' => $estudiante->user->nombres ?? 'Sin nombre',
                        'apellido' => $estudiante->user->primerApellido ?? 'Sin apellido',
                        'curso' => $estudiante->cursoParalelo->curso->nombre ?? 'Sin curso',
                        'paralelo' => $estudiante->cursoParalelo->paralelo->nombre ?? 'Sin paralelo',
                        'puntos' => $estudiante->user->puntaje->puntajeTotal ?? 0
                    ];
                })->toArray()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error obteniendo top estudiantes: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            $topEstudiantes = collect([]); // Devolver colección vacía en caso de error
        }

        try {
            // Obtener todos los cursos y paralelos con debug
            $cursos = Curso::orderBy('nombre')->get(['idCurso', 'nombre']);
            $paralelos = Paralelo::orderBy('nombre')->get(['idParalelo', 'nombre']);
            
            \Log::info('Datos a enviar a la vista:', [
                'cursos' => [
                    'count' => $cursos->count(),
                    'data' => $cursos->toArray()
                ],
                'paralelos' => [
                    'count' => $paralelos->count(),
                    'data' => $paralelos->toArray()
                ],
                'estudiantes' => [
                    'count' => $topEstudiantes->count(),
                    'data' => $topEstudiantes->toArray()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error obteniendo cursos/paralelos: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            $cursos = collect([]);
            $paralelos = collect([]);
        }

        // Obtener estadísticas generales del sistema
        $estadisticas = [
            'totalEstudiantes' => User::where('rol', 'estudiante')->whereNull('deleted_at')->count(),
            'totalDepositos' => Deposito::count(),
            'totalPuntos' => Puntaje::sum('puntos') ?? 0,
            'totalBasureros' => Basurero::where('estado', 'activo')->count(),
            'tiposBasura' => TipoBasura::where('estado', 'activo')->count(),
            'depositosHoy' => Deposito::whereDate('fechaHora', today())->count(),
            'puntosHoy' => Puntaje::whereDate('fechaAsignacion', today())->sum('puntos') ?? 0,
            'cursoMasActivo' => $this->getCursoMasActivo(),
            'tipoBasuraMasComun' => $this->getTipoBasuraMasComun()
        ];
        
        \Log::info('Estadísticas calculadas:', $estadisticas);

        // Verifica que los datos no sean null antes de enviarlos
        $data = [
            'topEstudiantes' => $topEstudiantes ?? collect([]),
            'cursos' => $cursos ?? collect([]),
            'paralelos' => $paralelos ?? collect([]),
            'estadisticas' => $estadisticas
        ];

        // Log final de los datos que se envían
        \Log::info('Enviando datos a la vista:', $data);
        
        return Inertia::render('Welcome', $data);
    }

    private function getCursoMasActivo()
    {
        try {
            return DB::table('deposito')
                ->join('usuario', 'deposito.idUser', '=', 'usuario.id')
                ->join('estudiante', 'usuario.id', '=', 'estudiante.idUser')
                ->join('curso_paralelo', 'estudiante.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
                ->join('curso', 'curso_paralelo.idCurso', '=', 'curso.idCurso')
                ->join('paralelo', 'curso_paralelo.idParalelo', '=', 'paralelo.idParalelo')
                ->select(
                    'curso.nombre as curso_nombre',
                    'paralelo.nombre as paralelo_nombre',
                    DB::raw('COUNT(deposito.idDeposito) as total_depositos')
                )
                ->groupBy('curso.idCurso', 'paralelo.idParalelo', 'curso.nombre', 'paralelo.nombre')
                ->orderBy('total_depositos', 'desc')
                ->first();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo curso más activo: ' . $e->getMessage());
            return null;
        }
    }

    private function getTipoBasuraMasComun()
    {
        try {
            return DB::table('deposito')
                ->join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
                ->select(
                    'tipoBasura.nombre',
                    'tipoBasura.descripcion',
                    DB::raw('COUNT(deposito.idDeposito) as total_depositos')
                )
                ->groupBy('tipoBasura.idTipoBasura', 'tipoBasura.nombre', 'tipoBasura.descripcion')
                ->orderBy('total_depositos', 'desc')
                ->first();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo tipo de basura más común: ' . $e->getMessage());
            return null;
        }
    }
}
