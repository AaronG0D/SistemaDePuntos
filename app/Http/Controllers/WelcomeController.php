<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Curso;
use App\Models\Paralelo;
use App\Models\Deposito;
use App\Models\TipoBasura;
use App\Models\Basurero;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class WelcomeController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Construir la consulta base con eager loading explícito
            $query = Estudiante::with([
                'user.puntaje',
                'cursoParalelo.curso',
                'cursoParalelo.paralelo'
            ]);

            // Aplicar filtros si están presentes
            if ($request->filled('curso') && $request->input('curso') !== 'all') {
                $query->whereHas('cursoParalelo', function($q) use ($request) {
                    $q->where('idCurso', $request->input('curso'));
                });
            }

            if ($request->filled('paralelo') && $request->input('paralelo') !== 'all') {
                $query->whereHas('cursoParalelo', function($q) use ($request) {
                    $q->where('idParalelo', $request->input('paralelo'));
                });
            }

            // Obtener los top 6 estudiantes con más puntos usando scopes del modelo
            $query = $query->rolEstudiante()
                           ->filterCurso($request->input('curso'))
                           ->filterParalelo($request->input('paralelo'))
                           ->orderByPuntaje('desc');

            // Debug de la consulta
            \Log::info('Query SQL:', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);

            $topEstudiantes = $query->take(6)->get();

            // Debug detallado de los resultados
            foreach ($topEstudiantes as $estudiante) {
                \Log::info('Datos del estudiante:', [
                    'id' => $estudiante->idUser,
                    'nombre' => $estudiante->user->nombres ?? 'Sin nombre',
                    'apellido' => $estudiante->user->primerApellido ?? 'Sin apellido',
                    'idCursoParalelo' => $estudiante->idCursoParalelo,
                    'cursoParalelo' => [
                        'id' => $estudiante->cursoParalelo->idCursoParalelo ?? 'No tiene',
                        'curso' => [
                            'id' => $estudiante->cursoParalelo->curso->idCurso ?? 'No tiene',
                            'nombre' => $estudiante->cursoParalelo->curso->nombre ?? 'Sin curso'
                        ],
                        'paralelo' => [
                            'id' => $estudiante->cursoParalelo->paralelo->idParalelo ?? 'No tiene',
                            'nombre' => $estudiante->cursoParalelo->paralelo->nombre ?? 'Sin paralelo'
                        ]
                    ],
                    'puntaje' => $estudiante->user->puntaje->puntajeTotal ?? 0
                ]);
            }

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
        try {
            $estadisticas = [
                'totalEstudiantes' => User::where('rol', 'estudiante')->count(),
                'totalDepositos' => Deposito::count(),
                'totalPuntos' => DB::table('puntaje')->sum('puntos'),
                'totalBasureros' => Basurero::where('estado', 'activo')->count(),
                'tiposBasura' => TipoBasura::where('estado', 'activo')->count(),
                'depositosHoy' => Deposito::whereDate('fechaDeposito', today())->count(),
                'puntosHoy' => Deposito::whereDate('fechaDeposito', today())->sum('puntosAsignados'),
                'cursoMasActivo' => $this->getCursoMasActivo(),
                'tipoBasuraMasComun' => $this->getTipoBasuraMasComun()
            ];
        } catch (\Exception $e) {
            \Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            $estadisticas = [
                'totalEstudiantes' => 0,
                'totalDepositos' => 0,
                'totalPuntos' => 0,
                'totalBasureros' => 0,
                'tiposBasura' => 0,
                'depositosHoy' => 0,
                'puntosHoy' => 0,
                'cursoMasActivo' => null,
                'tipoBasuraMasComun' => null
            ];
        }

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
                    DB::raw('COUNT(deposito.idDeposito) as total_depositos'),
                    DB::raw('SUM(deposito.puntosAsignados) as total_puntos')
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
                    DB::raw('COUNT(deposito.idDeposito) as total_depositos'),
                    DB::raw('SUM(deposito.puntosAsignados) as total_puntos')
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
