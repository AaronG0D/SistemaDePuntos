<?php

namespace Database\Seeders;

use App\Models\Basurero;
use App\Models\Deposito;
use App\Models\Estudiante;
use App\Models\PeriodoAcademico;
use App\Models\TipoBasura;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepositosPrimerBimestreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Obtener el primer período activo
        $primerPeriodo = PeriodoAcademico::where('activo', true)
            ->orderBy('fecha_inicio')
            ->first();

        if (!$primerPeriodo) {
            $this->command->error('❌ No hay un período activo. Por favor activa un período primero.');
            return;
        }

        $this->command->info("📅 Período: {$primerPeriodo->nombre}");
        $this->command->info("📆 Rango: {$primerPeriodo->fecha_inicio->format('Y-m-d')} a {$primerPeriodo->fecha_fin->format('Y-m-d')}");

        // 2. Obtener todos los estudiantes
        $estudiantes = Estudiante::with('user')->get();
        $totalEstudiantes = $estudiantes->count();

        if ($totalEstudiantes === 0) {
            $this->command->error('❌ No hay estudiantes en la base de datos.');
            return;
        }

        $this->command->info("👥 Total de estudiantes: {$totalEstudiantes}");

        // 3. Obtener tipos de basura y basureros disponibles
        $tiposBasura = TipoBasura::where('estado', true)->get();
        $basureros = Basurero::where('estado', true)->get();

        if ($tiposBasura->isEmpty() || $basureros->isEmpty()) {
            $this->command->error('❌ No hay tipos de basura o basureros activos.');
            return;
        }

        $this->command->info("🗑️  Tipos de basura disponibles: {$tiposBasura->count()}");
        $this->command->info("📍 Basureros disponibles: {$basureros->count()}");

        // 4. Distribuir estudiantes en categorías
        $distribucion = $this->distribuirEstudiantes($totalEstudiantes);
        
        $this->command->info("\n📊 Distribución de estudiantes:");
        $this->command->info("  • Sin depósitos: {$distribucion['sin_depositos']} estudiantes");
        $this->command->info("  • Pocos puntos (10-40): {$distribucion['pocos']} estudiantes");
        $this->command->info("  • Puntos medios (50-80): {$distribucion['medios']} estudiantes");
        $this->command->info("  • Muchos puntos (100+): {$distribucion['muchos']} estudiantes");

        // 5. Mezclar estudiantes aleatoriamente
        $estudiantesAleatorios = $estudiantes->shuffle();

        $depositosCreados = 0;
        $estudiantesConDepositos = 0;

        DB::beginTransaction();
        try {
            $offset = 0;

            // Sin depósitos
            $offset += $distribucion['sin_depositos'];
            $this->command->info("\n⏭️  Saltando {$distribucion['sin_depositos']} estudiantes sin depósitos...");

            // Pocos puntos (10-40 puntos)
            $this->command->info("\n🟡 Generando depósitos para estudiantes con pocos puntos...");
            $depositosCreados += $this->generarDepositosParaGrupo(
                $estudiantesAleatorios->slice($offset, $distribucion['pocos']),
                $primerPeriodo,
                $tiposBasura,
                $basureros,
                10,  // puntos mínimos
                40,  // puntos máximos
                1,   // depósitos mínimos
                3    // depósitos máximos
            );
            $estudiantesConDepositos += $distribucion['pocos'];
            $offset += $distribucion['pocos'];

            // Puntos medios (50-80 puntos)
            $this->command->info("\n🟠 Generando depósitos para estudiantes con puntos medios...");
            $depositosCreados += $this->generarDepositosParaGrupo(
                $estudiantesAleatorios->slice($offset, $distribucion['medios']),
                $primerPeriodo,
                $tiposBasura,
                $basureros,
                50,  // puntos mínimos
                80,  // puntos máximos
                3,   // depósitos mínimos
                6    // depósitos máximos
            );
            $estudiantesConDepositos += $distribucion['medios'];
            $offset += $distribucion['medios'];

            // Muchos puntos (100+ puntos)
            $this->command->info("\n🟢 Generando depósitos para estudiantes con muchos puntos...");
            $depositosCreados += $this->generarDepositosParaGrupo(
                $estudiantesAleatorios->slice($offset, $distribucion['muchos']),
                $primerPeriodo,
                $tiposBasura,
                $basureros,
                100, // puntos mínimos
                150, // puntos máximos
                7,   // depósitos mínimos
                12   // depósitos máximos
            );
            $estudiantesConDepositos += $distribucion['muchos'];

            DB::commit();

            $this->command->info("\n✅ Seeder completado exitosamente!");
            $this->command->info("📦 Total de depósitos creados: {$depositosCreados}");
            $this->command->info("👥 Estudiantes con depósitos: {$estudiantesConDepositos}");
            $this->command->info("🔢 Promedio de depósitos por estudiante: " . round($depositosCreados / max($estudiantesConDepositos, 1), 2));

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("\n❌ Error al generar depósitos: " . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }

    /**
     * Distribuye los estudiantes en diferentes categorías
     */
    private function distribuirEstudiantes(int $total): array
    {
        return [
            'sin_depositos' => (int) ($total * 0.15), // 15% sin depósitos
            'pocos' => (int) ($total * 0.30),         // 30% pocos puntos
            'medios' => (int) ($total * 0.35),        // 35% puntos medios
            'muchos' => (int) ($total * 0.20),        // 20% muchos puntos
        ];
    }

    /**
     * Genera depósitos para un grupo de estudiantes
     */
    private function generarDepositosParaGrupo(
        $estudiantes,
        PeriodoAcademico $periodo,
        $tiposBasura,
        $basureros,
        int $puntosMin,
        int $puntosMax,
        int $depositosMin,
        int $depositosMax
    ): int {
        $depositosCreados = 0;
        $bar = $this->command->getOutput()->createProgressBar($estudiantes->count());
        $bar->start();

        foreach ($estudiantes as $estudiante) {
            if (!$estudiante->user) {
                $bar->advance();
                continue;
            }

            // Determinar cuántos depósitos hará este estudiante
            $numDepositos = rand($depositosMin, $depositosMax);
            
            for ($i = 0; $i < $numDepositos; $i++) {
                // Seleccionar tipo de basura aleatorio
                $tipoBasura = $tiposBasura->random();
                
                // Generar fecha aleatoria dentro del período
                $fechaDeposito = $this->generarFechaAleatoria(
                    $periodo->fecha_inicio,
                    $periodo->fecha_fin
                );
                
                // Crear el depósito (el trigger calculará los puntos automáticamente)
                Deposito::create([
                    'idBasurero' => $basureros->random()->idBasurero,
                    'idUser' => $estudiante->idUser,
                    'idTipoBasura' => $tipoBasura->idTipoBasura,
                    'fechaHora' => $fechaDeposito,
                ]);
                
                $depositosCreados++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();

        return $depositosCreados;
    }

    /**
     * Genera una fecha aleatoria dentro del rango del período
     */
    private function generarFechaAleatoria(Carbon $inicio, Carbon $fin): Carbon
    {
        $timestampInicio = $inicio->timestamp;
        $timestampFin = $fin->timestamp;
        
        $timestampAleatorio = rand($timestampInicio, $timestampFin);
        
        return Carbon::createFromTimestamp($timestampAleatorio);
    }
}
