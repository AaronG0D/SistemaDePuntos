<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CredentialPdfService
{
    private $qrService;

    public function __construct(QrGeneratorService $qrService = null)
    {
        $this->qrService = $qrService ?: app(QrGeneratorService::class);
    }

    /**
     * Generar PDF simple de QRs para estudiantes usando DomPDF
     */
    public function generateSimpleQrPdf($students, $filename = 'qr_estudiantes.pdf')
    {
        try {
            // Crear directorio temporal si no existe
            $tempDir = storage_path('app/temp/');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $filePath = $tempDir . $filename;

            // Obtener nombre del curso (del primer estudiante)
            $cursoNombre = 'Todos los Cursos';
            if (!empty($students) && isset($students[0]['curso_nombre'])) {
                $cursoNombre = $students[0]['curso_nombre'] . ' - ' . $students[0]['paralelo_nombre'];
            }

            // Generar QRs para todos los estudiantes
            $studentsWithQr = [];
            foreach ($students as $student) {
                $qrResult = $this->qrService->generateQrWithLogo($student);
                if ($qrResult['success']) {
                    $studentsWithQr[] = [
                        'student' => $student,
                        'qr_url' => $qrResult['qr_url'], // Data URL base64
                        'nombre_completo' => trim(($student['nombres'] ?? '') . ' ' . ($student['primerApellido'] ?? '') . ' ' . ($student['segundoApellido'] ?? '')),
                        'curso' => ($student['curso_nombre'] ?? '') . ' - ' . ($student['paralelo_nombre'] ?? ''),
                        'codigo' => $student['qr_codigo'] ?? $student['id']
                    ];
                }
            }

            // Crear HTML para el PDF
            $html = $this->generateQrHtml($studentsWithQr, $cursoNombre);

            // Generar PDF usando DomPDF
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            
            // Guardar PDF temporalmente
            $pdf->save($filePath);

            return [
                'success' => true,
                'file_path' => $filePath,
                'filename' => $filename,
                'total_qrs' => count($studentsWithQr)
            ];

        } catch (\Exception $e) {
            \Log::error('Error generando PDF: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error generando PDF: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generar HTML para el PDF con QRs (9 por página en grid 3x3)
     */
    private function generateQrHtml($studentsWithQr, $cursoNombre = 'Estudiantes')
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Códigos QR - ' . htmlspecialchars($cursoNombre) . '</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: Arial, sans-serif;
                    padding: 15px;
                }
                .page-header {
                    text-align: center;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #333;
                }
                .page-header h1 {
                    font-size: 18px;
                    margin-bottom: 5px;
                }
                .page-header p {
                    font-size: 12px;
                    color: #666;
                }
                .qr-grid {
                    display: table;
                    width: 100%;
                    border-collapse: collapse;
                }
                .qr-row {
                    display: table-row;
                }
                .qr-item {
                    display: table-cell;
                    width: 33.33%;
                    text-align: center;
                    border: 1px solid #ddd;
                    padding: 8px;
                    vertical-align: top;
                }
                .qr-code {
                    width: 120px;
                    height: 120px;
                    margin: 0 auto 5px;
                    display: block;
                }
                .student-name {
                    font-weight: bold;
                    font-size: 9px;
                    margin-bottom: 3px;
                    line-height: 1.2;
                    min-height: 20px;
                }
                .student-code {
                    font-size: 8px;
                    color: #666;
                }
                .page-break {
                    page-break-before: always;
                }
            </style>
        </head>
        <body>';

        $count = 0;
        $totalPages = ceil(count($studentsWithQr) / 9);
        $currentPage = 1;

        foreach ($studentsWithQr as $index => $item) {
            // Nueva página cada 9 QRs
            if ($count % 9 === 0) {
                if ($count > 0) {
                    $html .= '</div></div>'; // Cerrar grid y body anterior
                    $html .= '<div class="page-break"></div>'; // Salto de página
                    $currentPage++;
                }
                
                // Header de la página
                $html .= '
                <div class="page-header">
                    <h1>Códigos QR - ' . htmlspecialchars($cursoNombre) . '</h1>
                    <p>Página ' . $currentPage . ' de ' . $totalPages . '</p>
                </div>
                <div class="qr-grid">';
            }

            // Nueva fila cada 3 QRs
            if ($count % 3 === 0) {
                $html .= '<div class="qr-row">';
            }

            $html .= '
                <div class="qr-item">
                    <img src="' . $item['qr_url'] . '" alt="QR Code" class="qr-code">
                    <div class="student-name">' . htmlspecialchars($item['nombre_completo']) . '</div>
                    <div class="student-code">' . htmlspecialchars($item['codigo']) . '</div>
                </div>';

            // Cerrar fila cada 3 QRs
            if (($count + 1) % 3 === 0 || $index === count($studentsWithQr) - 1) {
                // Rellenar celdas vacías si es la última fila y no está completa
                $remaining = 3 - (($count % 3) + 1);
                for ($i = 0; $i < $remaining; $i++) {
                    $html .= '<div class="qr-item" style="border: none;"></div>';
                }
                $html .= '</div>'; // Cerrar fila
            }

            $count++;
        }

        $html .= '
                </div>
            </body>
        </html>';

        return $html;
    }

    /**
     * Limpiar archivos temporales
     */
    public function cleanupTempFiles()
    {
        $tempDir = storage_path('app/temp/');
        if (is_dir($tempDir)) {
            $files = glob($tempDir . '*.pdf');
            foreach ($files as $file) {
                if (filemtime($file) < time() - 3600) { // Eliminar archivos de más de 1 hora
                    unlink($file);
                }
            }
        }
    }
}