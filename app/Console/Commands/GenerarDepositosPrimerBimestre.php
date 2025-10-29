<?php

namespace App\Console\Commands;

use Database\Seeders\DepositosPrimerBimestreSeeder;
use Illuminate\Console\Command;

class GenerarDepositosPrimerBimestre extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'depositos:generar-primer-bimestre';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera depósitos aleatorios para todos los estudiantes en el primer bimestre activo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando generación de depósitos para el primer bimestre...');
        $this->newLine();

        $seeder = new DepositosPrimerBimestreSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        return Command::SUCCESS;
    }
}
