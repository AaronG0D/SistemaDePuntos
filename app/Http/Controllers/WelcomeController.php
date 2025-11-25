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
            // Obtener los top 6 estudiantes ordenados por puntaje de tipo 'depositos'
            $topEstudiantes = Estudiante::with(['user', 'cursoParalelo.curso', 'cursoParalelo.paralelo'])
                ->whereNull('estudiante.deleted_at')
                ->orderByPuntaje('desc', 'depositos')
                ->limit(6)
                ->get()
                ->map(function ($estudiante) {
                    return (object) [
                        'idUser' => $estudiante->idUser,
                        'user' => (object) [
                            'nombres' => $estudiante->user->nombres,
                            'primerApellido' => $estudiante->user->primerApellido,
                            'segundoApellido' => $estudiante->user->segundoApellido,
                            'email' => $estudiante->user->email,
                            'puntaje' => (object) [
                                'puntajeTotal' => (int) $estudiante->total_puntos
                            ]
                        ],
                        'curso_paralelo' => (object) [
                            'idCursoParalelo' => $estudiante->idCursoParalelo,
                            'curso' => (object) [
                                'nombre' => $estudiante->cursoParalelo->curso->nombre ?? 'Sin curso'
                            ],
                            'paralelo' => (object) [
                                'nombre' => $estudiante->cursoParalelo->paralelo->nombre ?? 'Sin paralelo'
                            ]
                        ]
                    ];
                });

            \Log::info('Top estudiantes obtenidos:', [
                'count' => $topEstudiantes->count(),
                'estudiantes' => $topEstudiantes->map(fn($e) => [
                    'nombre' => $e->user->nombres,
                    'apellido' => $e->user->primerApellido,
                    'puntos' => $e->user->puntaje->puntajeTotal
                ])->toArray()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error obteniendo top estudiantes: ' . $e->getMessage());
            \Log::error('Stack: ' . $e->getTraceAsString());
            $topEstudiantes = collect([]);
        }

        try {
            $cursos = Curso::orderBy('nombre')->get(['idCurso', 'nombre']);
            $paralelos = Paralelo::orderBy('nombre')->get(['idParalelo', 'nombre']);
        } catch (\Exception $e) {
            \Log::error('Error obteniendo cursos/paralelos: ' . $e->getMessage());
            $cursos = collect([]);
            $paralelos = collect([]);
        }

        // Obtener estadísticas generales del sistema
        $estadisticas = [
            'totalEstudiantes' => User::where('rol', 'estudiante')->whereNull('deleted_at')->count(),
            'totalDepositos' => Deposito::count(),
            'totalPuntos' => Deposito::sum('puntos') ?? 0,
            'totalBasureros' => Basurero::where('estado', 'activo')->count(),
            'tiposBasura' => TipoBasura::where('estado', 'activo')->count(),
            'depositosHoy' => Deposito::whereDate('fechaHora', today())->count(),
            'puntosHoy' => Deposito::whereDate('fechaHora', today())->sum('puntos') ?? 0,
            'cursoMasActivo' => $this->getCursoMasActivo(),
            'tipoBasuraMasComun' => $this->getTipoBasuraMasComun()
        ];

        $data = [
            'topEstudiantes' => $topEstudiantes ?? collect([]),
            'cursos' => $cursos ?? collect([]),
            'paralelos' => $paralelos ?? collect([]),
            'estadisticas' => $estadisticas
        ];

        return Inertia::render('Welcome', $data);
    }

    private function getCursoMasActivo()
    {
        try {
            return Deposito::with(['user.estudiante.cursoParalelo.curso', 'user.estudiante.cursoParalelo.paralelo'])
                ->get()
                ->groupBy(fn($d) => $d->user->estudiante->cursoParalelo->idCursoParalelo ?? 'sin-curso')
                ->map(function ($group, $key) {
                    if ($key === 'sin-curso') return null;
                    
                    $first = $group->first();
                    return (object) [
                        'curso_nombre' => $first->user->estudiante->cursoParalelo->curso->nombre,
                        'paralelo_nombre' => $first->user->estudiante->cursoParalelo->paralelo->nombre,
                        'total_depositos' => $group->count()
                    ];
                })
                ->whereNotNull()
                ->sortByDesc('total_depositos')
                ->first();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo curso más activo: ' . $e->getMessage());
            return null;
        }
    }

    private function getTipoBasuraMasComun()
    {
        try {
            return Deposito::with('tipoBasura')
                ->get()
                ->groupBy('idTipoBasura')
                ->map(function ($group) {
                    $first = $group->first();
                    return (object) [
                        'nombre' => $first->tipoBasura->nombre,
                        'descripcion' => $first->tipoBasura->descripcion,
                        'total_depositos' => $group->count()
                    ];
                })
                ->sortByDesc('total_depositos')
                ->first();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo tipo de basura más común: ' . $e->getMessage());
            return null;
        }
    }
}

