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
}