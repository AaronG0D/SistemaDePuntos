<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Estudiante;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class EstudiantesImport
{
    private $resultados = [
        'insertados' => 0,      // Estudiantes completamente nuevos
        'actualizados' => 0,    // Estudiantes existentes actualizados
        'omitidos' => 0,        // Estudiantes que ya están en el curso
        'errores' => [],        // Estudiantes no procesados por errores
        'mensajes' => []
    ];
    
    private $filePath;
    
    public function __construct()
    {
    }
    
    public function getResultados()
    {
        return $this->resultados;
    }

    public function import($filePath)
    {
        $this->filePath = $filePath;
        
        try {
            // Obtener curso y paralelo del header del Excel
            $cursoParaleloId = $this->getCursoParaleloFromHeader();
            
            if (!$cursoParaleloId) {
                $this->resultados['errores'][] = [
                    'fila' => 0,
                    'mensaje' => 'No se pudo encontrar el curso y paralelo especificado en el archivo. Verifica que la plantilla tenga los datos correctos en las celdas B2 y E2.'
                ];
                return $this->resultados;
            }

            // Leer el archivo Excel directamente
            $spreadsheet = IOFactory::load($this->filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();

            // Procesar desde la fila 13 (donde empiezan los datos según la memoria)
            for ($rowIndex = 13; $rowIndex <= $highestRow; $rowIndex++) {
                
                try {
                    // Leer datos de la fila actual
                    $nombres = trim($worksheet->getCell('A' . $rowIndex)->getValue() ?? '');
                    $apellidos = trim($worksheet->getCell('B' . $rowIndex)->getValue() ?? '');
                    $email = trim($worksheet->getCell('C' . $rowIndex)->getValue() ?? '');
                    $fechaNacimiento = $this->parseDate($worksheet->getCell('D' . $rowIndex)->getValue());
                    $genero = $this->normalizeGender($worksheet->getCell('E' . $rowIndex)->getValue() ?? '');

                    // Validar que la fila no esté vacía
                    if (empty($nombres) && empty($apellidos) && empty($email)) {
                        continue; // Saltar filas vacías
                    }

                    // Validaciones básicas
                    if (empty($nombres) || empty($apellidos) || empty($email)) {
                        $this->resultados['errores'][] = [
                            'fila' => $rowIndex,
                            'mensaje' => 'Nombres, apellidos y email son obligatorios'
                        ];
                        continue;
                    }

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->resultados['errores'][] = [
                            'fila' => $rowIndex,
                            'mensaje' => 'Email inválido'
                        ];
                        continue;
                    }

                    if (!empty($genero) && !in_array($genero, ['M', 'F'])) {
                        $this->resultados['errores'][] = [
                            'fila' => $rowIndex,
                            'mensaje' => 'Género debe ser M o F'
                        ];
                        continue;
                    }

                    // Verificar si el usuario ya existe
                    $existingUser = User::where('email', $email)->first();
                    
                    if ($existingUser) {
                        // Verificar si ya es estudiante en este curso específico
                        $existingStudent = Estudiante::where('idUser', $existingUser->id)
                            ->where('idCursoParalelo', $cursoParaleloId)
                            ->first();
                        
                        if ($existingStudent) {
                            $this->resultados['omitidos']++;
                            $this->resultados['mensajes'][] = "Fila {$rowIndex}: Estudiante ya existe en este curso: {$email}";
                            continue;
                        }
                        
                        // Actualizar datos del usuario existente
                        $apellidosParts = explode(' ', trim($apellidos), 2);
                        $primerApellido = $apellidosParts[0] ?? '';
                        $segundoApellido = $apellidosParts[1] ?? '';
                        
                        $existingUser->update([
                            'nombres' => $nombres,
                            'primerApellido' => $primerApellido,
                            'segundoApellido' => $segundoApellido,
                        ]);
                        
                        // Verificar si el estudiante existe en otros cursos
                        $existingStudentOtherCourse = Estudiante::where('idUser', $existingUser->id)->first();
                        
                        if ($existingStudentOtherCourse) {
                            // Actualizar el curso del estudiante existente
                            $existingStudentOtherCourse->update([
                                'idCursoParalelo' => $cursoParaleloId
                            ]);
                            $this->resultados['actualizados']++;
                            $this->resultados['mensajes'][] = "Fila {$rowIndex}: Estudiante movido de curso anterior al nuevo curso: {$email}";
                        } else {
                            // Crear nuevo registro de estudiante
                            $this->createStudent($existingUser->id, $cursoParaleloId);
                            $this->resultados['actualizados']++;
                            $this->resultados['mensajes'][] = "Fila {$rowIndex}: Usuario existente agregado al curso: {$email}";
                        }
                    } else {
                        // Crear nuevo usuario PRIMERO
                        $userId = $this->createUser($nombres, $apellidos, $email, $fechaNacimiento, $genero);
                        
                        // Luego crear el estudiante
                        $this->createStudent($userId, $cursoParaleloId);
                        $this->resultados['insertados']++;
                        $this->resultados['mensajes'][] = "Fila {$rowIndex}: Nuevo estudiante creado: {$email}";
                    }

                } catch (\Exception $e) {
                    $this->resultados['errores'][] = [
                        'fila' => $rowIndex,
                        'mensaje' => 'Error al procesar: ' . $e->getMessage()
                    ];
                }
            }

        } catch (\Exception $e) {
            $this->resultados['errores'][] = [
                'fila' => 0,
                'mensaje' => 'Error al leer el archivo: ' . $e->getMessage()
            ];
        }

        return $this->resultados;
    }

    private function getCursoParaleloFromHeader()
    {
        try {
            $spreadsheet = IOFactory::load($this->filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // El curso está en B2 y el paralelo en E2 según la plantilla
            $nombreCurso = trim($worksheet->getCell('B2')->getValue() ?? '');
            $nombreParalelo = trim($worksheet->getCell('E2')->getValue() ?? '');
            
            // Agregar información de debug
            $this->resultados['mensajes'][] = "Debug: Curso leído de B2: '{$nombreCurso}'";
            $this->resultados['mensajes'][] = "Debug: Paralelo leído de E2: '{$nombreParalelo}'";
            
            // Verificar si contienen texto de placeholder
            if (strpos($nombreCurso, '[Escriba aquí') !== false || strpos($nombreParalelo, '[Escriba aquí') !== false) {
                $this->resultados['mensajes'][] = "Error: Debe reemplazar el texto de ejemplo en las celdas B2 y E2 con valores reales";
                return null;
            }
            
            if (empty($nombreCurso) || empty($nombreParalelo)) {
                $this->resultados['mensajes'][] = "Error: Curso o paralelo vacío";
                return null;
            }
            
            $cursoParaleloId = DB::table('curso_paralelo as cp')
                ->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
                ->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
                ->where('c.nombre', $nombreCurso)
                ->where('p.nombre', $nombreParalelo)
                ->value('cp.idCursoParalelo');
            
            if (!$cursoParaleloId) {
                $this->resultados['mensajes'][] = "Error: No se encontró combinación curso '{$nombreCurso}' - paralelo '{$nombreParalelo}' en la base de datos";
            } else {
                $this->resultados['mensajes'][] = "Éxito: Encontrado curso-paralelo ID: {$cursoParaleloId}";
            }
            
            return $cursoParaleloId;
        } catch (\Exception $e) {
            $this->resultados['mensajes'][] = "Error al leer header: " . $e->getMessage();
            return null;
        }
    }


    private function createUser($nombres, $apellidos, $email, $fechaNacimiento, $genero)
    {
        // Separar apellidos en primer y segundo apellido
        $apellidosParts = explode(' ', trim($apellidos), 2);
        $primerApellido = $apellidosParts[0] ?? '';
        $segundoApellido = $apellidosParts[1] ?? '';

        // Crear nuevo usuario (solo campos que existen en la tabla usuario)
        $user = User::create([
            'nombres' => $nombres,
            'primerApellido' => $primerApellido,
            'segundoApellido' => $segundoApellido,
            'email' => $email,
            'rol' => 'estudiante',
            'password' => bcrypt('123456'), // Password temporal
        ]);

        return $user->id;
    }

    private function createStudent($userId, $cursoParaleloId)
    {
        // Crear registro de estudiante
        Estudiante::create([
            'idUser' => $userId,
            'idCursoParalelo' => $cursoParaleloId
        ]);

        // No crear puntaje inicial - se creará cuando el estudiante haga su primer depósito
    }

    

    private function parseDate($dateValue)
    {
        if (empty($dateValue)) {
            return null;
        }

        // Si es un número (fecha de Excel)
        if (is_numeric($dateValue)) {
            try {
                return Date::excelToDateTimeObject($dateValue)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // Si es una cadena, intentar parsearla
        try {
            return date('Y-m-d', strtotime($dateValue));
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizeGender($gender)
    {
        $gender = strtoupper(trim($gender));
        
        if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE', 'H'])) {
            return 'M';
        }
        
        if (in_array($gender, ['F', 'FEMENINO', 'MUJER'])) {
            return 'F';
        }
        
        return $gender; // Devolver tal como está para validación posterior
    }
}