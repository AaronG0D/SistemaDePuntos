<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Evolución del Estudiante</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .header img {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #2E7D32;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 14px;
            color: #666;
        }
        .info-section {
            margin-bottom: 20px;
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #2196F3;
        }
        .info-row {
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            color: #1565C0;
        }
        .stats-table {
            width: 100%;
            margin-bottom: 20px;
            border-spacing: 10px;
        }
        .stat-box {
            width: 48%;
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2E7D32;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .page-number:before {
            content: "Página " counter(page);
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('img/LogoDario.png') }}" alt="Logo">
        <div class="title">Reporte de Evolución del Estudiante</div>
        <div class="subtitle">Generado el: {{ $fecha_generacion }}</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Estudiante:</span> {{ $nombre_completo }}
        </div>
        <div class="info-row">
            <span class="info-label">Curso y Paralelo:</span> {{ $curso_info }}
        </div>
        <div class="info-row">
            <span class="info-label">Rango de Fechas:</span> {{ $rango_fechas }}
        </div>
    </div>

    <table class="stats-table">
        <tr>
            <td class="stat-box">
                <div class="stat-value">{{ $total_puntos }}</div>
                <div class="stat-label">Puntos Totales en el Periodo</div>
            </td>
            <td class="stat-box">
                <div class="stat-value">{{ count($depositos) }}</div>
                <div class="stat-label">Cantidad de Depósitos</div>
            </td>
        </tr>
    </table>

    <h3>Detalle de Depósitos</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha y Hora</th>
                <th>Tipo de Residuo</th>
                <th>Puntos</th>
                <th>Basurero</th>
            </tr>
        </thead>
        <tbody>
            @forelse($depositos as $deposito)
            <tr>
                <td>{{ \Carbon\Carbon::parse($deposito->fechaHora)->format('d/m/Y H:i') }}</td>
                <td>{{ $deposito->tipoBasura->nombre }}</td>
                <td style="text-align: center; color: #2E7D32; font-weight: bold;">+{{ $deposito->tipoBasura->puntos }}</td>
                <td>{{ $deposito->basurero->ubicacion ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center;">No se encontraron depósitos en este rango de fechas.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Sistema de Puntos de Reciclaje - Unidad Educativa Darío Montaño<br>
        <span class="page-number"></span>
    </div>
</body>
</html>
