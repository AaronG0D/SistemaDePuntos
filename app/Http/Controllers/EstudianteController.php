<?php


namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\User;
use App\Imports\EstudiantesImport;
use App\Exports\PlantillaEstudiantesExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EstudianteController extends Controller
{


    public function index(Request $request)
    {
        // Debug temporal
        \Log::info('Parámetros recibidos:', $request->all());

        $query = Estudiante::with([
            'user.puntaje',
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

        return Inertia::render('admin/EstudiantesLIST', [
            'estudiantes' => $estudiantes,
            'cursos' => $cursos,
            'paralelos' => $paralelos,
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
                'user.puntaje',
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
     * Importar estudiantes desde Excel
     */
    public function importarEstudiantes(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|mimes:xlsx,xls|max:10240'
            ]);

            if (!$request->hasFile('archivo') || !$request->file('archivo')->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no válido o corrupto'
                ], 400);
            }

            $file = $request->file('archivo');
            $path = $file->store('temp');
            $fullPath = storage_path('app/' . $path);

            $import = new EstudiantesImport();
            $import->filePath = $fullPath;
            $results = $import->import();

            // Limpiar archivo temporal
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'insertados' => $results['exitosos'],
                    'actualizados' => $results['actualizados'],
                    'errores' => $results['errores'],
                    'mensajes' => $results['mensajes']
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en importación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
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

        return \Maatwebsite\Excel\Facades\Excel::download(
            new PlantillaEstudiantesExport(),
            $fileName,
            \Maatwebsite\Excel\Excel::XLSX
        );
    } catch (\Exception $e) {
        \Log::error('Error al descargar plantilla: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

}