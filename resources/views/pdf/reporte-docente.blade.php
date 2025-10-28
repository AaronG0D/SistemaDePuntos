<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Asignaciones - {{ $teacher['nombres'] }} {{ $teacher['apellidos'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            color: #1f2937;
            line-height: 1.6;
            background: #ffffff;
        }
        .page-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 30px 20px;
            margin: -20px -20px 30px -20px;
            border-bottom: 4px solid #1e3a8a;
        }
        .logo-section {
            text-align: center;
            margin-bottom: 15px;
        }
        .logo-img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 10px;
            background: white;
            border-radius: 50%;
            padding: 5px;
        }
        .institution-name {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 18px;
            font-weight: 600;
            opacity: 0.95;
        }
        .info-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .info-item {
            display: flex;
            align-items: flex-start;
        }
        .info-label {
            font-weight: 600;
            color: #475569;
            min-width: 120px;
            font-size: 13px;
        }
        .info-value {
            color: #1f2937;
            font-size: 13px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .filters-section {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .filters-title {
            font-weight: 700;
            color: #92400e;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .filter-item {
            display: inline-block;
            margin-right: 25px;
            font-size: 13px;
            color: #78350f;
        }
        .filter-item strong {
            color: #92400e;
        }
        .table-section {
            margin-top: 25px;
        }
        .table-title {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            background: white;
        }
        th {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            font-weight: 600;
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #1e3a8a;
            font-size: 11px;
        }
        td {
            border: 1px solid #e5e7eb;
            padding: 10px 8px;
            vertical-align: top;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        tr:hover {
            background-color: #eff6ff;
        }
        .points-cell {
            font-weight: bold;
            color: #059669;
            text-align: center;
            font-size: 13px;
        }
        .date-cell {
            white-space: nowrap;
            color: #475569;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
        }
        .footer-text {
            font-size: 10px;
            color: #6b7280;
            font-style: italic;
        }
        .footer-logo {
            font-weight: bold;
            color: #1e40af;
            margin-top: 5px;
        }
        @media print {
            body { margin: 0; padding: 10px; }
            .page-header { page-break-after: avoid; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="logo-section">
            <img src="{{ public_path('img/LogoDario.png') }}" alt="Logo Dario Montaño" class="logo-img">
            <div class="institution-name">Unidad Educativa Dario Montaño</div>
            <div class="report-title">Reporte de Asignación de Puntos Académicos</div>
        </div>
    </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Docente:</span>
                <span class="info-value">{{ $teacher['nombres'] }} {{ $teacher['apellidos'] }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Fecha de generación:</span>
                <span class="info-value">{{ now()->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Total de registros:</span>
                <span class="info-value">{{ $stats['total_assignments'] ?? 0 }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Período académico:</span>
                <span class="info-value">{{ date('Y') }}</span>
            </div>
        </div>
    </div>

    @if(isset($stats))
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ $stats['total_assignments'] }}</div>
            <div class="stat-label">Total Asignaciones</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $stats['total_points'] }}</div>
            <div class="stat-label">Total Puntos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $stats['average_points'] }}</div>
            <div class="stat-label">Promedio</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $stats['unique_students'] }}</div>
            <div class="stat-label">Estudiantes</div>
        </div>
    </div>
    @endif

    @if(isset($filters) && !empty($filters))
    <div class="filters-section">
        <div class="filters-title">📋 Filtros Aplicados:</div>
        @if(isset($filters['curso']))
            <span class="filter-item"><strong>Curso-Paralelo:</strong> {{ $filters['curso'] }}</span>
        @endif
        @if(isset($filters['materia']))
            <span class="filter-item"><strong>Materia:</strong> {{ $filters['materia'] }}</span>
        @endif
        @if(isset($filters['periodo']))
            <span class="filter-item"><strong>Período:</strong> {{ $filters['periodo'] }}</span>
        @endif
    </div>
    @endif

    <div class="table-section">
        <div class="table-title">📊 Detalle de Asignaciones</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">N°</th>
                    <th style="width: 10%;">Fecha</th>
                    <th style="width: 22%;">Estudiante</th>
                    <th style="width: 10%;">Curso</th>
                    <th style="width: 8%;">Paralelo</th>
                    <th style="width: 15%;">Materia</th>
                    <th style="width: 12%;">Período</th>
                    <th style="width: 7%;">Puntos</th>
                    <th style="width: 8%;">Comentario</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $index => $assignment)
                @php
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
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td class="date-cell">{{ isset($assignment['fecha_asignacion']) ? \Carbon\Carbon::parse($assignment['fecha_asignacion'])->format('d/m/Y') : '-' }}</td>
                    <td>{{ $estudiante['nombres'] ?? '' }} {{ $estudiante['apellidos'] ?? '' }}</td>
                    <td>{{ $curso['nombre'] ?? '' }}</td>
                    <td style="text-align: center;">{{ $paralelo['nombre'] ?? '' }}</td>
                    <td>{{ $materia['nombre'] ?? '' }}</td>
                    <td>{{ $periodo['nombre'] ?? '' }}</td>
                    <td class="points-cell">{{ $assignment['puntos'] ?? 0 }}</td>
                    <td style="font-size: 10px;">{{ $assignment['comentario'] ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #6b7280; padding: 30px; background: #f9fafb;">
                        <strong>No hay asignaciones para mostrar con los filtros seleccionados.</strong>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <div class="footer-text">
            Generado automáticamente el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i:s') }}
        </div>
        <div class="footer-logo">
            Sistema de Gestión de Puntos - U.E. Dario Montaño
        </div>
    </div>
</body>
</html>
