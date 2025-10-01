<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Estudiante;
use App\Models\Deposito;
use App\Models\PeriodoAcademico;
use Illuminate\Console\Command;
use Carbon\Carbon;

class TestStudentSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:student-system {user_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el sistema de estudiantes corregido';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Probando el sistema de estudiantes corregido...');
        
        // Obtener un estudiante de prueba
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $user = User::find($userId);
        } else {
            $user = User::where('rol', 'estudiante')->first();
        }
        
        if (!$user) {
            $this->error('❌ No se encontró ningún estudiante en la base de datos');
            return 1;
        }
        
        $this->info("👤 Probando con usuario: {$user->nombres} {$user->primerApellido}");
        
        // Probar obtención de datos del estudiante
        $this->testStudentData($user);
        
        // Probar obtención de depósitos
        $this->testStudentDeposits($user);
        
        // Probar cálculo de ranking
        $this->testStudentRanking($user);
        
        // Probar períodos académicos
        $this->testAcademicPeriods();
        
        $this->info('✅ Todas las pruebas completadas exitosamente!');
        
        return 0;
    }
    
    private function testStudentData(User $user)
    {
        $this->info("\n🔍 Probando obtención de datos del estudiante...");
        
        $estudiante = $user->estudiante()->with([
            'cursoParalelo.curso',
            'cursoParalelo.paralelo'
        ])->first();
        
        if (!$estudiante) {
            $this->warn('⚠️  El usuario no tiene registro de estudiante');
            return;
        }
        
        $this->line("   - ID Usuario: {$user->id}");
        $this->line("   - Nombres: {$user->nombres}");
        $this->line("   - Apellidos: {$user->primerApellido} {$user->segundoApellido}");
        $this->line("   - QR Código: {$user->qr_codigo}");
        
        if ($estudiante->cursoParalelo) {
            $curso = $estudiante->cursoParalelo->curso;
            $paralelo = $estudiante->cursoParalelo->paralelo;
            
            $this->line("   - Curso: " . ($curso ? $curso->nombre : 'No asignado'));
            $this->line("   - Paralelo: " . ($paralelo ? $paralelo->nombre : 'No asignado'));
        } else {
            $this->warn('   - ⚠️  No tiene curso-paralelo asignado');
        }
        
        $this->info('✅ Datos del estudiante obtenidos correctamente');
    }
    
    private function testStudentDeposits(User $user)
    {
        $this->info("\n🗂️  Probando obtención de depósitos...");
        
        $currentYear = Carbon::now()->year;
        $deposits = $user->depositos()
            ->with('tipoBasura')
            ->whereYear('fechaHora', $currentYear)
            ->orderBy('fechaHora', 'desc')
            ->get();
        
        $this->line("   - Total depósitos {$currentYear}: {$deposits->count()}");
        
        if ($deposits->count() > 0) {
            $totalPoints = $deposits->sum('puntos');
            $totalWeight = $deposits->sum('peso');
            
            $this->line("   - Puntos totales: {$totalPoints}");
            $this->line("   - Peso total: {$totalWeight} kg");
            
            $this->line("   - Últimos 3 depósitos:");
            foreach ($deposits->take(3) as $deposit) {
                $tipoBasura = $deposit->tipoBasura ? $deposit->tipoBasura->nombre : 'Sin tipo';
                $fecha = $deposit->fechaHora->format('d/m/Y H:i');
                $this->line("     * {$fecha} - {$tipoBasura} - {$deposit->peso}kg - {$deposit->puntos} pts");
            }
        } else {
            $this->warn('   - ⚠️  No tiene depósitos registrados este año');
        }
        
        $this->info('✅ Depósitos obtenidos correctamente');
    }
    
    private function testStudentRanking(User $user)
    {
        $this->info("\n🏆 Probando cálculo de ranking...");
        
        $estudiante = $user->estudiante()->first();
        if (!$estudiante) {
            $this->warn('   - ⚠️  No se puede calcular ranking sin registro de estudiante');
            return;
        }
        
        // Obtener estudiantes del mismo curso-paralelo
        $estudiantesIds = Estudiante::where('idCursoParalelo', $estudiante->idCursoParalelo)
            ->pluck('idUser');
        
        $this->line("   - Estudiantes en el mismo curso-paralelo: {$estudiantesIds->count()}");
        
        // Calcular ranking
        $currentYear = Carbon::now()->year;
        $rankings = Deposito::select('idUser', \DB::raw('COALESCE(SUM(puntos), 0) as total_puntos'))
            ->whereIn('idUser', $estudiantesIds)
            ->whereYear('fechaHora', $currentYear)
            ->groupBy('idUser')
            ->orderByDesc('total_puntos')
            ->get();
        
        $position = $rankings->search(function ($item) use ($user) {
            return $item->idUser == $user->id;
        });
        
        $ranking = $position !== false ? $position + 1 : $rankings->count() + 1;
        $userPoints = $rankings->where('idUser', $user->id)->first()?->total_puntos ?? 0;
        
        $this->line("   - Posición en ranking: #{$ranking}");
        $this->line("   - Puntos del estudiante: {$userPoints}");
        
        if ($rankings->count() > 0) {
            $topStudent = $rankings->first();
            $this->line("   - Líder del curso: Usuario ID {$topStudent->idUser} con {$topStudent->total_puntos} puntos");
        }
        
        $this->info('✅ Ranking calculado correctamente');
    }
    
    private function testAcademicPeriods()
    {
        $this->info("\n📅 Probando períodos académicos...");
        
        $currentPeriod = PeriodoAcademico::where('activo', true)
            ->orderBy('fecha_inicio', 'desc')
            ->first();
        
        if ($currentPeriod) {
            $this->line("   - Período actual: {$currentPeriod->nombre}");
            $this->line("   - Fecha inicio: {$currentPeriod->fecha_inicio->format('d/m/Y')}");
            $this->line("   - Fecha fin: {$currentPeriod->fecha_fin->format('d/m/Y')}");
        } else {
            $this->warn('   - ⚠️  No hay período académico activo');
        }
        
        $currentYear = Carbon::now()->year;
        $periodsThisYear = PeriodoAcademico::whereYear('fecha_inicio', $currentYear)
            ->orderBy('fecha_inicio')
            ->get();
        
        $this->line("   - Períodos en {$currentYear}: {$periodsThisYear->count()}");
        
        foreach ($periodsThisYear as $period) {
            $status = $period->activo ? '(ACTIVO)' : '';
            $this->line("     * {$period->nombre} {$status}");
        }
        
        $this->info('✅ Períodos académicos verificados');
    }
}
