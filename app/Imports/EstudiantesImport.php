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
        'insertados' => 0,
        'actualizados' => 0,
        'omitidos' => 0,
        'errores' => [],
        'mensajes' => [],
        'curso_nombre' => null,
        'paralelo_nombre' => null
    ];
    
    private $filePath;
    protected $cursoParalelo = null;
    private $cursoParaleloId = null;

    public function import($filePath)
    {
        $this->filePath = $filePath;
        
        try {
            $this->cursoParaleloId = $this->getCursoParaleloFromHeader();
            
            if (!$this->cursoParaleloId) {
                // Guardar historial incluso si no se pudo obtener curso-paralelo
                $this->guardarHistorial();
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
                            ->where('idCursoParalelo', $this->cursoParaleloId)
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
                                'idCursoParalelo' => $this->cursoParaleloId
                            ]);
                            $this->resultados['actualizados']++;
                            $this->resultados['mensajes'][] = "Fila {$rowIndex}: Estudiante movido de curso anterior al nuevo curso: {$email}";
                        } else {
                            // Crear nuevo registro de estudiante
                            $this->createStudent($existingUser->id, $this->cursoParaleloId);
                            $this->resultados['actualizados']++;
                            $this->resultados['mensajes'][] = "Fila {$rowIndex}: Usuario existente agregado al curso: {$email}";
                        }
                    } else {
                        // Crear nuevo usuario PRIMERO
                        $userId = $this->createUser($nombres, $apellidos, $email, $fechaNacimiento, $genero);
                        
                        // Luego crear el estudiante
                        $this->createStudent($userId, $this->cursoParaleloId);
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

            // Remover el guardado aquí - se hará al final siempre

        } catch (\Exception $e) {
            $this->resultados['errores'][] = [
                'fila' => 0,
                'mensaje' => 'Error al leer el archivo: ' . $e->getMessage()
            ];
        }

        // Guardar historial SIEMPRE, incluso si hay errores
        $this->guardarHistorial();

        return $this->resultados;
    }

    private function getCursoParaleloFromHeader()
    {
        try {
            $spreadsheet = IOFactory::load($this->filePath);
            // Intentar seleccionar la hoja por nombre de la plantilla
            $worksheet = $spreadsheet->getSheetByName('Plantilla Estudiantes');
            if (!$worksheet) {
                // Fallback al active sheet o primera hoja
                $worksheet = $spreadsheet->getActiveSheet() ?: $spreadsheet->getSheet(0);
            }
            
            // Debug: Leer valores exactos
            $cursoCell = $worksheet->getCell('B2')->getCalculatedValue();
            $paraleloCell = $worksheet->getCell('E2')->getCalculatedValue();
            
            \Log::info('Valores leídos del Excel:', [
                'B2_raw' => $cursoCell,
                'E2_raw' => $paraleloCell
            ]);
            
            $nombreCurso = trim((string)($cursoCell ?? ''));
            $nombreParalelo = trim((string)($paraleloCell ?? ''));

            // Limpiar texto de placeholder y prefijos
            $nombreCurso = preg_replace('/^\s*(curso\s*[:\-]?\s*)?(\[.*?\])?\s*/i', '', $nombreCurso);
            $nombreParalelo = preg_replace('/^\s*(paralelo\s*[:\-]?\s*)?(\[.*?\])?\s*/i', '', $nombreParalelo);

            \Log::info('Valores normalizados:', [
                'curso' => $nombreCurso,
                'paralelo' => $nombreParalelo
            ]);

            // Verificar si contienen texto de placeholder
            if (strpos($nombreCurso, '[Escriba aquí') !== false || strpos($nombreParalelo, '[Escriba aquí') !== false) {
                $this->resultados['errores'][] = [
                    'fila' => 2,
                    'mensaje' => 'Reemplaza el texto de ejemplo en B2 y E2 por el Curso y el Paralelo reales.'
                ];
                return null;
            }
            
            // Validaciones específicas por campo
            if (empty($nombreCurso)) {
                $this->resultados['errores'][] = [
                    'fila' => 2,
                    'mensaje' => 'Falta escribir el Curso en la celda B2.'
                ];
            }
            if (empty($nombreParalelo)) {
                $this->resultados['errores'][] = [
                    'fila' => 2,
                    'mensaje' => 'Falta escribir el Paralelo en la celda E2.'
                ];
            }
            if (!empty($this->resultados['errores'])) {
                return null;
            }
            
            // Normalizar (más tolerante: símbolos, acentos y ordinales)
            $cursoExcel = $this->normalizeCursoNombre($nombreCurso);
            $paraleloExcel = $this->normalizeParaleloNombre($nombreParalelo);

            // Buscar Curso por normalización en PHP (robusto ante variaciones)
            $cursoRegistros = DB::table('curso')->select('idCurso', 'nombre')->get();
            $cursoId = null;
            foreach ($cursoRegistros as $c) {
                if ($this->normalizeCursoNombre($c->nombre) === $cursoExcel) {
                    $cursoId = $c->idCurso;
                    break;
                }
            }
            if (!$cursoId) {
                $this->resultados['errores'][] = [
                    'fila' => 2,
                    'mensaje' => "El Curso '{$nombreCurso}' no existe. Revisa que esté escrito igual que en el sistema."
                ];
            }

            // Buscar Paralelo por normalización en PHP
            $paraleloRegistros = DB::table('paralelo')->select('idParalelo', 'nombre')->get();
            $paraleloId = null;
            foreach ($paraleloRegistros as $p) {
                if ($this->normalizeParaleloNombre($p->nombre) === $paraleloExcel) {
                    $paraleloId = $p->idParalelo;
                    break;
                }
            }
            if (!$paraleloId) {
                $this->resultados['errores'][] = [
                    'fila' => 2,
                    'mensaje' => "El Paralelo '{$nombreParalelo}' no existe. Revisa que esté escrito igual que en el sistema."
                ];
            }
            if (!empty($this->resultados['errores'])) {
                return null;
            }

            // Validar combinación curso-paralelo
            $cursoParaleloId = DB::table('curso_paralelo')
                ->where('idCurso', $cursoId)
                ->where('idParalelo', $paraleloId)
                ->value('idCursoParalelo');
            
            if (!$cursoParaleloId) {
                $this->resultados['errores'][] = [
                    'fila' => 2,
                    'mensaje' => "No encontramos ese Curso con ese Paralelo. Verifica que ambos existan y correspondan."
                ];
                return null;
            } else {
                $this->resultados['mensajes'][] = "Listo: se encontró el curso y paralelo para asignar a todos los estudiantes.";
                // Guardar los nombres de curso y paralelo en los resultados
                $this->resultados['curso_nombre'] = $nombreCurso;
                $this->resultados['paralelo_nombre'] = $nombreParalelo;
                
                // Debug: verificar que se están guardando
                \Log::info('Guardando nombres en resultados:', [
                    'curso_nombre' => $nombreCurso,
                    'paralelo_nombre' => $nombreParalelo
                ]);
            }
            
            return $cursoParaleloId;

        } catch (\Exception $e) {
            \Log::error('Error en getCursoParaleloFromHeader:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->resultados['errores'][] = [
                'fila' => 2,
                'mensaje' => 'No se pudo leer el Curso (B2) y Paralelo (E2). Asegúrate de que la hoja sea "Plantilla Estudiantes" y que B2/E2 tengan valores. Detalle: ' . $e->getMessage()
            ];
            return null;
        }
    }

    // ===================== Helpers de Normalización =====================
    private function normalizeString($str)
    {
        $str = (string)$str;
        // Reemplazar espacios duros NBSP y otros espacios Unicode comunes
        $unicodeSpaces = ["\xC2\xA0", "\xE2\x80\x80", "\xE2\x80\x81", "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84", "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87", "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A", "\xE2\x80\xAF", "\xE2\x81\x9F", "\xE3\x80\x80"];
        $str = str_replace($unicodeSpaces, ' ', $str);
        // Eliminar caracteres de control invisibles
        $str = preg_replace('/[\x00-\x1F\x7F]/u', '', $str);
        $str = mb_strtoupper(trim($str));
        // Reemplazar caracteres comunes
        $replacements = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
            'Ñ' => 'N', 'º' => '', '°' => '', '.' => '', ',' => '',
        ];
        $str = strtr($str, $replacements);
        // Colapsar espacios múltiples
        $str = preg_replace('/\s+/', ' ', $str);
        return $str;
    }

    private function normalizeCursoNombre($curso)
    {
        $s = $this->normalizeString($curso);
        // Mapeo de palabras a ordinales estándar
        $map = [
            'PRIMERO' => '1RO', 'SEGUNDO' => '2DO', 'TERCERO' => '3RO',
            'CUARTO' => '4TO', 'QUINTO' => '5TO', 'SEXTO' => '6TO',
        ];
        if (isset($map[$s])) return $map[$s];

        // Detectar números solos y formatear
        if (preg_match('/^(1|2|3|4|5|6)\s*(RO|DO|RO|TO)?$/', $s, $m)) {
            $n = (int)$m[1];
            return [1=>'1RO',2=>'2DO',3=>'3RO',4=>'4TO',5=>'5TO',6=>'6TO'][$n];
        }

        // Reemplazar formas como 1º, 2º, 5°, etc.
        if (preg_match('/^(1|2|3|4|5|6)$/', $s)) {
            $n = (int)$s;
            return [1=>'1RO',2=>'2DO',3=>'3RO',4=>'4TO',5=>'5TO',6=>'6TO'][$n];
        }

        // Si ya viene como 1RO/2DO/3RO/4TO/5TO/6TO o similar
        $s = str_replace(['  '], ' ', $s);
        return $s;
    }

    private function normalizeParaleloNombre($paralelo)
    {
        $s = $this->normalizeString($paralelo);
        // Quedarnos con la primera letra (A-Z)
        if (preg_match('/^[A-Z]/', $s, $m)) {
            return $m[0];
        }
        return $s;
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

    public function getCursoParalelo()
    {
        return $this->cursoParalelo;
    }

    protected function findOrCreateCursoParalelo($cursoNombre, $paraleloNombre)
    {
        // ...existing code...
        $this->cursoParalelo = $cursoParalelo;
        return $cursoParalelo;
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

    /**
     * Guardar historial de importación SIEMPRE, incluso si hay errores
     */
    private function guardarHistorial()
    {
        try {
            $cursoParaleloIdToSave = $this->cursoParaleloId;
            
            // Si no se pudo obtener curso-paralelo, usar el primero disponible para registrar el error
            if (!$cursoParaleloIdToSave) {
                $cursoParaleloIdToSave = DB::table('curso_paralelo')->first()->idCursoParalelo ?? null;
            }
            
            if ($cursoParaleloIdToSave) {
                \App\Models\HistorialImportacion::create([
                    'id_curso_paralelo' => $cursoParaleloIdToSave,
                    'insertados' => $this->resultados['insertados'],
                    'actualizados' => $this->resultados['actualizados'],
                    'omitidos' => $this->resultados['omitidos'],
                    'errores_count' => count($this->resultados['errores'])
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error al guardar historial de importación: ' . $e->getMessage());
        }
    }
}