<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Logo\Logo;
use Illuminate\Support\Facades\Log;


class QrGeneratorService
{
    private $logoPath;
    private $logoSizeRatio = 0.13; // 13% del tamaño del QR (logo más pequeño para credencial)
    private $qrSize = 600; // Tamaño del QR en píxeles (suficiente para impresión en credencial)

    public function __construct()
    {
        $this->logoPath = public_path('img/LogoDario.png');
    }

  public function generateQrWithLogo($userData, $filename = null)
{
    try {
        $qrData = $userData['qr_codigo'] ?? $userData['id'];

        $qrCode = new QrCode(
            data: $qrData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $this->qrSize,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $writer = new PngWriter();

        // ✅ Logo actualizado a la sintaxis de v6
        $logo = null;
        if (file_exists($this->logoPath)) {
            $logoSize = (int)($this->qrSize * $this->logoSizeRatio);
            $logo = new Logo(
                path: $this->logoPath,
                resizeToWidth: $logoSize
            );
        }

        $result = $writer->write($qrCode, $logo);

        $qrString = $result->getString();
        $qrBase64 = base64_encode($qrString);
        $qrDataUrl = 'data:image/png;base64,' . $qrBase64;

        if (!$filename) {
            $nombreLimpio = $this->normalizarNombre(
                $userData['nombres'] . ' ' . ($userData['primerApellido'] ?? '') . ' ' . ($userData['segundoApellido'] ?? '')
            );
            $filename = 'qr_' . $nombreLimpio . '.png';
        }

        return [
            'success' => true,
            'qr_url' => $qrDataUrl,
            'qr_string' => $qrString,
            'filename' => $filename,
            'qr_data' => $qrData
        ];
    } catch (\Exception $e) {
        Log::error('Error generando QR: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error: ' . $e->getMessage()
        ];
    }
}

    private function normalizarNombre($nombre)
    {
        $nombre = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombre);
        $nombre = strtolower(trim($nombre));
        $nombre = preg_replace('/\s+/', '_', $nombre);
        $nombre = preg_replace('/[^a-z0-9_]/', '', $nombre);
        return $nombre;
    }

    public function generateBatchQr($users)
    {
        $results = [];
        foreach ($users as $user) {
            $result = $this->generateQrWithLogo($user);
            $results[] = array_merge($result, ['user_id' => $user['id']]);
        }
        return $results;
    }





    /**
     * Limpiar archivos QR antiguos
     */
    public function cleanupOldQrFiles($olderThanDays = 7)
    {
        $qrDir = storage_path('app/public/qr_codes/');
        
        if (!is_dir($qrDir)) {
            return;
        }

        $files = glob($qrDir . '*.png');
        $cutoffTime = time() - ($olderThanDays * 24 * 60 * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
}