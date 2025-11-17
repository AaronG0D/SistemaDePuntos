<?php


namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\User;
use \App\Models\Deposito;
use App\Models\HistorialImportacion;
use App\Imports\EstudiantesImport;
use App\Exports\PlantillaEstudiantesExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EstudianteController extends Controller
{


    public function index(Request $request)
    {
        // Debug temporal
        \Log::info('Parámetros recibidos:', $request->all());

        $query = Estudiante::with([
            'user.puntajes',
            'cursoParalelo.curso',
            'cursoParalelo.paralelo'
        ])->whereNull('deleted_at'); // Solo estudiantes activos
                
        // Filtro por búsqueda
        if ($request->filled('search') && trim($request->input('search')) !== '') {
            $search = trim($request->input('search'));
            \Log::info('Aplicando filtro de búsqueda:', ['search' => $search]);
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('primerApellido', 'like', "%{$search}%")
                  ->orWhere('segundoApellido', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  // Búsqueda por nombre completo: nombres + apellidos
                  ->orWhereRaw(
                      "CONCAT(TRIM(IFNULL(nombres,'')), ' ', TRIM(IFNULL(primerApellido,'')), ' ', TRIM(IFNULL(segundoApellido,''))) LIKE ?",
                      ["%{$search}%"]
                  )
                  // También considerar formato común: apellidos primero
                  ->orWhereRaw(
                      "CONCAT(TRIM(IFNULL(primerApellido,'')), ' ', TRIM(IFNULL(segundoApellido,'')), ' ', TRIM(IFNULL(nombres,''))) LIKE ?",
                      ["%{$search}%"]
                  );
            });
        }

        // Filtro por curso
        if ($request->filled('curso') && $request->input('curso') !== 'all' && $request->input('curso') !== null) {
            $query->whereHas('cursoParalelo.curso', function ($q) use ($request) {
                $q->where('idCurso', $request->input('curso'));
            });
        }

        // Filtro por paralelo
        if ($request->filled('paralelo') && $request->input('paralelo') !== 'all' && $request->input('paralelo') !== null) {
            $query->whereHas('cursoParalelo.paralelo', function ($q) use ($request) {
                $q->where('idParalelo', $request->input('paralelo'));
            });
        }

        $estudiantes = $query->paginate(12);

        // Obtener estudiantes inactivos (soft delete) paginados
        $estudiantesInactivos = Estudiante::with([
            'user.puntajes',
            'cursoParalelo.curso',
            'cursoParalelo.paralelo'
        ])->onlyTrashed()->paginate(12, ['*'], 'inactivos_page');

        // Trae solo cursos y paralelos activos para los selectores
        $cursos = \App\Models\Curso::where('estado', true)->orderBy('nombre')->get(['idCurso', 'nombre']);
        $paralelos = \App\Models\Paralelo::where('estado', true)->orderBy('nombre')->get(['idParalelo', 'nombre']);

        // Obtener historial de importaciones recientes con formato de fecha corregido
        $historialImportaciones = HistorialImportacion::with(['cursoParalelo.curso', 'cursoParalelo.paralelo'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'curso_nombre' => $item->cursoParalelo->curso->nombre ?? '-',
                    'paralelo_nombre' => $item->cursoParalelo->paralelo->nombre ?? '-',
                    'insertados' => $item->insertados,
                    'actualizados' => $item->actualizados,
                    'omitidos' => $item->omitidos,
                    'errores' => $item->errores_count,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s')
                ];
            });

        return Inertia::render('admin/EstudiantesLIST', [
            'estudiantes' => $estudiantes,
            'estudiantesInactivos' => $estudiantesInactivos,
            'cursos' => $cursos,
            'paralelos' => $paralelos,
            'historialImportaciones' => $historialImportaciones,
        ]);
    }

    public function create()
    {
        $cursos = \App\Models\Curso::where('estado', true)->orderBy('nombre')->get(['idCurso', 'nombre']);
        $paralelos = \App\Models\Paralelo::where('estado', true)->orderBy('nombre')->get(['idParalelo', 'nombre']);
        $cursoParalelos = \App\Models\CursoParalelo::with(['curso', 'paralelo'])
            ->whereHas('curso', function($q) { $q->where('estado', true); })
            ->whereHas('paralelo', function($q) { $q->where('estado', true); })
            ->get();
        
        // Obtener usuarios con rol estudiante que aún no están asignados (incluyendo inactivos)
        $usuariosDisponibles = User::where('rol', 'estudiante')
            ->whereNull('deleted_at')
            ->whereDoesntHave('estudiante', function($q) {
                // Excluir usuarios que ya tengan estudiante (activo o inactivo)
                $q->withTrashed();
            })
            ->get(['id', 'nombres', 'primerApellido', 'segundoApellido', 'email']);

        return Inertia::render('admin/Estudiantes/Create', [
            'cursos' => $cursos,
            'paralelos' => $paralelos,
            'cursoParalelos' => $cursoParalelos,
            'usuariosDisponibles' => $usuariosDisponibles
        ]);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validar datos
            $request->validate([
                'idUser' => 'required|exists:usuario,id',
                'idCursoParalelo' => 'required|exists:curso_paralelo,idCursoParalelo',
            ],[ 
                'idUser.required' => 'El campo usuario es obligatorio.',
                'idCursoParalelo.required' => 'El campo curso-paralelo es obligatorio.',
            ]);

            // Verificar que el usuario no esté ya asignado como estudiante
            $existeEstudiante = Estudiante::where('idUser', $request->idUser)->exists();
            if ($existeEstudiante) {
                return redirect()->back()
                    ->withErrors(['idUser' => 'Este usuario ya está asignado como estudiante'])
                    ->withInput();
            }

            // Verificar que el usuario tenga rol estudiante
            $user = User::findOrFail($request->idUser);
            if ($user->rol !== 'estudiante') {
                return redirect()->back()
                    ->withErrors(['idUser' => 'El usuario seleccionado no tiene rol de estudiante'])
                    ->withInput();
            }

            // Crear estudiante
            Estudiante::create([
                'idUser' => $user->id,
                'idCursoParalelo' => $request->idCursoParalelo,
            ]);

            DB::commit();

            return redirect()->route('admin.estudiantes')
                ->with('success', 'Estudiante creado correctamente');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creando estudiante: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al crear el estudiante: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $estudiante = Estudiante::findOrFail($id);
            
            // Validar datos
            $request->validate([
                'user.nombres' => 'required|string|max:100',
                'user.primerApellido' => 'required|string|max:100',
                'user.segundoApellido' => 'nullable|string|max:100',
                'user.email' => 'required|email|max:255',
                'curso_paralelo.idCurso' => 'nullable|exists:curso,idCurso',
                'curso_paralelo.idParalelo' => 'nullable|exists:paralelo,idParalelo',
            ], [
                'user.nombres.required' => 'El campo nombres es obligatorio.',
                'user.nombres.string' => 'El campo nombres debe ser una cadena de texto.',
                'user.nombres.max' => 'El campo nombres no puede superar los 100 caracteres.',
                'user.primerApellido.required' => 'El campo primer apellido es obligatorio.',
                'user.primerApellido.string' => 'El campo primer apellido debe ser una cadena de texto.',
                'user.primerApellido.max' => 'El campo primer apellido no puede superar los 100 caracteres.',
                'user.segundoApellido.string' => 'El campo segundo apellido debe ser una cadena de texto.',
                'user.email.required' => 'El campo email es obligatorio.',
                'user.email.email' => 'El campo email debe ser una dirección de correo electrónico válida.',
                'curso_paralelo.idCurso.exists' => 'El curso seleccionado no existe.',
                'curso_paralelo.idParalelo.exists' => 'El paralelo seleccionado no existe.',
            ]);
            
            // Actualiza los datos del usuario
            $estudiante->user->update([
                'nombres' => $request->input('user.nombres'),
                'primerApellido' => $request->input('user.primerApellido'),
                'segundoApellido' => $request->input('user.segundoApellido'),
                'email' => $request->input('user.email'),
            ]);

            // Actualiza el curso-paralelo del estudiante (usando el id recibido)
            $idCurso = $request->input('curso_paralelo.idCurso');
            $idParalelo = $request->input('curso_paralelo.idParalelo');

            if ($idCurso && $idParalelo) {
                $cursoParalelo = \App\Models\CursoParalelo::firstOrCreate([
                    'idCurso' => $idCurso,
                    'idParalelo' => $idParalelo,
                ]);
                $estudiante->idCursoParalelo = $cursoParalelo->idCursoParalelo;
            }
            $estudiante->save();

            return redirect()->back()->with('success', 'Estudiante actualizado correctamente');
        } catch (\Exception $e) {
            \Log::error('Error actualizando estudiante: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el estudiante');
        }
    }

    public function destroy($id)
    {
        try {
            $estudiante = Estudiante::findOrFail($id);
            
            // Desactivar al estudiante
            $estudiante->delete();
            

            return redirect()->back()->with('success', 'Estudiante desactivado correctamente');
        } catch (\Exception $e) {
            \Log::error('Error desactivando estudiante: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al desactivar el estudiante');
        }
    }

    public function restore($id)
    {
        try {
            $estudiante = Estudiante::withTrashed()->findOrFail($id);
            
            // Reactivar al estudiante
            $estudiante->restore();
            
            // Reactivar tambien el usuario asociado
            if ($estudiante->user && $estudiante->user->trashed()) {
                $estudiante->user->restore();
            }

            return redirect()->back()->with('success', 'Estudiante reactivado correctamente');
        } catch (\Exception $e) {
            \Log::error('Error reactivando estudiante: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al reactivar el estudiante');
        }
    }

    public function show($id)
    {
        try {
            $estudiante = Estudiante::with([
                'user.puntajes',
                'cursoParalelo.curso',
                'cursoParalelo.paralelo'
            ])->findOrFail($id);

            // Obtener los últimos depósitos del estudiante (incluyendo tipo de basura con soft-deletes)
            $ultimosDepositos = Deposito::with([
                    'tipoBasura' => function ($q) { $q->withTrashed(); },
                    'basurero',
                    'periodo'
                ])
                ->where('idUser', $estudiante->idUser)
                ->orderBy('fechaHora', 'desc')
                ->limit(5)
                ->get();

            // Estadísticas de depósitos por tipo de basura (últimos 30 días)
            $depositosPorTipo = Deposito::with('tipoBasura')
                ->where('idUser', $estudiante->idUser)
                ->where('fechaHora', '>=', now()->subDays(30))
                ->get()
                ->groupBy('tipoBasura.nombre')
                ->map(function ($depositos, $tipoBasura) {
                    return [
                        'tipo' => $tipoBasura,
                        'cantidad' => $depositos->count(),
                        'puntos_totales' => $depositos->sum('tipoBasura.puntos'),
                        'ultimo_deposito' => $depositos->max('fechaHora')
                    ];
                })
                ->values();

            // Estadísticas generales
            $estadisticas = [
                'total_depositos' => \App\Models\Deposito::where('idUser', $estudiante->idUser)->count(),
                'depositos_este_mes' => \App\Models\Deposito::where('idUser', $estudiante->idUser)
                    ->whereMonth('fechaHora', now()->month)
                    ->whereYear('fechaHora', now()->year)
                    ->count(),
                 
                'dias_activo' => \App\Models\Deposito::where('idUser', $estudiante->idUser)
                    ->selectRaw('COUNT(DISTINCT DATE(fechaHora)) as dias')
                    ->value('dias') ?? 0
            ];

            return Inertia::render('admin/EstudianteView', [
                'estudiante' => $estudiante,
                'ultimosDepositos' => $ultimosDepositos,
                'depositosPorTipo' => $depositosPorTipo,
                'estadisticas' => $estadisticas,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error mostrando estudiante: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al mostrar el estudiante');
        }
    }

    /**
     * Mostrar vista de importación de estudiantes
     */
    public function showImport()
    {
        // Obtener historial de importaciones recientes
        $historialImportaciones = HistorialImportacion::with(['cursoParalelo.curso', 'cursoParalelo.paralelo'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'curso_nombre' => $item->cursoParalelo->curso->nombre ?? '-',
                    'paralelo_nombre' => $item->cursoParalelo->paralelo->nombre ?? '-',
                    'insertados' => $item->insertados,
                    'actualizados' => $item->actualizados,
                    'omitidos' => $item->omitidos,
                    'errores' => $item->errores_count,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s')
                ];
            });

        return Inertia::render('admin/EstudiantesImport', [
            'historialImportaciones' => $historialImportaciones,
        ]);
    }

    /**
     * Importar estudiantes desde Excel
     */
    public function importarEstudiantes(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240'
        ]);
    
        try {
            $file = $request->file('archivo');
            // Asegurar uso del disco local y que el directorio exista
            Storage::disk('local')->makeDirectory('temp');
            $path = $file->store('temp', 'local'); // devuelve 'temp/filename.xlsx'
            $fullPath = Storage::disk('local')->path($path);

            if (!Storage::disk('local')->exists($path)) {
                throw new \RuntimeException('No se pudo guardar el archivo temporal en storage/app/temp');
            }
    
            $import = new EstudiantesImport();
            $resultados = $import->import($fullPath);
    
            // Obtener historial actualizado inmediatamente después de la importación
            $historialReciente = HistorialImportacion::with(['cursoParalelo.curso', 'cursoParalelo.paralelo'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'curso_nombre' => $item->cursoParalelo->curso->nombre ?? '-',
                        'paralelo_nombre' => $item->cursoParalelo->paralelo->nombre ?? '-',
                        'insertados' => $item->insertados,
                        'actualizados' => $item->actualizados,
                        'omitidos' => $item->omitidos,
                        'errores' => $item->errores_count,
                        'created_at' => $item->created_at->format('Y-m-d H:i:s')
                    ];
                });
    
            // Agregar el historial SIEMPRE dentro de "data"
            $resultados['historial'] = $historialReciente;
    
            // Eliminar archivo temporal de forma segura
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
    
            // Si es una petición AJAX, devolver JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $resultados
                ]);
            }

            // Si es navegación normal, redirigir con mensaje de éxito
            return redirect()->route('admin.estudiantes.import')
                ->with('success', 'Importación completada exitosamente')
                ->with('importResults', $resultados);
    
        } catch (\Exception $e) {
            \Log::error('Error en importación: ' . $e->getMessage());
    
            // ⚡ También devolvemos historial en caso de error
            $historialReciente = HistorialImportacion::with(['cursoParalelo.curso', 'cursoParalelo.paralelo'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'curso_nombre' => $item->cursoParalelo->curso->nombre ?? '-',
                        'paralelo_nombre' => $item->cursoParalelo->paralelo->nombre ?? '-',
                        'insertados' => $item->insertados,
                        'actualizados' => $item->actualizados,
                        'omitidos' => $item->omitidos,
                        'errores' => $item->errores_count,
                        'created_at' => $item->created_at->format('Y-m-d H:i:s')
                    ];
                });
    
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage(),
                'data' => [
                    'historial' => $historialReciente
                ]
            ], 500);
        }
    }
    

    /**
     * Descargar plantilla Excel para importar estudiantes
     */
public function descargarPlantillaEstudiantes(Request $request)
{
    try {
        $fileName = 'Plantilla_Estudiantes_' . now()->format('Y-m-d') . '.xlsx';
        // Usar exportador propio basado en PhpSpreadsheet (no dependemos de Laravel Excel)
        $export = new PlantillaEstudiantesExport();
        return $export->download($fileName);
    } catch (\Exception $e) {
        \Log::error('Error al descargar plantilla: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

}