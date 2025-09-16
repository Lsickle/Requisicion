<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>ORDEN DE COMPRA #{{ $orden->order_oc ?? $orden->id }}</title>

    <style>
        body { font-family: 'DejaVu Sans', sans-serif; margin: 0; padding: 0; font-size: 12px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
        .company-info { float: left; width: 60%; }
        .document-info { float: right; width: 35%; text-align: right; }
        .logo { max-height: 80px; width: 150px; margin-top: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #2c3e50; margin-top: 10px; }
        .info-section { margin-bottom: 20px; overflow: hidden; }
        .info-box { width: 48%; float: left; }
        .info-box.right { float: right; }
        .info-box h4 { background-color: #f5f5f5; padding: 5px 10px; margin: 0 0 10px 0; border-left: 4px solid #2c3e50; font-size: 14px; }
        .info-item { margin-bottom: 5px; }
        .product-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .product-table th { background-color: #2c3e50; color: white; padding: 8px; text-align: left; font-size: 11px; }
        .product-table td { padding: 6px; border-bottom: 1px solid #ddd; font-size: 10px; }
        .product-table tr:nth-child(even) { background-color: #f9f9f9; }
        .totals { float: right; width: 300px; margin-top: 10px; }
        .total-row { overflow: hidden; margin-bottom: 5px; }
        .total-label { float: left; width: 70%; text-align: right; padding-right: 10px; font-weight: bold; }
        .total-value { float: right; width: 30%; text-align: right; font-weight: bold; }
        .signatures { margin-top: 60px; overflow: hidden; }
        .signature-box { width: 45%; float: left; text-align: center; }
        .signature-line { border-top: 1px solid #000; margin: 0 auto; width: 80%; padding-top: 5px; }
        .footer { margin-top: 30px; font-size: 10px; text-align: center; color: #777; border-top: 1px solid #eee; padding-top: 5px; }
        .text-right { text-align: right; }
        .clear { clear: both; }
        .page-break { page-break-after: always; }
    </style>
</head>

<body>
    <!-- Página 1: Productos para el proveedor -->
    <div class="header">
        <div class="company-info">
            @if(!empty($logo))
            <img src="{{ $logo }}" class="logo" alt="Logo de la empresa">
            @endif
        </div>
        <div class="document-info">
            <div class="title">ORDEN DE COMPRA #{{ $orden->order_oc ?? $orden->id }}</div>
            <div><strong>Fecha:</strong> {{ $date_oc }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="info-section">
        <div class="info-box">
            <h4>Proveedor</h4>
            <div class="info-item"><strong>Nombre:</strong> {{ $proveedor->prov_name ?? 'Proveedor' }}</div>
            <div class="info-item"><strong>NIT:</strong> {{ $proveedor->prov_nit ?? '' }}</div>
            <div class="info-item"><strong>Contacto:</strong> {{ $proveedor->prov_name_c ?? '' }}</div>
            <div class="info-item"><strong>Teléfono:</strong> {{ $proveedor->prov_phone ?? '' }}</div>
            <div class="info-item"><strong>Dirección:</strong> {{ ($proveedor->prov_adress ?? '') . (($proveedor->prov_city ?? '') ? ', '.$proveedor->prov_city : '') }}</div>
        </div>
        <div class="info-box right">
            <h4>Detalles de la Orden</h4>
            <div class="info-item"><strong>Método de pago:</strong> {{ $methods_oc }}</div>
            <div class="info-item"><strong>Plazo de pago:</strong> {{ $plazo_oc }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <table class="product-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="20%">Producto</th>
                <th width="35%">Descripción</th>
                <th width="10%">Unidad</th>
                <th width="10%">Cantidad</th>
                <th width="10%">Valor Unitario</th>
                <th width="10%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item['name_produc'] }}</td>
                <td>{{ $item['description_produc'] }}</td>
                <td>{{ $item['unit_produc'] }}</td>
                <td>{{ number_format($item['po_amount'], 0) }}</td>
                <td class="text-right">${{ number_format($item['precio_unitario'], 2) }}</td>
                <td class="text-right">${{ number_format($item['po_amount'] * $item['precio_unitario'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

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
            <div class="total-label">TOTAL A PAGAR:</div>
            <div class="total-value">${{ number_format($subtotal, 2) }}</div>
        </div>
    </div>
    <div class="clear"></div>

    @if(!empty($observaciones))
    <div style="margin-top: 20px; padding: 10px; background-color: #f9f9f9; border-left: 4px solid #2c3e50;">
        <h4 style="margin-top: 0;">Observaciones:</h4>
        <p>{{ $observaciones }}</p>
    </div>
    @endif

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

    <!-- Página 2: Distribución por centros -->
    <div class="page-break"></div>

    <div class="header">
        <div class="company-info">
            @if(!empty($logo))
            <img src="{{ $logo }}" class="logo" alt="Logo de la empresa">
            @endif
        </div>
        <div class="document-info">
            <div class="title">Distribución por Centros - Orden #{{ $orden->order_oc ?? $orden->id }}</div>
            <div><strong>Fecha:</strong> {{ $date_oc }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="info-section">
        <div class="info-box">
            <h4>Proveedor</h4>
            <div class="info-item"><strong>Nombre:</strong> {{ $proveedor->prov_name ?? 'Proveedor' }}</div>
            <div class="info-item"><strong>NIT:</strong> {{ $proveedor->prov_nit ?? '' }}</div>
            <div class="info-item"><strong>Contacto:</strong> {{ $proveedor->prov_name_c ?? '' }}</div>
        </div>
        <div class="info-box right">
            <h4>Orden</h4>
            <div class="info-item"><strong>Número:</strong> {{ $orden->order_oc ?? $orden->id }}</div>
            <div class="info-item"><strong>Método de pago:</strong> {{ $methods_oc }}</div>
            <div class="info-item"><strong>Plazo:</strong> {{ $plazo_oc }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <table class="product-table">
        <thead>
            <tr>
                <th width="25%">Producto</th>
                <th width="55%">Distribución por Centro</th>
                <th width="20%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td><strong>{{ $item['name_produc'] }}</strong><br><small>{{ $item['unit_produc'] }}</small></td>
                <td>
                    @php $dist = $distribucion[$item['producto_id']] ?? []; @endphp
                    @if(count($dist))
                        <ul style="margin:0;padding-left:16px;">
                            @foreach($dist as $row)
                                <li>{{ $row['name_centro'] }}: <strong>{{ $row['amount'] }}</strong></li>
                            @endforeach
                        </ul>
                    @else
                        <span>Sin distribución registrada</span>
                    @endif
                </td>
                <td class="text-right">{{ number_format($item['po_amount'], 0) }} {{ $item['unit_produc'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Documento generado el {{ $fecha_actual }} | Software de Requisición de Compras
    </div>
</body>

</html>