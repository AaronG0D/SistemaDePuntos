<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Curso;
use App\Models\Paralelo;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

        // Verifica que los datos no sean null antes de enviarlos
        $data = [
            'topEstudiantes' => $topEstudiantes ?? collect([]),
            'cursos' => $cursos ?? collect([]),
            'paralelos' => $paralelos ?? collect([])
        ];

        // Log final de los datos que se envían
        \Log::info('Enviando datos a la vista:', $data);
        
        return Inertia::render('Welcome', $data);
    }
}
