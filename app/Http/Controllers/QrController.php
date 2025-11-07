<?php

namespace App\Http\Controllers;

use App\Services\QrGeneratorService;
use App\Services\CredentialPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QrController extends Controller
{
    protected $qrService;
    protected $credentialPdfService;

    public function __construct(QrGeneratorService $qrService, CredentialPdfService $credentialPdfService)
    {
        $this->qrService = $qrService;
        $this->credentialPdfService = $credentialPdfService;
    }

    /**
     * Generar QR individual para un usuario (en memoria, sin guardar)
     */
    public function generateUserQr(Request $request, $userId)
    {
        try {
            $user = DB::table('usuario')
                ->where('id', $userId)
                ->first();

            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            // Convertir stdClass a array
            $userData = [
                'id' => $user->id,
                'nombres' => $user->nombres ?? '',
                'primerApellido' => $user->primerApellido ?? '',
                'segundoApellido' => $user->segundoApellido ?? '',
                'email' => $user->email ?? '',
                'qr_codigo' => $user->qr_codigo ?? null
            ];

            $result = $this->qrService->generateQrWithLogo($userData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'qr_url' => $result['qr_url'],  // Data URL base64
                    'filename' => $result['filename'],
                    'qr_data' => $result['qr_data']
                ]);
            }

            return response()->json(['error' => $result['error']], 500);
        } catch (\Exception $e) {
            \Log::error('Error en generateUserQr: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'error' => 'Error generando QR: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar QR para todos los estudiantes
     */
    public function generateAllStudentsQr(Request $request)
    {
        $students = DB::table('usuario')
            ->where('rol', 'estudiante')
            ->select('id', 'nombres', 'primerApellido', 'segundoApellido', 'email', 'qr_codigo')
            ->get()
            ->toArray();

        $result = $this->qrService->generateBatchQr($students);

        return response()->json([
            'success' => true,
            'generated_count' => count($result),
            'results' => $result
        ]);
    }

    /**
     * Generar QR para estudiantes de un curso específico
     */
    public function generateCourseStudentsQr(Request $request, $cursoParaleloId)
    {
        $students = DB::table('usuario as u')
            ->join('estudiante as e', 'u.id', '=', 'e.idUser')
            ->where('e.idCursoParalelo', $cursoParaleloId)
            ->where('u.rol', 'estudiante')
            ->select('u.id', 'u.nombres', 'u.primerApellido', 'u.segundoApellido', 'u.email', 'u.qr_codigo')
            ->get()
            ->toArray();

        $result = $this->qrService->generateBatchQr($students);

        return response()->json([
            'success' => true,
            'generated_count' => count($result),
            'curso_paralelo_id' => $cursoParaleloId,
            'results' => $result
        ]);
    }

    /**
     * Generar PDF de credenciales para estudiantes
     */
    public function generateCredentialsPdf(Request $request)
    {
        $cursoParaleloId = $request->get('curso_paralelo_id');
        $type = $request->get('type', 'credentials'); // 'credentials' o 'qr_only'
        
        if ($cursoParaleloId) {
            // Generar para curso específico
            $students = DB::table('usuario as u')
                ->join('estudiante as e', 'u.id', '=', 'e.idUser')
                ->join('curso_paralelo as cp', 'e.idCursoParalelo', '=', 'cp.idCursoParalelo')
                ->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
                ->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
                ->where('e.idCursoParalelo', $cursoParaleloId)
                ->where('u.rol', 'estudiante')
                ->select(
                    'u.id', 
                    'u.nombres', 
                    'u.primerApellido', 
                    'u.segundoApellido', 
                    'u.email', 
                    'u.qr_codigo',
                    'c.nombre as curso_nombre',
                    'p.nombre as paralelo_nombre'
                )
                ->get()
                ->toArray();

            $curso = $students[0]->curso_nombre ?? 'curso';
            $paralelo = $students[0]->paralelo_nombre ?? 'paralelo';
            $filename = $type . '_' . Str::slug($curso) . '_' . Str::slug($paralelo) . '_' . now()->format('Y-m-d') . '.pdf';
        } else {
            // Generar para todos los estudiantes
            $students = DB::table('usuario as u')
                ->join('estudiante as e', 'u.id', '=', 'e.idUser')
                ->join('curso_paralelo as cp', 'e.idCursoParalelo', '=', 'cp.idCursoParalelo')
                ->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
                ->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
                ->where('u.rol', 'estudiante')
                ->select(
                    'u.id', 
                    'u.nombres', 
                    'u.primerApellido', 
                    'u.segundoApellido', 
                    'u.email', 
                    'u.qr_codigo',
                    'c.nombre as curso_nombre',
                    'p.nombre as paralelo_nombre'
                )
                ->get()
                ->toArray();

            $filename = $type . '_estudiantes_completo_' . now()->format('Y-m-d') . '.pdf';
        }

        // Convertir objetos stdClass a arrays
        $studentsArray = array_map(function($student) {
            return (array) $student;
        }, $students);

        // Usar siempre el método simplificado
        $result = $this->credentialPdfService->generateSimpleQrPdf($studentsArray, $filename);

        return response()->json($result);
    }

    /**
     * Descargar PDF de credenciales
     */
    public function downloadCredentialsPdf(Request $request)
    {
        $result = $this->generateCredentialsPdf($request);
        $data = $result->getData(true);
        
        if ($data['success']) {
            // Generar PDF temporal y descargarlo sin guardar permanentemente
            $tempPath = tempnam(sys_get_temp_dir(), 'qr_credentials_') . '.pdf';
            
            // Copiar el archivo temporal
            copy($data['file_path'], $tempPath);
            
            return response()->download($tempPath, $data['filename'])->deleteFileAfterSend(true);
        }
        
        return response()->json(['error' => $data['error']], 500);
    }

    /**
     * Descargar QR individual (genera en memoria y descarga directamente)
     */
    public function downloadQr(Request $request, $userId)
    {
        try {
            $user = DB::table('usuario')
                ->where('id', $userId)
                ->first();

            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            // Convertir stdClass a array
            $userData = [
                'id' => $user->id,
                'nombres' => $user->nombres ?? '',
                'primerApellido' => $user->primerApellido ?? '',
                'segundoApellido' => $user->segundoApellido ?? '',
                'email' => $user->email ?? '',
                'qr_codigo' => $user->qr_codigo ?? null
            ];

            $result = $this->qrService->generateQrWithLogo($userData);

            if ($result['success']) {
                // Retornar el QR directamente desde memoria (sin guardar archivo)
                return response($result['qr_string'])
                    ->header('Content-Type', 'image/png')
                    ->header('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"');
            }

            return response()->json(['error' => $result['error']], 500);
        } catch (\Exception $e) {
            \Log::error('Error en downloadQr: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error descargando QR: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar archivos QR antiguos
     */
    public function cleanupQrFiles(Request $request)
    {
        $days = $request->get('days', 7);
        $this->qrService->cleanupOldQrFiles($days);

        return response()->json([
            'success' => true,
            'message' => "Archivos QR más antiguos que {$days} días han sido eliminados"
        ]);
    }

    /**
     * Obtener estadísticas de QR generados
     */
    public function getQrStats()
    {
        $qrDir = storage_path('app/public/qr_codes/');
        $totalFiles = 0;
        $totalSize = 0;

        if (is_dir($qrDir)) {
            $files = glob($qrDir . '*.png');
            $totalFiles = count($files);
            
            foreach ($files as $file) {
                $totalSize += filesize($file);
            }
        }

        return response()->json([
            'total_files' => $totalFiles,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'directory' => $qrDir
        ]);
    }

    /**
     * Obtener lista de estudiantes para el módulo QR
     */
    public function getStudentsForQr()
    {
        $students = DB::table('usuario as u')
            ->join('estudiante as e', 'u.id', '=', 'e.idUser')
            ->join('curso_paralelo as cp', 'e.idCursoParalelo', '=', 'cp.idCursoParalelo')
            ->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
            ->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
            ->where('u.rol', 'estudiante')
            ->select(
                'u.id',
                'u.nombres',
                'u.primerApellido',
                'u.segundoApellido',
                'u.email',
                'u.qr_codigo',
                'c.nombre as curso_nombre',
                'p.nombre as paralelo_nombre',
                'cp.idCursoParalelo as curso_paralelo_id'
            )
            ->orderBy('u.primerApellido')
            ->orderBy('u.segundoApellido')
            ->orderBy('u.nombres')
            ->get();

        return response()->json($students);
    }

    /**
     * Obtener lista de cursos para el módulo QR
     */
    public function getCoursesForQr()
    {
        $courses = DB::table('curso_paralelo as cp')
            ->join('curso as c', 'cp.idCurso', '=', 'c.idCurso')
            ->join('paralelo as p', 'cp.idParalelo', '=', 'p.idParalelo')
            ->select(
                'cp.idCursoParalelo as id',
                DB::raw("CONCAT(c.nombre, ' - ', p.nombre) as nombre")
            )
            ->orderBy('c.nombre')
            ->orderBy('p.nombre')
            ->get();

        return response()->json($courses);
    }

    /**
     * Activar QR de un estudiante (generar nuevo código)
     */
    public function activateQr($userId)
    {
        try {
            $user = DB::table('usuario')->where('id', $userId)->first();
            
            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Usuario no encontrado'], 404);
            }

            // Generar código QR basado en nombre y apellidos (igual que UserController)
            $fullName = trim(($user->nombres ?? '') . ' ' . ($user->primerApellido ?? '') . ' ' . ($user->segundoApellido ?? ''));
            $baseCode = Str::slug(preg_replace('/\s+/', ' ', $fullName));
            // Sufijo corto para evitar colisiones
            $newQrCode = $baseCode ? ($baseCode . '-' . Str::lower(Str::random(6))) : Str::lower(Str::random(8));
            
            // Actualizar el qr_codigo del usuario
            DB::table('usuario')
                ->where('id', $userId)
                ->update([
                    'qr_codigo' => $newQrCode,
                    'updated_at' => now()
                ]);

            \Log::info("QR activado para usuario ID: {$userId} con código: {$newQrCode}");

            return response()->json([
                'success' => true,
                'message' => 'QR activado correctamente',
                'qr_codigo' => $newQrCode
            ]);
        } catch (\Exception $e) {
            \Log::error("Error activando QR para usuario {$userId}: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'error' => 'Error al activar QR: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desactivar QR de un estudiante (establecer como vacío)
     */
    public function deactivateQr($userId)
    {
        try {
            $user = DB::table('usuario')->where('id', $userId)->first();
            
            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Usuario no encontrado'], 404);
            }

            // Establecer qr_codigo como null (más seguro que string vacío)
            DB::table('usuario')
                ->where('id', $userId)
                ->update([
                    'qr_codigo' => null,
                    'updated_at' => now()
                ]);

            \Log::info("QR desactivado para usuario ID: {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'QR desactivado correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error desactivando QR para usuario {$userId}: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'error' => 'Error al desactivar QR: ' . $e->getMessage()
            ], 500);
        }
    }
}
