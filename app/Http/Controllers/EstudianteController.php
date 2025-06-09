<?php


namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstudianteController extends Controller
{

    public function index(Request $request)
    {
        $estudiantes = Estudiante::with([
            'user.puntaje',
            'cursoParalelo.curso',
            'cursoParalelo.paralelo'
        ])->paginate(10); // Cambia 10 por la cantidad que desees por página

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
}