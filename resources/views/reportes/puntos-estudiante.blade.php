<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Puntos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 20px 0;
            border-bottom: 2px solid #333;
        }
        .estudiante-info {
            margin-bottom: 20px;
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
            background-color: #f4f4f4;
        }
        .total {
            font-weight: bold;
            text-align: right;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Puntos</h1>
    </div>

    <div class="estudiante-info">
        <h2>Información del Estudiante</h2>
        <p><strong>Nombre:</strong> {{ $estudiante->nombres }} {{ $estudiante->apellidos }}</p>
        <p><strong>Curso:</strong> {{ $estudiante->cursoParalelo->curso->nombre }} "{{ $estudiante->cursoParalelo->paralelo->nombre }}"</p>
    </div>

    <h3>Detalle de Puntos por Materia</h3>
    <table>
        <thead>
            <tr>
                <th>Materia</th>
                <th>Puntos</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach($estudiante->puntajes as $puntaje)
                <tr>
                    <td>{{ $puntaje->materia->nombre }}</td>
                    <td>{{ $puntaje->cantidad }}</td>
                    <td>{{ $puntaje->created_at->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total de Puntos: {{ $estudiante->puntajes->sum('cantidad') }}
    </div>
</body>
</html>
