<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $titulo ?? 'Estatus requisición' }} #{{ $requisicion->id }}</title>
    <style>
        /* Reset básico para DomPDF */
        body,
        div,
        table,
        tr,
        td,
        img {
            margin: 0;
            padding: 0;
            border: 0;
            font-size: 100%;
            font: inherit;
            vertical-align: baseline;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        /* Header mejorado */
        .header {
            width: 100%;
            margin-bottom: 20px;
            overflow: hidden;
            position: relative;
            page-break-after: avoid;
        }

        .company-info {
            float: left;
            width: 60%;
        }

        .document-info {
            float: right;
            width: 35%;
            text-align: right;
        }

        .logo {
            max-height: 80px;
            max-width: 200px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        /* Secciones de contenido */
        .section-title {
            background-color: #2c3e50;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            clear: both;
        }

        /* Tablas */
        .status-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .status-table th {
            background-color: #2c3e50;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
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
        }

        /* Clearfix para el header */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>

<body>
    <div class="header clearfix">
        <div class="company-info">
            @if(isset($logo) && $logo)
            <img src="{{ $logo }}" class="logo" alt="Logo de la empresa">
            @else
            <div style="font-weight: bold; font-size: 16px; margin-bottom: 10px;">Nombre de tu Empresa</div>
            @endif
        </div>
        <div class="document-info">
            <div class="title">Reporte Estatus #{{ $requisicion->id }}</div>
            <div><strong>Fecha de creación:</strong> {{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    <hr style="color: #2c3e50">

    <div class="section-title">Estatus Actual</div>
    <table class="status-table">
        <tr class="status-active">
            <td width="70%">{{ $estadoActual->status_name }}</td>
            <td width="30%" class="fecha">{{ $estadoActual->pivot->created_at->format('d/m/Y') }}</td>
        </tr>
    </table>

    <div class="section-title">Historial de Estatus</div>
    <table class="status-table">
        <thead>
            <tr>
                <th width="70%">Estatus</th>
                <th width="30%">Fecha de Actualización</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($historial->sortBy('pivot.created_at') as $estatus)
            <tr>
                <td>{{ $estatus->status_name }}</td>
                <td class="fecha">{{ $estatus->pivot->created_at->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>