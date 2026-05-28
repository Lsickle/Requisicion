<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Documento de Entrada - {{ $ordenCompra->order_oc }}</title>

    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
            color: #333;
        }

        /* Watermark */
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

        /* Main content */
        .content {
            position: relative;
            z-index: 1;
            padding: 20px;
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

        .info-box p {
            margin: 5px 0;
            padding: 0 10px;
        }

        .info-box strong {
            color: #2c3e50;
        }

        .table-section {
            margin: 30px 0;
            clear: both;
        }

        .table-section h3 {
            background-color: #2c3e50;
            color: white;
            padding: 10px;
            margin-bottom: 10px;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table thead {
            background-color: #ecf0f1;
            border-bottom: 2px solid #2c3e50;
        }

        table th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            color: #2c3e50;
        }

        table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 11px;
        }

        table tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        table tbody tr:nth-child(even) {
            background-color: #ffffff;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .amount {
            text-align: right;
            font-weight: bold;
        }

        .total-row {
            background-color: #ecf0f1;
            font-weight: bold;
        }

        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 48%;
            float: left;
            margin-top: 30px;
        }

        .signature-box.right {
            float: right;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 10px;
        }

        .signature-image {
            max-width: 150px;
            max-height: 80px;
            margin-top: 10px;
        }

        .footer {
            margin-top: 50px;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="watermark">
        @if(file_exists(public_path('images/favicon1.png')))
            <img src="{{ public_path('images/favicon1.png') }}" alt="Watermark">
        @endif
    </div>

    <div class="content">
        <!-- Header -->
        <div class="header clearfix">
            <div class="company-info">
                <div class="title">DOCUMENTO DE ENTRADA</div>
                <p style="font-size: 11px; color: #666; margin-top: 5px;">Recepción de Orden de Compra</p>
            </div>
            <div class="document-info">
                <p><strong>Documento:</strong> DE-{{ $ordenCompra->order_oc }}</p>
                <p><strong>Fecha:</strong> {{ $fecha }}</p>
                <p><strong>Orden OC:</strong> {{ $ordenCompra->order_oc }}</p>
            </div>
        </div>

        <!-- Información General -->
        <div class="info-section clearfix">
            <div class="info-box">
                <h4>Información de la Orden</h4>
                <p><strong>Número OC:</strong> {{ $ordenCompra->order_oc ?? $ordenCompra->id }}</p>
                <p><strong>Requisición:</strong> #{{ optional($ordenCompra->requisicion)->id ?? 'N/A' }}</p>
                <p><strong>Fecha Creación OC:</strong> {{ optional($ordenCompra->created_at)->format('d/m/Y H:i') ?? 'N/A' }}</p>
            </div>
            <div class="info-box right">
                <h4>Información del Solicitante</h4>
                <p><strong>Nombre:</strong> {{ optional($ordenCompra->requisicion)->name_user ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ optional($ordenCompra->requisicion)->email_user ?? 'N/A' }}</p>
                <p><strong>Operación:</strong> {{ optional($ordenCompra->requisicion)->operacion_user ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Tabla de Productos Recibidos -->
        <div class="table-section">
            <h3>Productos Recibidos</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40%;">Producto</th>
                        <th style="width: 12%;" class="text-center">Unidad</th>
                        <th style="width: 15%;" class="text-center">Cantidad Solicitada</th>
                        <th style="width: 15%;" class="text-center">Cantidad Recibida</th>
                        <th style="width: 18%;" class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recepciones as $recepcion)
                        @php
                            $diferencia = ($recepcion->cantidad_recibido ?? 0) - ($recepcion->cantidad ?? 0);
                            $estado = ($recepcion->cantidad_recibido >= $recepcion->cantidad) ? 'Completo' : 'Parcial';
                            if ($recepcion->cantidad_recibido == 0) {
                                $estado = 'No Recibido';
                            }
                        @endphp
                        <tr>
                            <td>{{ optional($recepcion->producto)->name_produc ?? 'Producto #' . $recepcion->producto_id }}</td>
                            <td class="text-center">{{ optional($recepcion->producto)->unit_produc ?? 'Unidad' }}</td>
                            <td class="text-center">{{ $recepcion->cantidad }}</td>
                            <td class="text-center">
                                <strong>{{ $recepcion->cantidad_recibido }}</strong>
                            </td>
                            <td class="text-center">
                                @if($estado === 'Completo')
                                    <span style="background-color: #d4edda; padding: 3px 6px; border-radius: 3px;">✓ {{ $estado }}</span>
                                @elseif($estado === 'Parcial')
                                    <span style="background-color: #fff3cd; padding: 3px 6px; border-radius: 3px;">◐ {{ $estado }}</span>
                                @else
                                    <span style="background-color: #f8d7da; padding: 3px 6px; border-radius: 3px;">✗ {{ $estado }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Resumen -->
        <div class="info-section clearfix" style="margin-top: 30px;">
            <div class="info-box">
                <h4>Notas</h4>
                <p style="font-style: italic; color: #666;">
                    Documento de recepción de orden de compra. El solicitante ha verificado la entrega de los productos indicados.
                </p>
            </div>
        </div>

        <!-- Sección de Firmas -->
        <div class="signature-section clearfix">
            <div class="signature-box">
                <p><strong>Solicitante que Recibe</strong></p>
                @if($firmaPath && file_exists($firmaPath))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($firmaPath)) }}" alt="Firma" class="signature-image">
                @else
                    <div style="height: 60px; border: 1px solid #ddd; margin-top: 10px; display: flex; align-items: center; justify-content: center; color: #999;">
                        (Firma digital)
                    </div>
                @endif
                <div class="signature-line">
                    {{ $solicitante ?? 'Nombre del Solicitante' }}<br>
                    <small>Firma y Nombre</small>
                </div>
            </div>

            <div class="signature-box right">
                <p><strong>Fecha y Hora de Recepción</strong></p>
                <div style="margin-top: 30px;">
                    <p><strong>{{ $fecha }}</strong></p>
                </div>
                <div class="signature-line">
                    Fecha de Recepción<br>
                    <small>Sistema Automatizado</small>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Este documento fue generado automáticamente por el Sistema de Requisiciones.</p>
            <p>Para verificar la autenticidad, consulte con el departamento de Compras.</p>
        </div>
    </div>
</body>
</html>
