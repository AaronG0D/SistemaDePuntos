<?php


namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            
            // Eliminar el usuario asociado
            $estudiante->user->delete();
            
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

            return Inertia::render('admin/EstudianteView', [
                'estudiante' => $estudiante,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error mostrando estudiante: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al mostrar el estudiante');
        }
    }
}