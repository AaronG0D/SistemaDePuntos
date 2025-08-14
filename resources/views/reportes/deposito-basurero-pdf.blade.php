<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Depósitos por Basurero</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
        h1 { color: #2E7D32; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #4CAF50; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Reporte de Depósitos por Basurero</h1>
    <p>Basurero ID: <strong>{{ $basurero_id }}</strong></p>
    <p>Fechas: <strong>{{ $fecha_inicio ?? '-' }}</strong> a <strong>{{ $fecha_fin ?? '-' }}</strong></p>
    <table>
        <thead>
            <tr>
                <th>Fecha y Hora</th>
                <th>Usuario</th>
                <th>Tipo de Basura</th>
                <th>Puntos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($depositos as $deposito)
            <tr>
                <td>{{ $deposito->fechaHora }}</td>
                <td>{{ $deposito->user ? $deposito->user->nombres : 'N/A' }}</td>
                <td>{{ $deposito->tipoBasura ? $deposito->tipoBasura->nombre : 'N/A' }}</td>
                <td>{{ $deposito->tipoBasura ? $deposito->tipoBasura->puntos : 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html> 