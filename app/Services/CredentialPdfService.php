<?php

namespace App\Services;

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
     * Generar PDF simple de QRs para estudiantes
     */
    public function generateSimpleQrPdf($students, $filename = 'qr_estudiantes.pdf')
    {
        try {
            // Crear directorio si no existe
            $pdfDir = storage_path('app/public/credentials/');
            if (!file_exists($pdfDir)) {
                mkdir($pdfDir, 0755, true);
            }

            $filePath = $pdfDir . $filename;

            // Verificar que TCPDF esté disponible
            if (!class_exists('TCPDF')) {
                return [
                    'success' => false,
                    'error' => 'TCPDF no está instalado. Ejecuta: composer require tecnickcom/tcpdf'
                ];
            }

            // Crear PDF usando TCPDF
            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            
            // Configuración básica
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(false);
            $pdf->SetFont('helvetica', '', 10);

            // Tamaño del QR en mm
            $qrSize = 40;
            $qrPerRow = 4;
            $spacing = 5;
            $margin = 10;

            $qrOnPage = 0;
            $totalQrs = 0;

            foreach ($students as $student) {
                // Nueva página si es necesario
                if ($qrOnPage == 0) {
                    $pdf->AddPage();
                }

                // Calcular posición
                $row = intval($qrOnPage / $qrPerRow);
                $col = $qrOnPage % $qrPerRow;
                
                $x = $margin + ($col * ($qrSize + $spacing));
                $y = $margin + ($row * ($qrSize + $spacing + 20)); // +20 para texto

                // Generar QR
                $qrResult = $this->qrService->generateQrWithLogo($student);
                
                if ($qrResult['success']) {
                    try {
                        // Dibujar QR
                        $pdf->Image($qrResult['file_path'], $x, $y, $qrSize, $qrSize, 'PNG');
                        
                        // Dibujar nombre del estudiante
                        $pdf->SetXY($x, $y + $qrSize + 2);
                        $pdf->SetFont('helvetica', '', 8);
                        $fullName = $student['nombres'] . ' ' . $student['primerApellido'];
                        $pdf->Cell($qrSize, 4, Str::limit($fullName, 20), 0, 0, 'C');
                        
                        // Dibujar curso
                        $pdf->SetXY($x, $y + $qrSize + 6);
                        $course = $student['curso_nombre'] . ' - ' . $student['paralelo_nombre'];
                        $pdf->Cell($qrSize, 4, Str::limit($course, 20), 0, 0, 'C');
                        
                        $totalQrs++;
                    } catch (\Exception $e) {
                        // Continuar con el siguiente estudiante si hay error con uno específico
                        continue;
                    }
                }

                $qrOnPage++;
                
                // Nueva página si se llena (4x5 = 20 QRs por página)
                if ($qrOnPage >= 20) {
                    $qrOnPage = 0;
                }
            }

            // Guardar PDF
            $pdf->Output($filePath, 'F');

            return [
                'success' => true,
                'file_path' => $filePath,
                'public_url' => Storage::url('credentials/' . $filename),
                'filename' => $filename,
                'total_qrs' => $totalQrs
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
     * Limpiar archivos temporales
     */
    public function cleanupTempFiles()
    {
        $tempDir = storage_path('app/public/qr_codes/');
        if (is_dir($tempDir)) {
            $files = glob($tempDir . '*.png');
            foreach ($files as $file) {
                if (filemtime($file) < time() - 3600) { // Eliminar archivos de más de 1 hora
                    unlink($file);
                }
            }
        }
    }
}