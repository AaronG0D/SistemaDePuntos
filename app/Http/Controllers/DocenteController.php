<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Materia;
use App\Models\Curso;
use App\Models\Paralelo;
use App\Models\CursoParalelo;
use App\Models\MateriaCursoParalelo;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Exports\PlantillaEstudiantesExport;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Imports\EstudiantesImport;
use Maatwebsite\Excel\Facades\Excel;

class DocenteController extends Controller
{
    public function index(Request $request)
    {
        $query = Docente::with([
            'user',
            'docenteMateriaCursos.materia',
            'docenteMateriaCursos.cursoParalelo.curso',
            'docenteMateriaCursos.cursoParalelo.paralelo'
        ]);

        // Filtro por búsqueda
        if ($request->filled('search') && trim($request->input('search')) !== '') {
            $search = trim($request->input('search'));
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('primerApellido', 'like', "%{$search}%")
                  ->orWhere('segundoApellido', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtro por materia
        if ($request->filled('materia') && $request->input('materia') !== 'all' && $request->input('materia') !== null) {
            $query->whereHas('docenteMateriaCursos.materia', function ($q) use ($request) {
                $q->where('idMateria', $request->input('materia'));
            });
        }

        // Filtro por curso
        if ($request->filled('curso') && $request->input('curso') !== 'all' && $request->input('curso') !== null) {
            $query->whereHas('docenteMateriaCursos.cursoParalelo.curso', function ($q) use ($request) {
                $q->where('idCurso', $request->input('curso'));
            });
        }

        $docentes = $query->paginate(10);

        // Trae todos los datos necesarios para los filtros
        $materias = Materia::orderBy('nombre')->get(['idMateria', 'nombre']);
        $cursos = Curso::orderBy('nombre')->get(['idCurso', 'nombre']);
        $paralelos = Paralelo::orderBy('nombre')->get(['idParalelo', 'nombre']);

        return Inertia::render('admin/DocentesLIST', [
            'docentes' => $docentes,
            'materias' => $materias,
            'cursos' => $cursos,
            'paralelos' => $paralelos,
        ]);
    }

    public function show($id)
    {
        try {
            $docente = Docente::with([
                'user',
                'docenteMateriaCursos.materia',
                'docenteMateriaCursos.cursoParalelo.curso',
                'docenteMateriaCursos.cursoParalelo.paralelo'
            ])->findOrFail($id);

            return Inertia::render('admin/DocenteView', [
                'docente' => $docente,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error mostrando docente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al mostrar el docente');
        }
    }

    public function edit($id)
    {
        try {
            $docente = Docente::with([
                'user',
                'docenteMateriaCursos.materia',
                'docenteMateriaCursos.cursoParalelo.curso',
                'docenteMateriaCursos.cursoParalelo.paralelo'
            ])->findOrFail($id);

            // Trae todos los datos necesarios para el formulario
            $materias = Materia::orderBy('nombre')->get(['idMateria', 'nombre']);
            $cursos = Curso::orderBy('nombre')->get(['idCurso', 'nombre']);
            $paralelos = Paralelo::orderBy('nombre')->get(['idParalelo', 'nombre']);
            $cursoParalelos = CursoParalelo::with(['curso', 'paralelo', 'materias'])->orderBy('idCursoParalelo')->get();

            // Obtener las materias disponibles por curso
            $materiasPorCurso = $this->getMateriasPorCurso();

            // NUEVO: Obtener las materias disponibles por curso-paralelo
            $materiasPorCursoParalelo = [];
            foreach ($cursoParalelos as $cp) {
                $materiasPorCursoParalelo[$cp->idCursoParalelo] = $cp->materias->map(function($m) {
                    return [
                        'idMateria' => $m->idMateria,
                        'nombre' => $m->nombre
                    ];
                });
            }

            return Inertia::render('admin/DocenteEdit', [
                'docente' => $docente,
                'materias' => $materias,
                'cursos' => $cursos,
                'paralelos' => $paralelos,
                'cursoParalelos' => $cursoParalelos,
                'materiasPorCurso' => $materiasPorCurso,
                'materiasPorCursoParalelo' => $materiasPorCursoParalelo, // NUEVO
            ]);
        } catch (\Exception $e) {
            \Log::error('Error editando docente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al editar el docente');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $docente = Docente::findOrFail($id);

            // Validar datos del usuario
            $request->validate([
                'user.nombres' => 'required|string|max:100',
                'user.primerApellido' => 'required|string|max:100',
                'user.segundoApellido' => 'nullable|string|max:100',
                'user.email' => 'required|email|max:100|unique:usuario,email,' . $docente->user->id,
                'materias_cursos' => 'nullable|array',
                'materias_cursos.*.idMateria' => 'required|exists:materia,idMateria',
                'materias_cursos.*.idCursoParalelo' => 'required|exists:curso_paralelo,idCursoParalelo',
            ]);

            // Actualiza los datos del usuario
            $docente->user->update($request->input('user'));

            // Actualiza las materias y cursos del docente
            if ($request->has('materias_cursos')) {
                // Eliminar asignaciones existentes
                $docente->docenteMateriaCursos()->delete();

                // Crear nuevas asignaciones
                foreach ($request->input('materias_cursos') as $asignacion) {
                    $docente->docenteMateriaCursos()->create([
                        'idMateria' => $asignacion['idMateria'],
                        'idCursoParalelo' => $asignacion['idCursoParalelo']
                    ]);
                }
            } else {
                // Si no se envían asignaciones, eliminar todas las existentes
                $docente->docenteMateriaCursos()->delete();
            }

            return redirect()->back()->with('success', 'Docente actualizado correctamente');
        } catch (\Exception $e) {
            \Log::error('Error actualizando docente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el docente: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $docente = Docente::findOrFail($id);
            
      
        
            
            // Eliminar el docente
            $docente->delete();

            return redirect()->back()->with('success', 'Docente eliminado correctamente');
        } catch (\Exception $e) {
            \Log::error('Error eliminando docente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar el docente');
        }
    }

    /**
     * Obtiene las materias disponibles por curso
     */
    private function getMateriasPorCurso()
    {
        $materiasPorCurso = [];
        
        $cursos = Curso::with(['cursoParalelos.materias'])->get();
        
        foreach ($cursos as $curso) {
            $materiasDelCurso = collect();
            
            foreach ($curso->cursoParalelos as $cursoParalelo) {
                $materiasDelCurso = $materiasDelCurso->merge($cursoParalelo->materias);
            }
            
            // Eliminar duplicados y ordenar
            $materiasPorCurso[$curso->idCurso] = $materiasDelCurso
                ->unique('idMateria')
                ->sortBy('nombre')
                ->values();
        }
        
        return $materiasPorCurso;
    }

    /**
     * API endpoint para obtener materias por curso y paralelo
     */
    public function getMateriasByCurso(Request $request)
    {
        $cursoId = $request->input('curso_id');
        $paraleloId = $request->input('paralelo_id');
        $docenteId = $request->input('docente_id'); // Para excluir las asignaciones del docente actual
        
        if (!$cursoId) {
            return response()->json(['error' => 'ID de curso requerido'], 400);
        }
        
        $query = Materia::whereHas('materiaCursoParalelos', function ($query) use ($cursoId) {
            $query->whereHas('cursoParalelo', function ($q) use ($cursoId) {
                $q->where('idCurso', $cursoId);
            });
        });
        
        // Si también se especifica el paralelo, filtrar por él
        if ($paraleloId) {
            $query->whereHas('materiaCursoParalelos', function ($query) use ($cursoId, $paraleloId) {
                $query->whereHas('cursoParalelo', function ($q) use ($cursoId, $paraleloId) {
                    $q->where('idCurso', $cursoId)
                      ->where('idParalelo', $paraleloId);
                });
            });
        }
        
        $materias = $query->orderBy('nombre')->get(['idMateria', 'nombre']);
        
        // Si se especifica un docente, excluir las materias ya asignadas a otros docentes
        if ($docenteId && $paraleloId) {
            $materiasAsignadas = DB::table('docente_materia_curso')
                ->join('curso_paralelo', 'docente_materia_curso.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
                ->where('curso_paralelo.idCurso', $cursoId)
                ->where('curso_paralelo.idParalelo', $paraleloId)
                ->where('docente_materia_curso.idDocente', '!=', $docenteId)
                ->pluck('docente_materia_curso.idMateria')
                ->toArray();
            
            $materias = $materias->whereNotIn('idMateria', $materiasAsignadas);
        }
        
        return response()->json($materias);
    }

    /**
     * Verifica si hay conflictos al asignar una materia a un docente
     */
    public function verificarConflictos(Request $request)
    {
        $request->validate([
            'idDocente' => 'required|exists:docente,idDocente',
            'idMateria' => 'required|exists:materia,idMateria',
            'idCursoParalelo' => 'required|exists:curso_paralelo,idCursoParalelo',
        ]);

        $idDocente = $request->input('idDocente');
        $idMateria = $request->input('idMateria');
        $idCursoParalelo = $request->input('idCursoParalelo');

        // Verificar si ya existe una asignación para esta materia y curso-paralelo
        $conflicto = DB::table('docente_materia_curso')
            ->where('idMateria', $idMateria)
            ->where('idCursoParalelo', $idCursoParalelo)
            ->where('idDocente', '!=', $idDocente)
            ->exists();

        if ($conflicto) {
            // Obtener información del conflicto
            $docenteConflicto = DB::table('docente_materia_curso')
                ->join('docente', 'docente_materia_curso.idDocente', '=', 'docente.idDocente')
                ->join('usuario', 'docente.idUser', '=', 'usuario.id')
                ->join('materia', 'docente_materia_curso.idMateria', '=', 'materia.idMateria')
                ->join('curso_paralelo', 'docente_materia_curso.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
                ->join('curso', 'curso_paralelo.idCurso', '=', 'curso.idCurso')
                ->join('paralelo', 'curso_paralelo.idParalelo', '=', 'paralelo.idParalelo')
                ->where('docente_materia_curso.idMateria', $idMateria)
                ->where('docente_materia_curso.idCursoParalelo', $idCursoParalelo)
                ->where('docente_materia_curso.idDocente', '!=', $idDocente)
                ->select(
                    'usuario.nombres',
                    'usuario.primerApellido',
                    'materia.nombre as materia',
                    'curso.nombre as curso',
                    'paralelo.nombre as paralelo'
                )
                ->first();

            return response()->json([
                'conflicto' => true,
                'mensaje' => "Ya existe una asignación para esta materia en este curso-paralelo",
                'detalles' => $docenteConflicto
            ]);
        }

        return response()->json([
            'conflicto' => false,
            'mensaje' => "No hay conflictos"
        ]);
    }

    /**
     * Obtiene las materias disponibles para un curso-paralelo específico
     */
    public function getMateriasDisponibles(Request $request)
    {
        $idCurso = $request->input('idCurso');
        $idParalelo = $request->input('idParalelo');
        $idDocente = $request->input('idDocente'); // Opcional, para edición

        if (!$idCurso || !$idParalelo) {
            return response()->json(['error' => 'Curso y paralelo son requeridos'], 400);
        }

        // Buscar o crear el curso-paralelo
        $cursoParalelo = CursoParalelo::where('idCurso', $idCurso)
            ->where('idParalelo', $idParalelo)
            ->with(['curso', 'paralelo'])
            ->first();

        if (!$cursoParalelo) {
            return response()->json(['error' => 'Combinación curso-paralelo no encontrada'], 404);
        }

        // Obtener todas las materias disponibles para este curso-paralelo
        $materiasDisponibles = MateriaCursoParalelo::where('idCursoParalelo', $cursoParalelo->idCursoParalelo)
            ->with('materia')
            ->get()
            ->pluck('materia');

        // Filtrar las materias que ya están asignadas a otros docentes
        $materiasAsignadas = DB::table('docente_materia_curso')
            ->where('idCursoParalelo', $cursoParalelo->idCursoParalelo);

        // Si estamos editando, excluir las materias del docente actual
        if ($idDocente) {
            $materiasAsignadas = $materiasAsignadas->where('idDocente', '!=', $idDocente);
        }
        
        $materiasAsignadasIds = $materiasAsignadas->pluck('idMateria')->toArray();
        $materiasDisponibles = $materiasDisponibles->whereNotIn('idMateria', $materiasAsignadasIds);

        return response()->json([
            'cursoParalelo' => $cursoParalelo,
            'materiasDisponibles' => $materiasDisponibles->values()
        ]);
    }

   public function create()
{
    // Usuarios con rol docente que no tienen registro en docentes
    $usuariosDisponibles = User::where('rol', 'docente')
        ->whereDoesntHave('docente')
        ->get(['id', 'nombres', 'primerApellido', 'segundoApellido', 'email']);

    // Eager loading optimizado: curso → paralelos → materias
    $cursos = Curso::with([
        'cursoParalelos.paralelo',
        'cursoParalelos.materias'
    ])
    ->orderBy('nombre')
    ->get();

    // Preparar estructura para Vue: cursos con sus paralelos y materias
    $cursosEstructura = $cursos->map(function ($curso) {
        return [
            'idCurso' => $curso->idCurso,
            'nombre' => $curso->nombre,
            'paralelos' => $curso->cursoParalelos->map(function ($cp) {
                return [
                    'idParalelo' => $cp->paralelo->idParalelo,
                    'nombre' => $cp->paralelo->nombre,
                    'materias' => $cp->materias->map(function ($materia) {
                        return [
                            'idMateria' => $materia->idMateria,
                            'nombre' => $materia->nombre
                        ];
                    })
                ];
            })->values()
        ];
    });


    // Obtener todas las materias, paralelos y curso-paralelos
    $materias = Materia::orderBy('nombre')->get(['idMateria', 'nombre']);
    $paralelos = Paralelo::orderBy('nombre')->get(['idParalelo', 'nombre']);
    $cursoParalelos = CursoParalelo::with(['curso', 'paralelo'])->get();


    return Inertia::render('admin/Docentes/Create', [
        'usuariosDisponibles' => $usuariosDisponibles,
        'cursos' => $cursosEstructura,
        'materias' => $materias,
        'paralelos' => $paralelos,
        'cursoParalelos' => $cursoParalelos
    ]);
}

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validar datos
            $request->validate([
                'idUser' => 'required|exists:usuario,id',
                'materias_cursos' => 'required|array|min:1',
                'materias_cursos.*.idMateria' => 'required|exists:materia,idMateria',
                'materias_cursos.*.idCurso' => 'required|exists:curso,idCurso',
                'materias_cursos.*.idParalelo' => 'required|exists:paralelo,idParalelo',
            ]);

            // Crear docente con el usuario seleccionado
            $docente = Docente::create([
                'idUser' => $request->idUser,
            ]);

            // Asignar materias y cursos
            foreach ($request->materias_cursos as $asignacion) {
                $cursoParalelo = CursoParalelo::firstOrCreate([
                    'idCurso' => $asignacion['idCurso'],
                    'idParalelo' => $asignacion['idParalelo']
                ]);

                $docente->docenteMateriaCursos()->create([
                    'idMateria' => $asignacion['idMateria'],
                    'idCursoParalelo' => $cursoParalelo->idCursoParalelo
                ]);
            }

            DB::commit();

            return redirect()->route('admin.docentes')
                ->with('success', 'Docente creado correctamente');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creando docente: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al crear el docente')
                ->withInput();
        }
    }

    public function descargarPlantillaEstudiantes(Request $request)
    {
        try {
            $cursoParaleloId = $request->query('curso_paralelo_id');
            $cursoParalelo = CursoParalelo::with(['curso', 'paralelo'])
                ->findOrFail($cursoParaleloId);

            $plantilla = new PlantillaEstudiantesExport([$cursoParalelo]);
            $spreadsheet = $plantilla->generateTemplate();

            // Crear un nombre de archivo único
            $filename = sprintf(
                'plantilla_estudiantes_%s_%s_%s.xlsx',
                \Str::slug($cursoParalelo->curso->nombre),
                \Str::slug($cursoParalelo->paralelo->nombre),
                date('Y-m-d_His')
            );

            // Crear archivo temporal
            $tempFile = tempnam(sys_get_temp_dir(), 'plantilla_');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tempFile);

            // Devolver el archivo como descarga
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend();

        } catch (\Exception $e) {
            \Log::error('Error al generar plantilla:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Error al generar la plantilla: ' . $e->getMessage());
        }
    }

    public function importarEstudiantes(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls',
                'curso_paralelo_id' => 'required|exists:curso_paralelo,idCursoParalelo'
            ]);

            Excel::import(
                new EstudiantesImport($request->curso_paralelo_id), 
                $request->file('file')
            );

            return response()->json(['message' => 'Estudiantes importados exitosamente']);
        } catch (\Exception $e) {
            \Log::error('Error importando estudiantes:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(
                ['error' => 'Error al importar estudiantes: ' . $e->getMessage()],
                500
            );
        }
    }
}