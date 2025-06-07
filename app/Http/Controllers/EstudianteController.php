<?php


namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstudianteController extends Controller
{

    public function index()
    {
        $estudiantes = Estudiante::with(['user', 'cursoParalelo.curso', 'cursoParalelo.paralelo'])
            ->get()
            ->map(function ($estudiante) {
                return [
                    'id' => $estudiante->idUser,
                    'nombres' => $estudiante->user->nombres,
                    'primerApellido' => $estudiante->user->primerApellido,
                    'segundoApellido' => $estudiante->user->segundoApellido,
                    'email' => $estudiante->user->email,
                    'curso' => $estudiante->cursoParalelo->curso->nombre,
                    'paralelo' => $estudiante->cursoParalelo->paralelo->nombre,
                    'qr_codigo' => $estudiante->user->qr_codigo,
                    'puntos_totales' => $estudiante->puntos_totales ?? 0,
                ];
            });

        return Inertia::render('admin/EstudiantesLIST', [
            'estudiantes' => $estudiantes
        ]);
    }
}