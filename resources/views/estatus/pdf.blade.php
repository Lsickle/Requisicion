<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo ?? 'Estatus requisición ' }} #{{ $requisicion->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        h1,
        h3 {
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .section-title {
            background-color: #2c3e50;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            border-radius: 4px;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .status-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .status-table th {
            background-color: #2c3e50;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }

        .status-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
        }

        .status-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .status-active {
            background-color: #e8f4ff !important;
            font-weight: bold;
        }

        .fecha {
            text-align: right;
            color: #555;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <h1>Reporte Estatus de Requisición</h1>

    <div class="section-title">Estatus Actual</div>
    <table class="status-table">
        <tr class="status-active">
            <td>{{ $estadoActual->status_name }}</td>
            <td class="fecha">{{ $estadoActual->pivot->created_at->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="section-title">Historial de Estatus</div>
    <table class="status-table">
        <thead>
            <tr>
                <th>Estatus</th>
                <th>Fecha de Actualización</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($historial->sortBy('pivot.created_at') as $estatus)
            <tr>
                <td>{{ $estatus->status_name }}</td>
                <td class="fecha">{{ $estatus->pivot->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>