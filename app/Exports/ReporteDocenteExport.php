<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReporteDocenteExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithEvents, ShouldAutoSize
{
    protected $data;
    protected $teacher;
    protected $filters;

    public function __construct($data, $teacher, $filters = [])
    {
        $this->data = $data;
        $this->teacher = $teacher;
        $this->filters = $filters;
    }

    public function collection()
    {
        // Crear filas vacías para el encabezado (filas 1-11)
        $emptyRows = collect(range(1, 11))->map(function() {
            return [
                '', '', '', '', '', '', '', '', ''
            ];
        });
        
        // Procesar los datos de asignaciones
        $assignmentsData = collect($this->data['assignments'])->map(function($assignment, $index) {
            // Convertir stdClass a array si es necesario
            if (is_object($assignment)) {
                $assignment = (array) $assignment;
            }
            
            // Verificar que las claves existan antes de acceder a ellas
            $estudiante = isset($assignment['estudiante']) ? 
                (is_object($assignment['estudiante']) ? (array) $assignment['estudiante'] : $assignment['estudiante']) : 
                [];
            $curso = isset($estudiante['curso']) ? 
                (is_object($estudiante['curso']) ? (array) $estudiante['curso'] : $estudiante['curso']) : 
                [];
            $paralelo = isset($estudiante['paralelo']) ? 
                (is_object($estudiante['paralelo']) ? (array) $estudiante['paralelo'] : $estudiante['paralelo']) : 
                [];
            $materia = isset($assignment['materia']) ? 
                (is_object($assignment['materia']) ? (array) $assignment['materia'] : $assignment['materia']) : 
                [];
            $periodo = isset($assignment['periodo']) ? 
                (is_object($assignment['periodo']) ? (array) $assignment['periodo'] : $assignment['periodo']) : 
                [];
            
            return [
                'N°' => $index + 1,
                'Fecha' => isset($assignment['fecha_asignacion']) ? date('d/m/Y', strtotime($assignment['fecha_asignacion'])) : '-',
                'Estudiante' => ($estudiante['nombres'] ?? '') . ' ' . ($estudiante['apellidos'] ?? ''),
                'Curso' => $curso['nombre'] ?? '',
                'Paralelo' => $paralelo['nombre'] ?? '',
                'Materia' => $materia['nombre'] ?? '',
                'Período' => $periodo['nombre'] ?? '',
                'Puntos' => (int)($assignment['puntos'] ?? 0),
                'Comentario' => $assignment['comentario'] ?? '-'
            ];
        });
        
        // Combinar filas vacías con datos
        return $emptyRows->concat($assignmentsData);
    }

    public function headings(): array
    {
        return ['N°', 'Fecha', 'Estudiante', 'Curso', 'Paralelo', 'Materia', 'Período', 'Puntos', 'Comentario'];
    }

    public function title(): string
    {
        return 'Reporte de Asignaciones';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para los encabezados de la tabla
            12 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Añadir logo
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Logo de la institución');
                $drawing->setPath(public_path('img/LogoDario.png'));
                $drawing->setHeight(80);
                $drawing->setCoordinates('B1');
                $drawing->setOffsetX(20);
                $drawing->setOffsetY(10);
                $drawing->setWorksheet($sheet);

                // Título principal
                $sheet->mergeCells('A2:I2');
                $sheet->setCellValue('A2', 'UNIDAD EDUCATIVA DARIO MONTAÑO');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '1E40AF']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(30);

                // Subtítulo
                $sheet->mergeCells('A3:I3');
                $sheet->setCellValue('A3', 'REPORTE DE ASIGNACIÓN DE PUNTOS');
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => '374151']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(25);

                // Información del docente
                $row = 5;
                $sheet->setCellValue("A{$row}", 'Docente:');
                $sheet->setCellValue("B{$row}", $this->teacher['nombres'] . ' ' . $this->teacher['apellidos']);
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                
                $row++;
                $sheet->setCellValue("A{$row}", 'Fecha de generación:');
                $sheet->setCellValue("B{$row}", date('d/m/Y H:i:s'));
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);

                // Filtros aplicados
                if (!empty($this->filters)) {
                    $row++;
                    $sheet->setCellValue("A{$row}", 'Filtros aplicados:');
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    
                    if (isset($this->filters['curso'])) {
                        $row++;
                        $sheet->setCellValue("A{$row}", '  • Curso:');
                        $sheet->setCellValue("B{$row}", $this->filters['curso']);
                    }
                    if (isset($this->filters['materia'])) {
                        $row++;
                        $sheet->setCellValue("A{$row}", '  • Materia:');
                        $sheet->setCellValue("B{$row}", $this->filters['materia']);
                    }
                    if (isset($this->filters['periodo'])) {
                        $row++;
                        $sheet->setCellValue("A{$row}", '  • Período:');
                        $sheet->setCellValue("B{$row}", $this->filters['periodo']);
                    }
                }

                // Estadísticas
                $row += 2;
                $sheet->mergeCells("A{$row}:I{$row}");
                $sheet->setCellValue("A{$row}", 'ESTADÍSTICAS');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB']
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $row++;
                $stats = $this->data['stats'];
                $sheet->setCellValue("A{$row}", 'Total de asignaciones:');
                $sheet->setCellValue("B{$row}", (int)($stats['total_assignments'] ?? 0));
                $sheet->setCellValue("D{$row}", 'Total de puntos:');
                $sheet->setCellValue("E{$row}", (int)($stats['total_points'] ?? 0));
                $sheet->setCellValue("G{$row}", 'Promedio:');
                $sheet->setCellValue("H{$row}", (float)($stats['average_points'] ?? 0));
                $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);

                // Encabezados de la tabla (fila 12)
                $headerRow = 12;
                $sheet->getStyle("A{$headerRow}:I{$headerRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ],
                    ],
                ]);

                // Aplicar bordes a todas las celdas con datos
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A{$headerRow}:I{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB']
                        ],
                    ],
                ]);

                // Centrar columnas específicas
                $sheet->getStyle("A{$headerRow}:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B{$headerRow}:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D{$headerRow}:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$headerRow}:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H{$headerRow}:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Pie de página
                $footerRow = $lastRow + 2;
                $sheet->mergeCells("A{$footerRow}:I{$footerRow}");
                $sheet->setCellValue("A{$footerRow}", 'Generado automáticamente por el Sistema de Gestión de Puntos - U.E. Dario Montaño');
                $sheet->getStyle("A{$footerRow}")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            },
        ];
    }
}
