<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ranking de Usuarios</title>

    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .header img {
            width: 70px;
            margin-right: 10px;
        }

        .header-title {
            font-size: 14pt;
            font-weight: bold;
            color: #2E7D32;
        }

        h1 {
            text-align: center;
            color: #2E7D32;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        th {
            background: #4CAF50;
            color: #fff;
        }
        
        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #2E7D32;
            margin-top: 20px;
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="header">
        <img src="{{ public_path('img/LogoDario.png') }}" alt="Logo">
        <div class="header-title">Sistema de Reportes Dario Montaño</div>
    </div>

    <h1>Ranking de Usuarios</h1>

    <p><strong>Período:</strong> {{ $nombre_periodo }}</p>
    <p><strong>Fecha de generación:</strong> {{ $fecha_generacion }}</p>

    <h2 class="section-title">Estadísticas</h2>
    <table>
        <thead>
            <tr>
                <th>Total Usuarios</th>
                <th>Total Puntos</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $estadisticas['total_usuarios'] }}</td>
                <td>{{ $estadisticas['total_puntos'] }}</td>
            </tr>
        </tbody>
    </table>

    <h2 class="section-title">Top 10 Usuarios</h2>
    <table>
        <thead>
            <tr>
                <th>Posición</th>
                <th>Nombre Completo</th>
                <th>Curso</th>
                <th>Puntos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($usuarios as $index => $u)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $u->nombre_completo ?? ($u->nombres . ' ' . $u->primerApellido) }}</td>
                <td>{{ $u->curso_paralelo ?? 'N/A' }}</td>
                <td>{{ $u->total_puntos ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
