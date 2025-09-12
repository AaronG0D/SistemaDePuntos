<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class EstudiantesMateriaExport
{
    protected $estudiantes;
    protected $curso;
    protected $materia;
    protected $periodo;
    protected $docente;
    protected $estadisticas;

    public function __construct($estudiantes, $curso, $materia, $periodo, $docente, $estadisticas = null)
    {
        $this->estudiantes = $estudiantes;
        $this->curso = $curso;
        $this->materia = $materia;
        $this->periodo = $periodo;
        $this->docente = $docente;
        $this->estadisticas = $estadisticas;
    }

    public function generateExcel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte Estudiantes');

        // Configurar página
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageMargins()->setTop(0.5);
        $sheet->getPageMargins()->setBottom(0.5);
        $sheet->getPageMargins()->setLeft(0.5);
        $sheet->getPageMargins()->setRight(0.5);

        // Logo (si existe)
        $this->addLogo($sheet);

        // Título principal
        $sheet->setCellValue('A1', 'REPORTE DE ESTUDIANTES POR MATERIA');
        $sheet->mergeCells('A1:H1');
        $this->styleTitle($sheet, 'A1');

        // Subtítulo con información institucional
        $sheet->setCellValue('A2', 'Sistema de Gestión de Puntos Ecológicos');
        $sheet->mergeCells('A2:H2');
        $this->styleSubtitle($sheet, 'A2');

        // Información del reporte con estilo elegante
        $this->addReportInfo($sheet);

        // Estadísticas visuales
        $this->addStatistics($sheet);

        // Encabezados de tabla con gradiente
        $this->addTableHeaders($sheet);

        // Datos de estudiantes con formato condicional
        $this->addStudentData($sheet);

        // Aplicar estilos finales
        $this->applyFinalStyling($sheet);

        // Crear gráfico de rendimiento
        $this->addPerformanceChart($sheet, $spreadsheet);

        return $spreadsheet;
    }

    private function addLogo($sheet)
    {
        $logoPath = public_path('img/LogoDario.png');
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo Institución');
            $drawing->setPath($logoPath);
            $drawing->setHeight(80);
            $drawing->setCoordinates('I1');
            $drawing->setWorksheet($sheet);
        }
    }

    private function styleTitle($sheet, $cell)
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 20,
                'color' => ['rgb' => 'FFFFFF'],
                'name' => 'Calibri'
            ],
            'fill' => [
                'fillType' => Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => ['rgb' => '1E40AF'],
                'endColor' => ['rgb' => '3B82F6']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);
    }

    private function styleSubtitle($sheet, $cell)
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font' => [
                'italic' => true,
                'size' => 12,
                'color' => ['rgb' => '6B7280']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);
    }

    private function addReportInfo($sheet)
    {
        // Información en tarjetas elegantes
        $infoData = [
            ['📚 Curso:', $this->curso->curso ?? 'N/A', '🎯 Paralelo:', $this->curso->paralelo ?? 'N/A'],
            ['📖 Materia:', $this->materia ?? 'N/A', '📅 Período:', $this->periodo->nombre ?? 'N/A'],
            ['🔢 Código:', $this->periodo->codigo ?? 'N/A', '👨‍🏫 Docente:', $this->docente->nombres ?? 'N/A'],
            ['⏰ Generado:', now()->format('d/m/Y H:i:s'), '👥 Estudiantes:', count($this->estudiantes)]
        ];

        $row = 4;
        foreach ($infoData as $info) {
            $sheet->fromArray($info, null, 'A' . $row);
            
            // Estilo para etiquetas
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '1F2937'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']]
            ]);
            $sheet->getStyle('C' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '1F2937'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']]
            ]);
            
            // Estilo para valores
            $sheet->getStyle('B' . $row)->applyFromArray([
                'font' => ['color' => ['rgb' => '374151'], 'size' => 11]
            ]);
            $sheet->getStyle('D' . $row)->applyFromArray([
                'font' => ['color' => ['rgb' => '374151'], 'size' => 11]
            ]);
            
            $row++;
        }
    }

    private function addStatistics($sheet)
    {
        if (!$this->estadisticas) return;

        // Título de estadísticas
        $sheet->setCellValue('F4', '📊 ESTADÍSTICAS DEL CURSO');
        $sheet->mergeCells('F4:H4');
        $sheet->getStyle('F4:H4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $statsData = [
            ['💯 Total Puntos:', number_format($this->estadisticas['total_puntos'] ?? 0)],
            ['📈 Promedio:', number_format($this->estadisticas['promedio'] ?? 0, 2)],
            ['🏆 Máximo:', number_format($this->estadisticas['maximo'] ?? 0)],
            ['📉 Mínimo:', number_format($this->estadisticas['minimo'] ?? 0)]
        ];

        $statRow = 5;
        foreach ($statsData as $stat) {
            $sheet->setCellValue('F' . $statRow, $stat[0]);
            $sheet->setCellValue('G' . $statRow, $stat[1]);
            
            $sheet->getStyle('F' . $statRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '065F46']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']]
            ]);
            $sheet->getStyle('G' . $statRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '059669']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ]);
            
            $statRow++;
        }
    }

    private function addTableHeaders($sheet)
    {
        $headers = ['N°', 'ID', 'Apellidos', 'Nombres', 'Registros', 'Puntos', 'Calificación', 'Estado'];
        $sheet->fromArray($headers, null, 'A9');

        // Estilo de encabezados con gradiente
        $sheet->getStyle('A9:H9')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => ['rgb' => '1E40AF'],
                'endColor' => ['rgb' => '3730A3']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '1E40AF']
                ]
            ]
        ]);

        // Ajustar anchos de columna
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);

        $sheet->getRowDimension(9)->setRowHeight(30);
    }

    private function addStudentData($sheet)
    {
        $row = 10;
        foreach ($this->estudiantes as $index => $estudiante) {
            $puntos = $estudiante->puntos_atribuidos ?? 0;
            $calificacion = $this->getCalificacionCualitativa($puntos);
            $estado = $this->getEstadoEmoji($puntos);

            $data = [
                $index + 1,
                $estudiante->idUser,
                trim(($estudiante->primerApellido ?? '') . ' ' . ($estudiante->segundoApellido ?? '')),
                $estudiante->nombres,
                $estudiante->registros ?? 0,
                $puntos,
                $calificacion,
                $estado
            ];

            $sheet->fromArray($data, null, 'A' . $row);

            // Formato condicional por rendimiento
            $this->applyConditionalFormatting($sheet, $row, $puntos);

            // Alternar colores de fila
            if (($row - 10) % 2 == 0) {
                $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']]
                ]);
            }

            $row++;
        }
    }

    private function applyConditionalFormatting($sheet, $row, $puntos)
    {
        $color = '6B7280'; // Gris por defecto
        $bgColor = 'F9FAFB';

        if ($puntos >= 90) {
            $color = '059669'; // Verde
            $bgColor = 'ECFDF5';
        } elseif ($puntos >= 70) {
            $color = 'D97706'; // Amarillo
            $bgColor = 'FFFBEB';
        } elseif ($puntos >= 50) {
            $color = 'DC2626'; // Rojo
            $bgColor = 'FEF2F2';
        }

        // Aplicar color a la fila de puntos y calificación
        $sheet->getStyle('F' . $row . ':G' . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $color]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]]
        ]);

        // Centrar números
        $sheet->getStyle('A' . $row . ':B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E' . $row . ':H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function applyFinalStyling($sheet)
    {
        $highestRow = $sheet->getHighestRow();
        
        // Bordes para toda la tabla
        $sheet->getStyle('A9:H' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB']
                ]
            ]
        ]);

        // Altura de filas
        for ($i = 10; $i <= $highestRow; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(25);
        }
    }

    private function addPerformanceChart($sheet, $spreadsheet)
    {
        // Crear datos para el gráfico
        $chartData = $this->getChartData();
        
        if (empty($chartData)) return;

        // Agregar datos del gráfico en columnas ocultas
        $sheet->setCellValue('J1', 'Calificación');
        $sheet->setCellValue('K1', 'Cantidad');
        
        $chartRow = 2;
        foreach ($chartData as $label => $count) {
            $sheet->setCellValue('J' . $chartRow, $label);
            $sheet->setCellValue('K' . $chartRow, $count);
            $chartRow++;
        }

        // Ocultar columnas del gráfico
        $sheet->getColumnDimension('J')->setVisible(false);
        $sheet->getColumnDimension('K')->setVisible(false);
    }

    private function getChartData()
    {
        $data = ['Excelente' => 0, 'Bueno' => 0, 'Regular' => 0, 'Deficiente' => 0];
        
        foreach ($this->estudiantes as $estudiante) {
            $puntos = $estudiante->puntos_atribuidos ?? 0;
            $calificacion = $this->getCalificacionCualitativa($puntos);
            $data[$calificacion]++;
        }
        
        return array_filter($data); // Solo mostrar categorías con datos
    }

    private function getCalificacionCualitativa($puntos)
    {
        if ($puntos >= 90) return 'Excelente';
        if ($puntos >= 70) return 'Bueno';
        if ($puntos >= 50) return 'Regular';
        return 'Deficiente';
    }

    private function getEstadoEmoji($puntos)
    {
        if ($puntos >= 90) return '🏆 Destacado';
        if ($puntos >= 70) return '✅ Aprobado';
        if ($puntos >= 50) return '⚠️ Regular';
        return '❌ Necesita Mejora';
    }
}
