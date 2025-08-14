<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ranking</title>
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
    <h1>Reporte de Ranking</h1>
    <p>Periodo: <strong>{{ $periodo }}</strong></p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Usuario</th>
                <th>Puntos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($usuarios as $i => $usuario)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $usuario->nombres ?? 'N/A' }} {{ $usuario->primerApellido ?? '' }}</td>
                <td>{{ $usuario->total_puntos ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html> 