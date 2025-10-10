<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Requisición #{{ $requisicion->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
            color: #333;
        }

        .watermark {
            position: fixed;
            inset: 0;
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

        .content {
            position: relative;
            z-index: 1;
        }

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

        .centros-lista ul {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
        }

        .centros-lista li {
            margin-bottom: 3px;
            padding: 3px 0;
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

        .label {
            font-weight: bold;
            color: #2c3e50;
            min-width: 120px;
            display: inline-block;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .page-break {
            page-break-after: always;
            page-break-before: always;
        }

        /* Bloque de totales compatible PDF */
        .totals-table {
            width: 340px;
            margin: 20px 0 0 0;
            float: left;
            background: rgba(255, 255, 255, 0.288);
            border: 1px solid rgba(44, 62, 80, 0.06);
            border-radius: 6px;
            font-size: 12px;
            page-break-inside: avoid;
            box-sizing: border-box;
        }

        .totals-table td {
            padding: 10px 12px;
            border: none;
        }

        .totals-table .label {
            font-weight: 700;
            text-align: right;
            width: 65%;
        }

        .totals-table .value {
            text-align: right;
            width: 35%;
        }

        .totals-table .total-label {
            font-weight: 800;
            font-size: 13px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            padding-top: 8px;
        }

        .totals-table .total-value {
            font-weight: 800;
            font-size: 13px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            padding-top: 8px;
        }
    </style>
</head>

<body>
    @php $wm = !empty($logo) ? $logo : asset('images/VigiaLogoC.png'); @endphp
    <div class="watermark"><img src="{{ $wm }}" alt="marca de agua"></div>

    <div class="content">
        <!-- Encabezado -->
        <div class="header">
            <div class="company-info">
                @if(!empty($logo))
                <img src="{{ $logo }}" class="logo" alt="Logo de la empresa">
                @else
                <img src="{{ asset('images/VigiaLogoC.png') }}" class="logo" alt="Logo de la empresa">
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
                <div class="info-item"><span class="label">Solicitante:</span> {{ $requisicion->name_user ??
                    'Desconocido' }}</div>
                <div class="info-item"><span class="label">Prioridad:</span> {{
                    ucfirst($requisicion->prioridad_requisicion)}}</div>
                <div class="info-item"><span class="label">Recobrable:</span> {{ $requisicion->Recobrable }}</div>
                <div class="info-item"><span class="label">Detalles:</span> {{ $requisicion->detail_requisicion }}</div>
                <div class="info-item"><span class="label">Justificación:</span> {{ $requisicion->justify_requisicion }}
                </div>
            </div>
        </div>

        <!-- Tabla de productos (paginada si es necesario) -->
        @php
        $rowsPerPage = 18;
        $productPages = $requisicion->productos->chunk($rowsPerPage);
        $grandTotal = $requisicion->productos->reduce(function($carry, $p) {
        $qty = (int)($p->pivot->pr_amount ?? 0);
        $unit = (float)($p->price_produc ?? 0);
        return $carry + ($qty * $unit);
        }, 0);
        @endphp

        @foreach($productPages as $pageIndex => $page)
        <table class="product-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Unidad</th>
                    <th>Cantidad</th>
                    <th>Valor Unitario</th>
                    <th>Valor Total</th>
                    <th>Asignación a centros</th>
                </tr>
            </thead>
            <tbody>
                @foreach($page as $producto)
                <tr>
                    <td>{{ $producto->name_produc }}</td>
                    <td>{{ $producto->unit_produc ?? '-' }}</td>
                    <td>{{ $producto->pivot->pr_amount }}</td>
                    @php
                    $unitPrice = (float) ($producto->price_produc ?? 0);
                    $lineTotal = $unitPrice * ((int)($producto->pivot->pr_amount ?? 0));
                    @endphp
                    <td>${{ number_format($unitPrice, 2) }}</td>
                    <td>${{ number_format($lineTotal, 2) }}</td>
                    <td class="centros-lista">
                        <ul>
                            @php
                            $distros = null;
                            if (isset($producto->distribucion_centros) && is_countable($producto->distribucion_centros)
                            && count($producto->distribucion_centros) > 0) {
                            $distros = $producto->distribucion_centros;
                            } else {
                            $distros = \Illuminate\Support\Facades\DB::table('centro_producto')
                            ->where('requisicion_id', $requisicion->id)
                            ->where('producto_id', $producto->id)
                            ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                            ->select('centro.name_centro', 'centro_producto.amount')
                            ->get();
                            }
                            @endphp
                            @if($distros && is_countable($distros) && count($distros) > 0)
                            @foreach($distros as $centro)
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
        @if(!$loop->last)
        <div class="page-break"></div>
        @endif
        @endforeach

        <!-- Totales generales en tabla SIMPLE y visible en PDF -->
        <table class="totals-table">
            <tr>
                <td class="label total-label">TOTAL GENERAL:</td>
                <td class="value total-value">${{ number_format($grandTotal, 2) }}</td>
            </tr>
        </table>
        <div class="clear"></div>

        <!-- Firmas -->
        <div class="signatures">
            <div class="signature-box">
                <p class="font-semibold mb-2">{{ $requisicion->name_user ?? 'Desconocido' }}</p>
                <div class="signature-line"></div>
                <p class="mt-2">Solicitante</p>
            </div>
            <div class="clear"></div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Documento generado el {{ now()->format('d/m/Y H:i') }} | Software de Requisicion de Compras
        </div>
    </div> <!-- .content -->
</body>

</html>