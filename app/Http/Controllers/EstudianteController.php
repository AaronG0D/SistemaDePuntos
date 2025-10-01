<?php


namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\User;
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
        ]);

        // Filtro por búsqueda
        if ($request->filled('search') && trim($request->input('search')) !== '') {
            $search = trim($request->input('search'));
            \Log::info('Aplicando filtro de búsqueda:', ['search' => $search]);
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('primerApellido', 'like', "%{$search}%")
                  ->orWhere('segundoApellido', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
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

        $estudiantes = $query->paginate(10);

        // Trae todos los cursos y paralelos como objetos
        $cursos = \App\Models\Curso::orderBy('nombre')->get(['idCurso', 'nombre']);
        $paralelos = \App\Models\Paralelo::orderBy('nombre')->get(['idParalelo', 'nombre']);

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
            'cursos' => $cursos,
            'paralelos' => $paralelos,
            'historialImportaciones' => $historialImportaciones,
        ]);
    }

    public function create()
    {
        $cursos = \App\Models\Curso::orderBy('nombre')->get(['idCurso', 'nombre']);
        $paralelos = \App\Models\Paralelo::orderBy('nombre')->get(['idParalelo', 'nombre']);
        $cursoParalelos = \App\Models\CursoParalelo::with(['curso', 'paralelo'])->get();
        
        // Obtener usuarios con rol estudiante que aún no están asignados
        $usuariosDisponibles = User::where('rol', 'estudiante')
            ->whereDoesntHave('estudiante')
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

            // Actualiza los datos del usuario
            $estudiante->user->update($request->input('user'));

            // Busca o crea el registro de curso_paralelo
            $cursoParalelo = \App\Models\CursoParalelo::firstOrCreate([
                'idCurso' => $request->input('curso_paralelo.idCurso'),
                'idParalelo' => $request->input('curso_paralelo.idParalelo')
            ]);

            // Actualiza el idCursoParalelo del estudiante y guarda
            $estudiante->idCursoParalelo = $cursoParalelo->idCursoParalelo;
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
            
            
            // Eliminar el estudiante
            $estudiante->delete();

            return redirect()->back()->with('success', 'Estudiante eliminado correctamente');
        } catch (\Exception $e) {
            \Log::error('Error eliminando estudiante: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar el estudiante');
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

            // Obtener los últimos depósitos del estudiante agrupados por tipo de basura
            $ultimosDepositos = \App\Models\Deposito::with(['tipoBasura', 'basurero'])
                ->where('idUser', $estudiante->idUser)
                ->orderBy('fechaHora', 'desc')
                ->limit(10)
                ->get();

            // Estadísticas de depósitos por tipo de basura (últimos 30 días)
            $depositosPorTipo = \App\Models\Deposito::with('tipoBasura')
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
                'kg_reciclados_estimados' => \App\Models\Deposito::where('idUser', $estudiante->idUser)->count() * 0.5, // Estimación
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