<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking de Usuarios</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url({{ storage_path('fonts/DejaVuSans.ttf') }}) format("truetype");
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'DejaVu Sans';
            src: url({{ storage_path('fonts/DejaVuSans-Bold.ttf') }}) format("truetype");
            font-weight: bold;
            font-style: normal;
        }
        * {
            font-family: 'DejaVu Sans', sans-serif;
            box-sizing: border-box;
        }
        @page {
            margin: 2.5cm 2cm;
            size: letter portrait;
        }
        body {
            font-size: 10pt;
            line-height: 1.3;
            background: white;
            color: black;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 1.5cm;
            padding-bottom: 0.5cm;
            border-bottom: 2pt solid #4CAF50;
        }
        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 0.5cm;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #2E7D32;
            margin-bottom: 0.3cm;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 10pt;
            color: #666;
            margin-bottom: 0.2cm;
        }
        .fecha-generacion {
            font-size: 9pt;
            color: #888;
            font-style: italic;
        }
        .section {
            margin-bottom: 1.2cm;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 13pt;
            font-weight: bold;
            color: #2E7D32;
            border-bottom: 1.5pt solid #4CAF50;
            padding-bottom: 0.3cm;
            margin-bottom: 0.6cm;
            text-transform: uppercase;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 0.8cm;
        }
        .stat-box {
            display: table-cell;
            width: 50%;
            background: #f5f5f5;
            padding: 0.5cm;
            text-align: center;
            border: 1pt solid #ddd;
            border-radius: 4pt;
        }
        .stat-value {
            font-size: 18pt;
            font-weight: bold;
            color: #1976D2;
            margin-bottom: 0.2cm;
        }
        .stat-label {
            font-size: 9pt;
            color: #666;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.4cm 0;
            font-size: 9pt;
        }
        th {
            background-color: #4CAF50;
            color: white;
            text-align: left;
            padding: 0.35cm;
            font-weight: bold;
            border: 0.5pt solid #43A047;
        }
        td {
            padding: 0.3cm;
            border-bottom: 0.5pt solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .rank-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            font-weight: bold;
            color: white;
        }
        .rank-1 {
            background-color: #FFD700;
            color: #000;
        }
        .rank-2 {
            background-color: #C0C0C0;
            color: #000;
        }
        .rank-3 {
            background-color: #CD7F32;
            color: #fff;
        }
        .rank-other {
            background-color: #4CAF50;
        }
        .footer {
            position: fixed;
            bottom: -2cm;
            left: 0;
            right: 0;
            height: 1cm;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 0.5pt solid #ddd;
            padding-top: 0.3cm;
            background: white;
        }
        .page-number:before {
            content: counter(page);
        }
        .page-number:after {
            content: " de " counter(pages);
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('img/LogoDario.png') }}" alt="Logo" class="logo">
        <div class="title">Ranking de {{ $filtros_nombres['tipo_ranking'] }}</div>
        <div class="subtitle">
            <strong>Período:</strong> {{ $filtros_nombres['periodo'] }}
        </div>
        <div class="fecha-generacion">
            Generado el: {{ $fecha_generacion }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">Estadísticas del Período</div>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value">{{ number_format($estadisticas['total_usuarios']) }}</div>
                <div class="stat-label">Total Usuarios</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">{{ number_format($estadisticas['total_puntos']) }}</div>
                <div class="stat-label">Puntos Totales</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Top 15 {{ $filtros_nombres['tipo_ranking'] }}</div>
        <table>
            <thead>
                <tr>
                    <th width="12%">Posición</th>
                    @if($filtros_nombres['tipo_ranking'] === 'Cursos')
                        <th width="48%">Curso - Paralelo</th>
                        <th width="15%">Estudiantes</th>
                    @else
                        <th width="28%">Nombre Completo</th>
                        <th width="20%">Curso</th>
                    @endif
                    <th width="15%">Total Depósitos</th>
                    <th width="15%">Puntos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($usuarios as $index => $u)
                <tr>
                    <td>
                        @if($index < 3)
                            <span class="rank-badge rank-{{ $index + 1 }}">{{ $index + 1 }}</span>
                        @else
                            <span class="rank-badge rank-other">{{ $index + 1 }}</span>
                        @endif
                    </td>
                    <td>{{ $u->nombre_completo ?? ($u->nombres . ' ' . $u->primerApellido) }}</td>
                    @if($filtros_nombres['tipo_ranking'] === 'Cursos')
                        <td>{{ $u->cantidad_estudiantes ?? 'N/A' }}</td>
                    @else
                        <td>{{ $u->curso_paralelo ?? 'N/A' }}</td>
                    @endif
                    <td>{{ number_format($u->total_depositos ?? 0) }}</td>
                    <td><strong>{{ number_format(round($u->total_puntos ?? 0)) }} pts</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <script type="text/php">
            if (isset($pdf)) {
                $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
                $size = 8;
                $font = $fontMetrics->getFont("helvetica");
                $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
                $x = ($pdf->get_width() - $width) / 2;
                $y = $pdf->get_height() - 35;
                $pdf->page_text($x, $y, $text, $font, $size);
            }
        </script>
    </div>
</body>
</html>
