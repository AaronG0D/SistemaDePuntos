<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RankingController extends Controller
{
    public function rankingEstudiantes(Request $request)
    {
        $student = auth()->user()->student;
        $currentPeriod = Period::where('activo', true)->first();
        
        // Obtener estudiantes del mismo curso
        $ranking = Student::where('curso_paralelo_id', $student->curso_paralelo_id)
            ->with(['user'])
            ->withSum(['deposits' => function($query) use ($currentPeriod) {
                if ($currentPeriod) {
                    $query->where('periodo_id', $currentPeriod->id);
                }
            }], 'puntaje_obtenido')
            ->orderByDesc('deposits_sum_puntaje_obtenido')
            ->get()
            ->map(function($s, $index) {
                return [
                    'id' => $s->id,
                    'nombres' => $s->user->nombres,
                    'apellidos' => $s->user->apellidos,
                    'puntaje' => (int)$s->deposits_sum_puntaje_obtenido,
                    'posicion' => $index + 1
                ];
            });

        return Inertia::render('Students/Ranking', [
            'student' => $student->load('curso', 'paralelo'),
            'ranking' => $ranking,
            'currentPeriod' => $currentPeriod,
            'myPosition' => $ranking->where('id', $student->id)->first()['posicion'] ?? null,
            'totalStudents' => $ranking->count()
        ]);
    }
}