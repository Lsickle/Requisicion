<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Orden de Compra #{{ $orden->order_oc }}</title>
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
            margin-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 10px;
        }

        /* Información de proveedor y orden */
        .info-section {
            margin-bottom: 20px;
            overflow: hidden;
        }

        .info-box {
            width: 48%;
            float: left;
        }

        .info-box.right {
            float: right;
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
            /*tamaño de fuente */
        }

        .product-table td {
            padding: 6px;
            /* Reducir padding para más espacio */
            border-bottom: 1px solid #ddd;
            font-size: 10px;
            /*tamaño de fuente */
        }

        .product-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Totales */
        .totals {
            float: right;
            width: 300px;
            margin-top: 10px;
        }

        .total-row {
            overflow: hidden;
            margin-bottom: 5px;
        }

        .total-label {
            float: left;
            width: 70%;
            text-align: right;
            padding-right: 10px;
            font-weight: bold;
        }

        .total-value {
            float: right;
            width: 30%;
            text-align: right;
            font-weight: bold;
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
    </style>
</head>

<body>
    <!-- Encabezado -->
    <div class="header">
        @if(file_exists($logo))
        <div class="company-info">
            <img src="{{ $logo }}" class="logo" alt="Logo">
        </div>
        @endif

        <div class="document-info">
            <div class="title">ORDEN DE COMPRA #{{ $orden->order_oc }}</div>
            <div><strong>Fecha:</strong> {{ $orden->date_oc->format('d/m/Y') }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <!-- Información del proveedor y orden -->
    <div class="info-section">
        <div class="info-box">
            <h4>Proveedor</h4>
            <div class="info-item"><strong>Nombre:</strong> {{ $orden->proveedor->prov_name }}</div>
            <div class="info-item"><strong>NIT:</strong> {{ $orden->proveedor->prov_nit }}</div>
            <div class="info-item"><strong>Contacto:</strong> {{ $orden->proveedor->prov_name_c }}</div>
            <div class="info-item"><strong>Teléfono:</strong> {{ $orden->proveedor->prov_phone }}</div>
            <div class="info-item"><strong>Dirección:</strong> {{ $orden->proveedor->prov_adress }}, {{
                $orden->proveedor->prov_city }}</div>
        </div>

        <div class="info-box right">
            <h4>Detalles de la Orden</h4>
            <div class="info-item"><strong>Método de pago:</strong> {{ $orden->methods_oc }}</div>
            <div class="info-item"><strong>Plazo de entrega:</strong> {{ $orden->plazo_oc }}</div>
            <div class="info-item"><strong>Estado:</strong> {{ ucfirst($orden->estado) }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <!-- Tabla de productos -->
    <table class="product-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="25%">Producto</th>
                <th width="15%">Descripción</th>
                <th width="10%">Unidad</th>
                <th width="10%">Cantidad</th>
                <th width="15%">Centros de Costo</th>
                <th width="10%">Valor Unitario</th>
                <th width="10%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item['nombre'] }}</td>
                <td>{{ $item['descripcion'] }}</td>
                <td>{{ $item['unidad'] }}</td>
                <td>{{ number_format($item['cantidad'], 0) }}</td>
                <td>{{ $item['centros'] }}</td>
                <td class="text-right">${{ number_format($item['precio'], 2) }}</td>
                <td class="text-right">${{ number_format($item['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <div class="totals">
        <div class="total-row">
            <div class="total-label">SUBTOTAL:</div>
            <div class="total-value">${{ number_format($subtotal, 2) }}</div>
        </div>
        <div class="total-row">
            <div class="total-label">IVA (0%):</div>
            <div class="total-value">$0.00</div>
        </div>
        <div class="total-row">
            <div class="total-label">TOTAL:</div>
            <div class="total-value">${{ number_format($subtotal, 2) }}</div>
        </div>
    </div>
    <div class="clear"></div>

    <!-- Observaciones -->
    @if($orden->observaciones)
    <div style="margin-top: 20px; padding: 10px; background-color: #f9f9f9; border-left: 4px solid #2c3e50;">
        <h4 style="margin-top: 0;">Observaciones:</h4>
        <p>{{ $orden->observaciones }}</p>
    </div>
    @endif

    <!-- Firmas -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line"></div>
            <p>Autorizado por</p>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <p>Recibido por (Proveedor)</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="footer">
        Documento generado el {{ $fecha_actual }} | Software de Requisicion de Compras
    </div>
</body>

</html>