<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PlantillaEstudiantesExport
{
    public function generateTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla Estudiantes');

        // Configurar página
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

        // Título principal con estilo profesional
        $sheet->setCellValue('A1', '🎓 PLANTILLA PARA IMPORTAR ESTUDIANTES');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);

        // Información del curso y paralelo con estilo
        $sheet->setCellValue('A2', 'CURSO:');
        $sheet->setCellValue('B2', '[Escriba aquí: 1ro, 2do, 3ro, 4to, 5to, 6to]');
        $sheet->setCellValue('D2', 'PARALELO:');
        $sheet->setCellValue('E2', '[Escriba aquí: A, B, C, D]');
        
        $sheet->getStyle('A2:E2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ]);

        // Sección de instrucciones con estilo
        $sheet->setCellValue('A4', '📋 INSTRUCCIONES IMPORTANTES:');
        $sheet->mergeCells('A4:E4');
        $sheet->getStyle('A4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '059669']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);

        $instrucciones = [
            '1. ✅ Complete CURSO y PARALELO en las celdas amarillas arriba',
            '2. 📝 Llene los datos de estudiantes desde la fila 13',
            '3. 📧 Cada email debe ser único en el sistema',
            '4. ⚠️ NO modifique los encabezados de las columnas',
            '5. 💾 Guarde el archivo y súbalo al sistema'
        ];

        $row = 5;
        foreach ($instrucciones as $instruccion) {
            $sheet->setCellValue('A' . $row, $instruccion);
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['size' => 10],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
            ]);
            $row++;
        }

        // Encabezados con estilo profesional
        $headers = ['NOMBRES', 'APELLIDOS', 'EMAIL'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '12', $header);
            $sheet->getStyle($col . '12')->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
            ]);
            $col++;
        }

        // Ejemplos con colores alternados
        $ejemplos = [
            ['Juan Carlos', 'Pérez González', 'juan.perez@email.com'],
            ['María Elena', 'García López', 'maria.garcia@email.com'],
            ['Carlos Alberto', 'Rodríguez Martínez', 'carlos.rodriguez@email.com']
        ];

        $row = 13;
        foreach ($ejemplos as $i => $ejemplo) {
            $col = 'A';
            foreach ($ejemplo as $valor) {
                $sheet->setCellValue($col . $row, $valor);
                $bgColor = ($i % 2 == 0) ? 'F8FAFC' : 'FFFFFF';
                $sheet->getStyle($col . $row)->applyFromArray([
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
                ]);
                $col++;
            }
            $row++;
        }

        // Nota importante
        $sheet->setCellValue('A16', '⚠️ NOTA: Los datos anteriores son ejemplos. Elimine estas filas antes de importar.');
        $sheet->mergeCells('A16:E16');
        $sheet->getStyle('A16')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626'], 'italic' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);

        // Ajustar anchos de columna
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);

        // Aplicar bordes a la tabla principal
        $sheet->getStyle('A12:C15')->applyFromArray([
            'borders' => [
                'outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => '374151']]
            ]
        ]);

        return $spreadsheet;
    }

    public function download($filename = 'plantilla_estudiantes.xlsx')
    {
        $spreadsheet = $this->generateTemplate();
        
        $tempFile = tempnam(sys_get_temp_dir(), 'plantilla_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
