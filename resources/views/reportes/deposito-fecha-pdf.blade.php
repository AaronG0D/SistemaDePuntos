<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Depósitos por Fecha</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .header img {
            width: 100px; /* más pequeño */
            margin-right: 10px;
        }

        .header-title {
            font-size: 14pt;
            font-weight: bold;
            color: #000000;
        }

        h1 { 
            color: #2E7D32; 
            text-align: center; 
            margin-top: 10px;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #4CAF50; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }

        .section-title {
            font-size: 12pt;
            margin-top: 25px;
            color: #2E7D32;
            font-weight: bold;
            text-decoration: underline;
        }

        .stats-table th {
            background: #1976D2;
        }
    </style>
</head>
<body>

    <!-- HEADER CON LOGO Y TÍTULO -->
    <div class="header">
        <img src="{{ public_path('img/LogoDario.png') }}" alt="Logo">
        <div class="header-title">
            Sistema de Reportes Dario Montaño
        </div>
    </div>

    <h1>Reporte de Depósitos por Fecha</h1>

    <p><strong>Fechas:</strong> {{ $fecha_inicio ?? '-' }} a {{ $fecha_fin ?? '-' }}</p>
    <p><strong>Fecha de generación:</strong> {{ $fecha_generacion }}</p>

    <!-- =======================
         ESTADÍSTICAS GENERALES
    ======================== -->
    <h2 class="section-title">Estadísticas Generales</h2>
    <table class="stats-table">
        <thead>
            <tr>
                <th>Total de Depósitos</th>
                <th>Total de Puntos</th>
                <th>Usuarios Únicos</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $estadisticas['total_depositos'] }}</td>
                <td>{{ $estadisticas['total_puntos'] }}</td>
                <td>{{ $estadisticas['usuarios_unicos'] }}</td>
            </tr>
        </tbody>
    </table>

    <!-- =======================
         RESUMEN POR TIPO BASURA
    ======================== -->
    <h2 class="section-title">Depósitos por Tipo de Basura</h2>
    <table>
        <thead>
            <tr>
                <th>Tipo de Basura</th>
                <th>Cantidad</th>
                <th>Puntos Totales</th>
            </tr>
        </thead>
        <tbody>
            @foreach($porTipoBasura as $tipo)
                <tr>
                    <td>{{ $tipo['nombre'] ?? 'N/A' }}</td>
                    <td>{{ $tipo['cantidad'] }}</td>
                    <td>{{ $tipo['puntos_totales'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- =======================
            LISTA DE DEPÓSITOS
    ======================== -->
    <h2 class="section-title">Detalle de Depósitos</h2>

    <table>
        <thead>
            <tr>
                <th>Fecha y Hora</th>
                <th>Usuario</th>
                <th>Tipo de Basura</th>
                <th>Puntos</th>
                <th>Basurero</th>
            </tr>
        </thead>
        <tbody>
            @foreach($depositos as $deposito)
            <tr>
                <td>{{ $deposito->fechaHora }}</td>
                <td>{{ $deposito->user->nombres ?? 'N/A' }}</td>
                <td>{{ $deposito->tipoBasura->nombre ?? 'N/A' }}</td>
                <td>{{ $deposito->tipoBasura->puntos ?? 0 }}</td>
                <td>{{ $deposito->basurero->ubicacion ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
