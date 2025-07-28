<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia y corrige inconsistencias en la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando corrección de la base de datos...');

        try {
            // 1. Crear tabla materia_curso_paralelo si no existe
            $this->info('1. Verificando tabla materia_curso_paralelo...');
            if (!Schema::hasTable('materia_curso_paralelo')) {
                Schema::create('materia_curso_paralelo', function ($table) {
                    $table->increments('id');
                    $table->unsignedSmallInteger('idCursoParalelo');
                    $table->unsignedSmallInteger('idMateria');
                    $table->timestamps();

                    $table->foreign('idCursoParalelo')
                          ->references('idCursoParalelo')
                          ->on('curso_paralelo')
                          ->onDelete('cascade');
                    $table->foreign('idMateria')
                          ->references('idMateria')
                          ->on('materia')
                          ->onDelete('cascade');
                });
                $this->info('   - Tabla materia_curso_paralelo creada');
            } else {
                $this->info('   - Tabla materia_curso_paralelo ya existe');
            }

            // 2. Verificar datos básicos
            $this->info('2. Verificando datos básicos...');
            
            // Cursos
            if (DB::table('curso')->count() == 0) {
                DB::table('curso')->insert([
                    ['idCurso' => 1, 'nombre' => 'Primero'],
                    ['idCurso' => 2, 'nombre' => 'Segundo'],
                    ['idCurso' => 3, 'nombre' => 'Tercero'],
                    ['idCurso' => 4, 'nombre' => 'Cuarto'],
                ]);
                $this->info('   - Cursos creados');
            }

            // Paralelos
            if (DB::table('paralelo')->count() == 0) {
                DB::table('paralelo')->insert([
                    ['idParalelo' => 1, 'nombre' => 'A'],
                    ['idParalelo' => 2, 'nombre' => 'B'],
                    ['idParalelo' => 3, 'nombre' => 'C'],
                    ['idParalelo' => 4, 'nombre' => 'D'],
                ]);
                $this->info('   - Paralelos creados');
            }

            // Materias
            if (DB::table('materia')->count() == 0) {
                DB::table('materia')->insert([
                    ['idMateria' => 1, 'nombre' => 'Matemáticas'],
                    ['idMateria' => 2, 'nombre' => 'Lenguaje'],
                    ['idMateria' => 3, 'nombre' => 'Ciencias Naturales'],
                    ['idMateria' => 4, 'nombre' => 'Historia'],
                    ['idMateria' => 5, 'nombre' => 'Física'],
                ]);
                $this->info('   - Materias creadas');
            }

            // Curso_paralelo básicos
            if (DB::table('curso_paralelo')->count() == 0) {
                $cursoParalelos = [];
                for ($curso = 1; $curso <= 4; $curso++) {
                    for ($paralelo = 1; $paralelo <= 4; $paralelo++) {
                        $cursoParalelos[] = [
                            'idCurso' => $curso,
                            'idParalelo' => $paralelo
                        ];
                    }
                }
                DB::table('curso_paralelo')->insert($cursoParalelos);
                $this->info('   - Curso-paralelos creados');
            }

            // 3. Crear relaciones materia-curso_paralelo basadas en las asignaciones existentes
            $this->info('3. Creando relaciones materia-curso_paralelo...');
            
            // Obtener todas las asignaciones existentes de docentes
            $asignacionesExistentes = DB::table('docente_materia_curso')
                ->select('idMateria', 'idCursoParalelo')
                ->distinct()
                ->get();

            $relacionesCreadas = 0;
            foreach ($asignacionesExistentes as $asignacion) {
                $existe = DB::table('materia_curso_paralelo')
                    ->where('idMateria', $asignacion->idMateria)
                    ->where('idCursoParalelo', $asignacion->idCursoParalelo)
                    ->exists();

                if (!$existe) {
                    DB::table('materia_curso_paralelo')->insert([
                        'idMateria' => $asignacion->idMateria,
                        'idCursoParalelo' => $asignacion->idCursoParalelo,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $relacionesCreadas++;
                }
            }

            $this->info("   - {$relacionesCreadas} relaciones materia-curso_paralelo creadas");

            // 4. Crear relaciones básicas si no hay asignaciones existentes
            if ($relacionesCreadas == 0) {
                $this->info('4. Creando relaciones básicas materia-curso_paralelo...');
                
                $materias = DB::table('materia')->get();
                $cursoParalelos = DB::table('curso_paralelo')->get();
                
                $relacionesBasicas = [];
                foreach ($materias as $materia) {
                    foreach ($cursoParalelos as $cursoParalelo) {
                        $relacionesBasicas[] = [
                            'idMateria' => $materia->idMateria,
                            'idCursoParalelo' => $cursoParalelo->idCursoParalelo,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                
                if (!empty($relacionesBasicas)) {
                    DB::table('materia_curso_paralelo')->insert($relacionesBasicas);
                    $this->info('   - Relaciones básicas creadas');
                }
            }

            // 5. Verificar integridad referencial
            $this->info('5. Verificando integridad referencial...');
            
            // Limpiar asignaciones de docentes con referencias inválidas
            DB::statement("
                DELETE dmc FROM docente_materia_curso dmc
                LEFT JOIN materia m ON dmc.idMateria = m.idMateria
                LEFT JOIN curso_paralelo cp ON dmc.idCursoParalelo = cp.idCursoParalelo
                WHERE m.idMateria IS NULL OR cp.idCursoParalelo IS NULL
            ");

            // Verificar estudiantes
            DB::statement("
                UPDATE estudiante e
                LEFT JOIN curso_paralelo cp ON e.idCursoParalelo = cp.idCursoParalelo
                SET e.idCursoParalelo = 1
                WHERE cp.idCursoParalelo IS NULL
            ");

            // 6. Asegurar puntajes para estudiantes
            $this->info('6. Asegurando puntajes para estudiantes...');
            DB::statement("
                INSERT IGNORE INTO puntaje (idUser, puntajeTotal)
                SELECT id, 0 FROM usuario 
                WHERE rol = 'estudiante' 
                AND id NOT IN (SELECT idUser FROM puntaje)
            ");

            // 7. Limpiar usuarios inválidos
            $this->info('7. Limpiando usuarios inválidos...');
            DB::table('usuario')->whereNotIn('rol', ['estudiante', 'docente', 'administrador'])->delete();

            // Verificar integridad referencial de docentes y estudiantes
            DB::statement("
                DELETE d FROM docente d
                LEFT JOIN usuario u ON d.idUser = u.id
                WHERE u.id IS NULL
            ");

            DB::statement("
                DELETE e FROM estudiante e
                LEFT JOIN usuario u ON e.idUser = u.id
                WHERE u.id IS NULL
            ");

            // 8. Crear índices
            $this->info('8. Creando índices...');
            $indices = [
                'CREATE INDEX IF NOT EXISTS idx_docente_materia_curso_docente ON docente_materia_curso(idDocente)',
                'CREATE INDEX IF NOT EXISTS idx_docente_materia_curso_materia ON docente_materia_curso(idMateria)',
                'CREATE INDEX IF NOT EXISTS idx_docente_materia_curso_curso_paralelo ON docente_materia_curso(idCursoParalelo)',
                'CREATE INDEX IF NOT EXISTS idx_materia_curso_paralelo_materia ON materia_curso_paralelo(idMateria)',
                'CREATE INDEX IF NOT EXISTS idx_materia_curso_paralelo_curso_paralelo ON materia_curso_paralelo(idCursoParalelo)',
            ];

            foreach ($indices as $indice) {
                try {
                    DB::statement($indice);
                } catch (\Exception $e) {
                    // El índice ya existe
                }
            }

            // 9. Limpiar conflictos entre docentes
            $this->info('9. Limpiando conflictos entre docentes...');
            $conflictos = DB::table('docente_materia_curso as dmc1')
                ->join('docente_materia_curso as dmc2', function ($join) {
                    $join->on('dmc1.idMateria', '=', 'dmc2.idMateria')
                         ->on('dmc1.idCursoParalelo', '=', 'dmc2.idCursoParalelo')
                         ->on('dmc1.idDocente', '!=', 'dmc2.idDocente');
                })
                ->select('dmc1.idDocente as docente1', 'dmc2.idDocente as docente2', 'dmc1.idMateria', 'dmc1.idCursoParalelo')
                ->get();

            $conflictosResueltos = 0;
            foreach ($conflictos as $conflicto) {
                // Mantener la asignación del docente con ID menor (más antiguo)
                $docenteAMantener = min($conflicto->docente1, $conflicto->docente2);
                $docenteAEliminar = max($conflicto->docente1, $conflicto->docente2);

                // Eliminar la asignación duplicada
                DB::table('docente_materia_curso')
                    ->where('idDocente', $docenteAEliminar)
                    ->where('idMateria', $conflicto->idMateria)
                    ->where('idCursoParalelo', $conflicto->idCursoParalelo)
                    ->delete();

                $conflictosResueltos++;
            }

            if ($conflictosResueltos > 0) {
                $this->info("   - {$conflictosResueltos} conflictos resueltos");
            } else {
                $this->info("   - No se encontraron conflictos");
            }

            $this->info('✅ Base de datos corregida exitosamente!');
            
            // Mostrar estadísticas
            $this->info('Estadísticas:');
            $this->info('- Usuarios: ' . DB::table('usuario')->count());
            $this->info('- Estudiantes: ' . DB::table('estudiante')->count());
            $this->info('- Docentes: ' . DB::table('docente')->count());
            $this->info('- Materias: ' . DB::table('materia')->count());
            $this->info('- Cursos: ' . DB::table('curso')->count());
            $this->info('- Paralelos: ' . DB::table('paralelo')->count());
            $this->info('- Curso-Paralelos: ' . DB::table('curso_paralelo')->count());
            $this->info('- Relaciones Materia-CursoParalelo: ' . DB::table('materia_curso_paralelo')->count());
            $this->info('- Asignaciones de docentes: ' . DB::table('docente_materia_curso')->count());

        } catch (\Exception $e) {
            $this->error('Error durante la corrección: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 