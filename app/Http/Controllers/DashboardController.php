<?php

namespace App\Http\Controllers;

use App\Models\Deposito;
use App\Models\puntos;
use App\Models\User;
use App\Models\Curso;
use App\Models\CursoParalelo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();
        $inicioSemana = Carbon::now()->startOfWeek();
        $inicioMes = Carbon::now()->startOfMonth();
        $mesAnterior = Carbon::now()->subMonth();

        // Obtener estadísticas de depósitos
        $depositosHoy = Deposito::whereDate('created_at', $hoy)->count();
        $depositosSemana = Deposito::whereBetween('created_at', [$inicioSemana, Carbon::now()])->count();
        $depositosMes = Deposito::whereBetween('created_at', [$inicioMes, Carbon::now()])->count();
        
        // Obtener estadísticas de puntos por período
        $puntosHoy = Deposito::whereDate('deposito.created_at', $hoy)
            ->join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
            ->sum('tipoBasura.puntos');
            
        $puntosSemana = Deposito::whereBetween('deposito.created_at', [$inicioSemana, Carbon::now()])
            ->join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
            ->sum('tipoBasura.puntos');
            
        $puntosMes = Deposito::whereBetween('deposito.created_at', [$inicioMes, Carbon::now()])
            ->join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
            ->sum('tipoBasura.puntos');
        
        // Calcular variación respecto al mes anterior
        $puntosMesAnterior = Deposito::whereBetween('deposito.created_at', [$mesAnterior->startOfMonth(), $mesAnterior->endOfMonth()])
            ->join('tipoBasura', 'deposito.idTipoBasura', '=', 'tipoBasura.idTipoBasura')
            ->sum('tipoBasura.puntos');
            
        $variacionMes = $puntosMesAnterior > 0 
            ? (($puntosMes - $puntosMesAnterior) / $puntosMesAnterior) * 100 
            : 100;

        // Top 10 estudiantes (usando la tabla puntos)
        $topEstudiantes = User::where('rol', 'estudiante')
            ->join('puntaje', 'usuario.id', '=', 'puntaje.idUser')
            ->orderBy('puntaje.puntajeTotal', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($est) => [
                'nombre' => $est->nombres . ' ' . $est->primerApellido,
                'puntos' => $est->puntajeTotal
            ]);

        // Ranking por curso (sumando puntos de los estudiantes)
        $rankingCurso = Curso::select('curso.*')
            ->join('curso_paralelo', 'curso.idCurso', '=', 'curso_paralelo.idCurso')
            ->join('estudiante', 'estudiante.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
            ->join('usuario', 'usuario.id', '=', 'estudiante.idUser')
            ->join('puntaje', 'usuario.id', '=', 'puntaje.idUser')
            ->where('usuario.rol', 'estudiante')
            ->groupBy('curso.idCurso')
            ->select('curso.nombre', DB::raw('SUM(puntaje.puntajeTotal) as puntos'))
            ->orderByDesc('puntos')
            ->get()
            ->map(fn($curso) => [
                'curso' => $curso->nombre,
                'puntos' => $curso->puntos ?? 0
            ]);

        // Ranking por paralelo
        $rankingParalelo = CursoParalelo::with(['curso', 'paralelo'])
            ->join('estudiante', 'estudiante.idCursoParalelo', '=', 'curso_paralelo.idCursoParalelo')
            ->join('usuario', 'usuario.id', '=', 'estudiante.idUser')
            ->join('puntaje', 'usuario.id', '=', 'puntaje.idUser')
            ->where('usuario.rol', 'estudiante')
            ->groupBy('curso_paralelo.idCursoParalelo')
            ->select(
                'curso_paralelo.*',
                DB::raw('SUM(puntaje.puntajeTotal) as puntos')
            )
            ->orderByDesc('puntos')
            ->get()
            ->map(fn($cp) => [
                'paralelo' => $cp->curso->nombre . ' ' . $cp->paralelo->nombre,
                'puntos' => $cp->puntos ?? 0
            ]);

        return Inertia::render('Dashboard', [
            'estadisticas' => [
                'depositos_hoy' => $depositosHoy,
                'depositos_semana' => $depositosSemana,
                'depositos_mes' => $depositosMes,
                'puntos_hoy' => $puntosHoy,
                'puntos_semana' => $puntosSemana,
                'puntos_mes' => $puntosMes,
                'variacion_mes' => round($variacionMes, 2),
                'top_estudiantes' => $topEstudiantes,
                'ranking_curso' => $rankingCurso,
                'ranking_paralelo' => $rankingParalelo,
            ]
        ]);
    }
}
