<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HistorialImportacion;
use Carbon\Carbon;

class HistorialImportacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $historialData = [
            [
                'curso_nombre' => 'Matemáticas Avanzadas',
                'paralelo_nombre' => 'A',
                'insertados' => 25,
                'actualizados' => 3,
                'omitidos' => 2,
                'errores_count' => 0,
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'curso_nombre' => 'Física General',
                'paralelo_nombre' => 'B',
                'insertados' => 30,
                'actualizados' => 1,
                'omitidos' => 0,
                'errores_count' => 1,
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'curso_nombre' => 'Química Orgánica',
                'paralelo_nombre' => 'A',
                'insertados' => 22,
                'actualizados' => 5,
                'omitidos' => 1,
                'errores_count' => 0,
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'curso_nombre' => 'Historia Universal',
                'paralelo_nombre' => 'C',
                'insertados' => 28,
                'actualizados' => 2,
                'omitidos' => 3,
                'errores_count' => 2,
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'curso_nombre' => 'Programación Web',
                'paralelo_nombre' => 'A',
                'insertados' => 20,
                'actualizados' => 4,
                'omitidos' => 0,
                'errores_count' => 0,
                'created_at' => Carbon::now()->subDays(7),
                'updated_at' => Carbon::now()->subDays(7),
            ],
        ];

        foreach ($historialData as $data) {
            HistorialImportacion::create($data);
        }
    }
}
