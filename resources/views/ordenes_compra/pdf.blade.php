<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>ORDEN DE COMPRA #{{ $orden->order_oc ?? $orden->id }}</title>

    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
            color: #333;
        }

        /* Watermark (imagen) */
        .watermark {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            width: 100%;
            height: 100%;
            text-align: center;
            opacity: 0.10;
        }

        .watermark img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-60deg);
            width: 160%;
            max-width: none;
            height: auto;
            display: block;
        }

        /* Ensure main content prints above watermark */
        .content {
            position: relative;
            z-index: 1;
        }

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
            max-height: 50px;
            width: auto;
            margin-top: 20px;
        }

        .top-logo {
            text-align: center;
            margin-bottom: 8px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 10px;
        }

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

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: transparent;
        }

        .product-table th {
            background-color: rgba(44, 62, 80, 0.95);
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }

        .product-table td {
            padding: 6px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            font-size: 10px;
            background: transparent;
        }

        .product-table tr:nth-child(even) {
            background-color: transparent;
        }

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

        .footer {
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }

        .text-right {
            text-align: right;
        }

        .clear {
            clear: both;
        }

        .page-break {
            page-break-after: always;
        }

        /* Totals card (mejorada, consistente con requisición) */
        .totals-card {
            width: 340px;
            margin-top: 18px;
            /* Alinear a la derecha sin usar float (más fiable en PDF) */
            display: block;
            clear: both; /* sentarse debajo de elementos flotantes anteriores */
            margin-left: auto; /* empuja a la derecha */
            margin-right: 0;
            page-break-inside: avoid;
        }
        .totals-card .card {
            background: rgba(255,255,255,0.75);
            border-radius: 8px;
            padding: 10px 14px;
            box-shadow: 0 2px 6px rgba(44,62,80,0.05);
            -webkit-print-color-adjust: exact;
            border: 1px solid rgba(44,62,80,0.06);
            display: flex;
            align-items: center;
            max-width: 340px; /* asegurar ancho estable */
            box-sizing: border-box;
        }
        .totals-card .stripe {
            width: 10px;
            height: 100%;
            background: #2c3e50;
            border-radius: 6px 0 0 6px;
            flex: 0 0 10px;
            margin-right: 10px;
        }
        .totals-card .card-content { flex: 1; }
        .totals-card .label {
            display: block;
            text-align: right;
            color: #2c3e50;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
        }
        .totals-card .amount {
            display: block;
            text-align: right;
            color: #111827;
            font-weight: 900;
            font-size: 20px;
            margin-top: 6px;
        }
        .totals-card .muted { display:block; text-align:right; color:#6b7280; font-size:11px; margin-top:4px; }

        /* Nuevo: cuadro de totales estilo caja con borde (como en la imagen) */
        .totals-box {
            width: 360px;
            /* Alinear a la izquierda */
            float: left;
            margin-left: 0;
            margin-top: 18px;
            /* Fondo más transparente para permitir que la marca de agua se vea debajo */
            background: rgba(255,255,255,0.35);
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            -webkit-print-color-adjust: exact;
            page-break-inside: avoid;
            overflow: hidden;
            box-sizing: border-box;
        }
        .totals-box .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
        }
        .totals-box .row + .row { border-top: 1px solid rgba(0,0,0,0.06); }
        .totals-box .label { color: #374151; font-weight: 700; text-transform: uppercase; font-size: 12px; }
        .totals-box .value { color: #111827; font-weight: 800; font-size: 16px; }
        .totals-box .row.total .label { font-size: 13px; }
        .totals-box .row.total .value { font-size: 18px; }
    </style>
</head>

<body>
    {{-- Marca de agua (usar $logo si viene como data URI, si no fallback a asset) --}}
    @php $watermarkSrc = !empty($logo) ? $logo : asset('images/VigiaLogoC.png'); @endphp
    <div class="watermark"><img src="{{ $watermarkSrc }}" alt="marca de agua"></div>

     <div class="content">
     <!-- Página 1: Productos para el proveedor -->
    <div class="header">
    <div class="header">
         <div class="company-info">
            @if(!empty($logo))
                <img src="{{ $logo }}" class="logo" alt="Logo de la empresa">
            @else
                <img src="{{ asset('images/VigiaLogoC.png') }}" alt="Vigía Plus Logistics" class="logo">
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
                    <div class="info-item"><strong>Dirección:</strong> {{ ($proveedor->prov_adress ?? '') .
                        (($proveedor->prov_city ?? '') ? ', '.$proveedor->prov_city : '') }}</div>
                </div>
                <div class="info-box right">
                    <h4>Detalles de la Orden</h4>
                    <div class="info-item"><strong>Método de pago:</strong> {{ $methods_oc }}</div>
                    <div class="info-item"><strong>Plazo de pago:</strong> {{ $plazo_oc }}</div>
                </div>
                <div class="clear"></div>
            </div>

            <!-- Nuevo: Centro de Costo / Operación de la requisición -->
            <div class="info-section">
                <div class="info-box">
                    <h4>Centro de Costo</h4>
                    <div class="info-item">
                        <strong>Operación:</strong>
                        {{ optional(optional($orden)->requisicion)->operacion_user ??
                        optional(optional($orden)->requisicion)->operacion->nombre ?? ($orden->operacion ?? 'N/A') }}
                    </div>
                </div>
                <div class="clear"></div>
            </div>

            @php
            // Paginación de productos para el PDF
            $rowsPerPage = 18; // ajustar si es necesario
            $productPages = array_chunk($items, $rowsPerPage);
            @endphp

            <style>
                /* Forzar repetición del thead en cada página y evitar cortes de fila */
                thead {
                    display: table-header-group;
                }

                tfoot {
                    display: table-footer-group;
                }

                tr {
                    page-break-inside: avoid;
                }
            </style>

            @foreach($productPages as $pageIndex => $pageItems)
            <table class="product-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%">Producto</th>
                        <th width="30%">Descripción</th>
                        <th width="8%">Unidad</th>
                        <th width="8%">Cantidad</th>
                        <th width="8%">IVA</th>
                        <th width="8%">Valor Unitario ({{ $currency ?? 'COP' }})</th>
                        <th width="10%">Total (c/ IVA) ({{ $currency ?? 'COP' }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pageItems as $i => $item)
                    <tr>
                        <td>{{ $pageIndex * $rowsPerPage + $i + 1 }}</td>
                        <td>{{ $item['name_produc'] }}</td>
                        <td>{{ $item['description_produc'] }}</td>
                        <td>{{ $item['unit_produc'] }}</td>
                        <td>{{ number_format($item['po_amount'], 0) }}</td>
                        @php
                        $ivaPercent = isset($item['iva']) ? (float)$item['iva'] : 0; // porcentaje
                        $ivaRate = $ivaPercent / 100;
                        // Usar precio en moneda original si existe; fallback a COP
                        $unitPrice = (float)($item['unit_price'] ?? ($item['precio_unitario'] ?? 0));
                        $unitIva = round($unitPrice * $ivaRate, 2);
                        $unitWithIva = round($unitPrice + $unitIva, 2);
                        $lineTotalWithIva = round($unitWithIva * (int)$item['po_amount'], 2);
                        @endphp
                        <td>{{ $ivaPercent > 0 ? number_format($ivaPercent, 2).'%' : '0%' }}</td>
                        <td class="text-right">{{ $currency ?? 'COP' }} ${{ number_format($unitPrice, 2) }}<br><small>+ IVA {{ $currency ?? 'COP' }} ${{ number_format($unitIva,2) }}</small></td>
                        <td class="text-right">{{ $currency ?? 'COP' }} ${{ number_format($lineTotalWithIva, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($pageIndex !== count($productPages) - 1)
            <div class="page-break"></div>
            @endif
            @endforeach

            {{-- reemplazo del bloque de totales flotante por cuadro con borde --}} 
            @php /* Antes: tarjeta .totals-card */ @endphp

            <div style="clear:both;"></div>
            <div class="totals-box" role="region" aria-label="Total General">
                <div class="row total">
                    <div class="label">TOTAL GENERAL ({{ $currency ?? 'COP' }})</div>
                    <div class="value">{{ $currency ?? 'COP' }} ${{ number_format($total ?? $subtotal ?? 0, 2) }}</div>
                </div>
            </div>
            <div style="clear:both;"></div>

    @if(!empty($observaciones))
    <div style="margin-top: 20px; padding: 10px; border-left: 4px solid #2c3e50; background: transparent;">
        <h4 style="margin-top: 0;">Observaciones:</h4>
        <p>{{ $observaciones }}</p>
    </div>
    @endif

            <div class="signatures">
                <div class="signature-box">
                    <p class="font-semibold mb-2">{{ $orden->oc_user ?? session('user.name') ?? 'N/A' }}</p>
                    <div class="signature-line"></div>
                    <p>Elaborado por</p>
                </div>
                <div class="signature-box" style="float:right;">
                    <p class="font-semibold mb-2">Alejandro Ramirez</p>
                    <div class="signature-line"></div>
                    <p>Aprobado por</p>
                </div>
                <div class="clear"></div>
            </div>

            <!-- Página 2: Distribución por centros -->
            <div class="page-break"></div>

            <div class="header">
                <div class="company-info">
                    @if(!empty($logo))
                    <img src="{{ $logo }}" class="logo" alt="Logo de la empresa">
                    @else
                    <img src="{{ asset('images/logo.png') }}" class="logo" alt="Vigía Plus Logistics">
                    @endif
                </div>
                <div class="document-info">
                    <div class="title">Distribución por Centros/subcentros - Orden #{{ $orden->order_oc ?? $orden->id }}
                    </div>
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
                        <th width="55%">Distribución por Centro/subcentros</th>
                        <th width="20%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td><strong>{{ $item['name_produc'] }}</strong><br><small>{{ $item['unit_produc'] }}</small>
                        </td>
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
                        <td class="text-right">{{ number_format($item['po_amount'], 0) }} {{ $item['unit_produc'] }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="footer">
                Documento generado el {{ $fecha_actual }} | Software de Requisición de Compras
            </div>
        </div> 
</body>

</html>