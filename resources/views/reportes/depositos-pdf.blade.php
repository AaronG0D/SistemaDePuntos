<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Depósitos</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url({{ storage_path('fonts/DejaVuSans.ttf') }}) format("truetype");
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'DejaVu Sans';
            src: url({{ storage_path('fonts/DejaVuSans-Bold.ttf') }}) format("truetype");
            font-weight: bold;
            font-style: normal;
            font-display: swap;
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
            margin-bottom: 2cm;
            padding-bottom: 0.5cm;
            border-bottom: 1pt solid #4CAF50;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #2E7D32;
            margin-bottom: 0.3cm;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 9pt;
            color: #666;
        }
        .section {
            margin-bottom: 1.5cm;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #2E7D32;
            border-bottom: 0.5pt solid #4CAF50;
            padding-bottom: 0.2cm;
            margin-bottom: 0.5cm;
            text-transform: uppercase;
        }
        .stats-container {
            display: block;
            margin-bottom: 0.5cm;
        }
        .stat-box {
            background: #f5f5f5;
            padding: 0.4cm;
            margin-bottom: 0.4cm;
            border-radius: 4pt;
            border: 0.5pt solid #ddd;
        }
        .stat-value {
            font-size: 16pt;
            font-weight: bold;
            color: #1976D2;
            margin-bottom: 0.2cm;
        }
        .stat-label {
            font-size: 8pt;
            color: #666;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.3cm 0;
            font-size: 9pt;
        }
        th {
            background-color: #4CAF50;
            color: white;
            text-align: left;
            padding: 0.3cm;
            font-weight: bold;
            border: 0.5pt solid #43A047;
        }
        td {
            padding: 0.25cm 0.3cm;
            border-bottom: 0.5pt solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
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
        <div class="title">Reporte de Depósitos de Residuos</div>
        <div class="subtitle">{{ $fecha_generacion }}</div>
    </div>

    <div class="section">
        <div class="section-title">Resumen General</div>
        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-value">{{ number_format($estadisticas['total_depositos']) }}</div>
                <div class="stat-label">Total de Depósitos</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">{{ number_format($estadisticas['total_puntos']) }} pts</div>
                <div class="stat-label">Puntos Totales</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">{{ number_format($estadisticas['usuarios_unicos']) }}</div>
                <div class="stat-label">Usuarios Participantes</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Desglose por Tipo de Residuo</div>
        <table>
            <thead>
                <tr>
                    <th width="50%">Tipo de Residuo</th>
                    <th width="25%">Cantidad</th>
                    <th width="25%">Puntos Totales</th>
                </tr>
            </thead>
            <tbody>
                @foreach($porTipoBasura as $tipo)
                <tr>
                    <td>{{ $tipo['nombre'] }}</td>
                    <td>{{ number_format($tipo['cantidad']) }}</td>
                    <td>{{ number_format($tipo['puntos_totales']) }} pts</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="page-break-before: always;"></div>

    <div class="section">
        <div class="section-title">Últimos Depósitos Registrados</div>
        <table>
            <thead>
                <tr>
                    <th width="20%">Fecha y Hora</th>
                    <th width="25%">Usuario</th>
                    <th width="25%">Ubicación</th>
                    <th width="20%">Tipo</th>
                    <th width="10%">Puntos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($depositos->take(15) as $deposito)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($deposito->fechaHora)->isoFormat('DD MMM YYYY, HH:mm') }}</td>
                    <td>{{ $deposito->user ? mb_convert_encoding("{$deposito->user->nombres} {$deposito->user->primerApellido}", 'UTF-8', 'auto') : 'N/A' }}</td>
                    <td>{{ $deposito->basurero ? mb_convert_encoding($deposito->basurero->ubicacion, 'UTF-8', 'auto') : 'N/A' }}</td>
                    <td>{{ $deposito->tipoBasura ? mb_convert_encoding($deposito->tipoBasura->nombre, 'UTF-8', 'auto') : 'N/A' }}</td>
                    <td>{{ $deposito->tipoBasura ? number_format($deposito->tipoBasura->puntos) : 0 }} pts</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Sistema de Puntos - Página <span class="page-number"></span>
    </div>
</body>
</html> 