<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QrGeneratorService
{
    private $logoPath;
    private $logoSizeRatio = 0.2;
    private $qrSize = 200; // Tamaño base del QR en píxeles

    public function __construct()
    {
        $this->logoPath = base_path('public/img/LogoDario.png');
    }

    /**
     * Generar código QR con logo para un usuario
     */
    public function generateQrWithLogo($userData, $filename = null)
    {
        try {
            if (!$filename) {
                $filename = 'qr_' . Str::slug($userData['nombres'] . '_' . $userData['primerApellido']) . '_' . time() . '.png';
            }

            $filePath = storage_path('app/public/qr_codes/' . $filename);
            
            // Crear directorio si no existe
            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            // Crear string simple para el QR
            $qrString = $userData['id'] . '|' . $userData['nombres'] . '|' . $userData['primerApellido'] . '|' . $userData['email'];
            
            // Generar QR usando la API de QR Server
            $qrUrl = $this->buildQrUrl($qrString);
            
            // Descargar y procesar la imagen
            $qrImage = $this->downloadAndProcessQr($qrUrl);
            
            if ($qrImage) {
                // Agregar logo si existe
                if (file_exists($this->logoPath)) {
                    $qrImage = $this->addLogoToQr($qrImage);
                }
                
                // Guardar imagen final
                imagepng($qrImage, $filePath);
                imagedestroy($qrImage);
                
                return [
                    'success' => true,
                    'file_path' => $filePath,
                    'public_url' => Storage::url('qr_codes/' . $filename),
                    'filename' => $filename
                ];
            }

            return [
                'success' => false,
                'error' => 'No se pudo generar el código QR'
            ];
        } catch (\Exception $e) {
            \Log::error('Error generando QR: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Construir URL para generar QR
     */
    private function buildQrUrl($qrString)
    {
        $encodedData = urlencode($qrString);
        
        return 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
            'size' => $this->qrSize . 'x' . $this->qrSize,
            'data' => $encodedData,
            'format' => 'png',
            'charset' => 'utf-8',
            'margin' => 2,
            'color' => '000000',
            'bgcolor' => 'FFFFFF',
            'ecc' => 'H'
        ]);
    }

    /**
     * Descargar y procesar imagen QR
     */
    private function downloadAndProcessQr($url)
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; QR Generator)'
            ]
        ]);

        $imageData = @file_get_contents($url, false, $context);
        
        if ($imageData === false) {
            return null;
        }

        $image = imagecreatefromstring($imageData);
        
        if ($image === false) {
            return null;
        }

        return $image;
    }

    /**
     * Agregar logo al QR
     */
    private function addLogoToQr($qrImage)
    {
        if (!file_exists($this->logoPath)) {
            return $qrImage;
        }

        $logo = imagecreatefrompng($this->logoPath);
        
        if ($logo === false) {
            return $qrImage;
        }

        // Calcular tamaño del logo (más pequeño para mejor detección)
        $logoSize = intval($this->qrSize * 0.15); // Reducido de 0.2 a 0.15
        
        // Redimensionar logo manteniendo proporción cuadrada
        $resizedLogo = imagecreatetruecolor($logoSize, $logoSize);
        imagealphablending($resizedLogo, false);
        imagesavealpha($resizedLogo, true);
        
        // Crear fondo blanco para el logo
        $white = imagecolorallocate($resizedLogo, 255, 255, 255);
        imagefill($resizedLogo, 0, 0, $white);
        
        imagecopyresampled(
            $resizedLogo, $logo,
            0, 0, 0, 0,
            $logoSize, $logoSize,
            imagesx($logo), imagesy($logo)
        );

        // Posicionar logo en el centro del QR
        $logoX = ($this->qrSize - $logoSize) / 2;
        $logoY = ($this->qrSize - $logoSize) / 2;

        // Crear un marco blanco alrededor del logo para mejor contraste
        $margin = 2;
        $frameSize = $logoSize + ($margin * 2);
        $frameX = $logoX - $margin;
        $frameY = $logoY - $margin;
        
        // Dibujar marco blanco
        imagefilledrectangle($qrImage, $frameX, $frameY, $frameX + $frameSize, $frameY + $frameSize, $white);

        // Pegar logo en el QR
        imagecopy($qrImage, $resizedLogo, $logoX, $logoY, 0, 0, $logoSize, $logoSize);

        // Limpiar memoria
        imagedestroy($logo);
        imagedestroy($resizedLogo);

        return $qrImage;
    }

    /**
     * Generar QR para múltiples usuarios (batch)
     */
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