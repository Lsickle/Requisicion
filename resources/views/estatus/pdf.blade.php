<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo ?? 'Requisición' }} - {{ $requisicion->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
        }

        .logo {
            height: 70px;
            float: left;
            margin-right: 20px;
        }

        .header-text {
            overflow: hidden;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 14px;
            color: #7f8c8d;
        }

        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
            color: #2c3e50;
        }

        .info-value {
            flex: 1;
        }

        .section-title {
            background-color: #2c3e50;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            margin: 25px 0 10px 0;
            border-radius: 4px;
        }

        .current-status {
            background-color: #e8f4ff;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }

        .status-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .status-table th {
            background-color: #2c3e50;
            color: white;
            padding: 10px;
            text-align: left;
        }

        .status-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .status-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .status-active {
            background-color: #e8f4ff !important;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            text-align: right;
            color: #7f8c8d;
        }
    </style>
</head>

<body>
    <h1>Reporte de Requisición</h1>

    <h3>Estatus Actual:</h3>
    <p>{{ $estadoActual->status_name }} - {{ $estadoActual->pivot->created_at->format('d/m/Y H:i') }}</p>

    <h3>Historial de Estatus:</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Estatus</th>
                <th>Fecha de Actualización</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($historial as $estatus)
            <tr>
                <td>{{ $estatus->status_name }}</td>
                <td>{{ $estatus->pivot->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>