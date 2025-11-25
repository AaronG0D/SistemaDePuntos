<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ranking de Cursos</title>
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
            text-align: center;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .rank-1 {
            background-color: #FFD700 !important; /* Oro */
            font-weight: bold;
        }
        .rank-2 {
            background-color: #C0C0C0 !important; /* Plata */
            font-weight: bold;
        }
        .rank-3 {
            background-color: #CD7F32 !important; /* Bronce */
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            color: white;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-gold { background-color: #FFD700; color: #000; }
        .badge-silver { background-color: #C0C0C0; color: #000; }
        .badge-bronze { background-color: #CD7F32; color: #000; }
        
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
        <div class="title">Ranking de Cursos - Reciclaje</div>
        <div class="subtitle">Periodo: {{ $periodoTexto }}</div>
        <div class="subtitle">Generado el: {{ $fecha_generacion }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">Pos.</th>
                <th>Curso y Paralelo</th>
                <th style="text-align: center;">Estudiantes</th>
                <th style="text-align: center;">Depósitos</th>
                <th style="text-align: center;">Puntos Totales</th>
                <th style="text-align: center;">Promedio/Est.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ranking as $index => $curso)
            <tr class="{{ $index < 3 ? 'rank-' . ($index + 1) : '' }}">
                <td style="text-align: center;">
                    @if($index == 0) <span class="badge badge-gold">1º</span>
                    @elseif($index == 1) <span class="badge badge-silver">2º</span>
                    @elseif($index == 2) <span class="badge badge-bronze">3º</span>
                    @else {{ $index + 1 }}º
                    @endif
                </td>
                <td>{{ $curso->nombre_completo }}</td>
                <td style="text-align: center;">{{ $curso->cantidad_estudiantes }}</td>
                <td style="text-align: center;">{{ $curso->total_depositos }}</td>
                <td style="text-align: center; font-weight: bold; color: #2E7D32;">{{ $curso->total_puntos }}</td>
                <td style="text-align: center;">{{ $curso->promedio_por_estudiante }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">No hay datos disponibles para este periodo.</td>
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
