<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Requisición #{{ $requisicion->id }}</title>
    <style>
        /* Reset y estilos base */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
            color: #333;
        }

        /* Encabezado */
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
            overflow: hidden;
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
            max-height: 50px;
            width: auto;
            margin-top: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 10px;
        }

        /* Información de requisición */
        .info-section {
            margin-bottom: 20px;
            overflow: hidden;
        }

        .info-box {
            width: 100%;
            margin-bottom: 15px;
        }

        .info-box h4 {
            background-color: #f5f5f5;
            padding: 5px 10px;
            margin: 0 0 10px 0;
            border-left: 4px solid #2c3e50;
            font-size: 14px;
        }

        .info-item {
            margin-bottom: 5px;
        }

        /* Tabla de productos */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .product-table th {
            background-color: #2c3e50;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }

        .product-table td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }

        .product-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Centros de costo */
        .centros-lista {
            margin: 0;
            padding: 0;
        }

        .centros-lista ul {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
        }

        .centros-lista li {
            margin-bottom: 3px;
            padding: 3px 0;
        }

        /* Firmas */
        .signatures {
            margin-top: 60px;
            overflow: hidden;
        }

        .signature-box {
            width: 45%;
            float: left;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin: 0 auto;
            width: 80%;
            padding-top: 5px;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }

        /* Utilidades */
        .text-right {
            text-align: right;
        }

        .clear {
            clear: both;
        }

        .label {
            font-weight: bold;
            color: #2c3e50;
            min-width: 120px;
            display: inline-block;
        }
    </style>
</head>

<body>
    <!-- Encabezado -->
    <div class="header">
        <div class="company-info">
            @if($logo)
            <img src="{{ $logo }}" class="logo" alt="Logo de la empresa">
            @endif
        </div>

        <div class="document-info">
            <div class="title">
                REQUISICIÓN #{{ $requisicion->id }}
                @if(!empty($operacionUsuario))
                {{ $operacionUsuario }}
                @endif
            </div>
            <div><strong>Fecha:</strong> {{ $requisicion->created_at->format('d/m/Y') }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <!-- Información de la requisición -->
    <div class="info-section">
        <div class="info-box">
            <h4>Detalles de la Requisición</h4>
            <div class="info-item"><span class="label">Solicitante:</span> {{ $requisicion->name_user ?? 'Desconocido' }}</div>
            <div class="info-item"><span class="label">Prioridad:</span> {{ ucfirst($requisicion->prioridad_requisicion)}}</div>
            <div class="info-item"><span class="label">Recobrable:</span> {{ $requisicion->Recobrable }}</div>
            <div class="info-item"><span class="label">Detalles:</span> {{ $requisicion->detail_requisicion }}</div>
            <div class="info-item"><span class="label">Justificación:</span> {{ $requisicion->justify_requisicion }}</div>
        </div>
    </div>

    <!-- Tabla de productos -->
    <table class="product-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Asignación a centros</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requisicion->productos as $producto)
            <tr>
                <td>{{ $producto->name_produc }}</td>
                <td>{{ $producto->pivot->pr_amount }}</td>
                <td class="centros-lista">
                    <ul>
                        @if(isset($producto->distribucion_centros) && $producto->distribucion_centros->count() > 0)
                        @foreach($producto->distribucion_centros as $centro)
                        <li>{{ $centro->name_centro }} ({{ $centro->amount }})</li>
                        @endforeach
                        @else
                        <li>No hay centros asignados</li>
                        @endif
                    </ul>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Firmas -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line"></div>
            <p>Solicitante</p>
        </div>
        <div class="clear"></div>
    </div>

    <!-- Footer -->
    <div class="footer">
        Documento generado el {{ now()->format('d/m/Y H:i') }} | Software de Requisicion de Compras
    </div>
</body>

</html>
