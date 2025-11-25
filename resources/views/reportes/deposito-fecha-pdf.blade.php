<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Depósitos por Fecha</title>
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
        .logo-container {
            margin-bottom: 0.5cm;
        }
        .logo {
            width: 80px;
            height: auto;
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
            width: 33.33%;
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
        <div class="logo-container">
            <img src="{{ public_path('img/LogoDario.png') }}" alt="Logo" class="logo">
        </div>
        <div class="title">Reporte de Depósitos por Fecha</div>
        <div class="subtitle">
            <strong>Período:</strong> {{ $fecha_inicio ?? '-' }} a {{ $fecha_fin ?? '-' }}
        </div>
        <div class="fecha-generacion">
            Generado el: {{ $fecha_generacion }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">Estadísticas Generales</div>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value">{{ number_format($estadisticas['total_depositos']) }}</div>
                <div class="stat-label">Total Depósitos</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">{{ number_format($estadisticas['total_puntos']) }}</div>
                <div class="stat-label">Puntos Totales</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">{{ number_format($estadisticas['usuarios_unicos']) }}</div>
                <div class="stat-label">Usuarios Únicos</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Depósitos por Tipo de Basura</div>
        <table>
            <thead>
                <tr>
                    <th width="50%">Tipo de Basura</th>
                    <th width="25%">Cantidad</th>
                    <th width="25%">Puntos Totales</th>
                </tr>
            </thead>
            <tbody>
                @foreach($porTipoBasura as $tipo)
                <tr>
                    <td>{{ $tipo['nombre'] ?? 'N/A' }}</td>
                    <td>{{ number_format($tipo['cantidad']) }}</td>
                    <td>{{ number_format($tipo['puntos_totales']) }} pts</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Detalle de Depósitos</div>
        <table>
            <thead>
                <tr>
                    <th width="18%">Fecha y Hora</th>
                    <th width="22%">Usuario</th>
                    <th width="25%">Tipo de Basura</th>
                    <th width="12%">Puntos</th>
                    <th width="23%">Basurero</th>
                </tr>
            </thead>
            <tbody>
                @foreach($depositos as $deposito)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($deposito->fechaHora)->format('d/m/Y H:i') }}</td>
                    <td>{{ $deposito->user->nombres ?? 'N/A' }} {{ $deposito->user->primerApellido ?? '' }}</td>
                    <td>{{ $deposito->tipoBasura->nombre ?? 'N/A' }}</td>
                    <td>{{ $deposito->tipoBasura->puntos ?? 0 }} pts</td>
                    <td>{{ $deposito->basurero->ubicacion ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Sistema de Puntos Dario Montaño - Página <span class="page-number"></span>
    </div>
</body>
</html>
