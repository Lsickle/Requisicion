<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>ORDEN DE COMPRA #<?php echo e($orden->order_oc ?? $orden->id); ?></title>

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
            page-break-inside: avoid;
            break-inside: avoid;
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
    
    <?php $watermarkSrc = !empty($logo) ? $logo : asset('images/VigiaLogoC.png'); ?>
    <div class="watermark"><img src="<?php echo e($watermarkSrc); ?>" alt="marca de agua"></div>

    <div class="content">
        <!-- Encabezado limpio -->
        <div class="header">
            <div class="company-info">
                <?php if(!empty($logo)): ?>
                    <img src="<?php echo e($logo); ?>" class="logo" alt="Logo de la empresa">
                <?php else: ?>
                    <img src="<?php echo e(asset('images/VigiaLogoC.png')); ?>" alt="Vigía Plus Logistics" class="logo">
                <?php endif; ?>
            </div>
            <div class="document-info">
                <div class="title">ORDEN DE COMPRA #<?php echo e($orden->order_oc ?? $orden->id); ?></div>
                <div><strong>Fecha:</strong> <?php echo e($date_oc); ?></div>
            </div>
            <div class="clear"></div>
        </div>

        <!-- Info proveedor y orden -->
        <div class="info-section">
            <div class="info-box">
                <h4>Proveedor</h4>
                <div class="info-item"><strong>Nombre:</strong> <?php echo e($proveedor->prov_name ?? 'Proveedor'); ?></div>
                <div class="info-item"><strong>NIT:</strong> <?php echo e($proveedor->prov_nit ?? ''); ?></div>
                <div class="info-item"><strong>Contacto:</strong> <?php echo e($proveedor->prov_name_c ?? ''); ?></div>
                <div class="info-item"><strong>Teléfono:</strong> <?php echo e($proveedor->prov_phone ?? ''); ?></div>
                <div class="info-item"><strong>Email:</strong> <?php echo e($proveedor->prov_email ?? ''); ?></div>
                <div class="info-item"><strong>Dirección:</strong> <?php echo e(($proveedor->prov_adress ?? '') . (($proveedor->prov_city ?? '') ? ', '.$proveedor->prov_city : '')); ?></div>
            </div>
            <div class="info-box right">
                <h4>Detalles de la Orden</h4>
                <div class="info-item"><strong>Método de pago:</strong> <?php echo e($methods_oc); ?></div>
                <div class="info-item"><strong>Plazo de pago:</strong> <?php echo e($plazo_oc); ?></div>
                <div class="info-item"><strong>Ubicación:</strong> <?php echo e($orden->ubicacion ?? 'No especificada'); ?></div>
            </div>
            <div class="clear"></div>
        </div>

        <!-- Centro de Costo / Operación -->
        <div class="info-section">
            <div class="info-box">
                <h4>Centro de Costo</h4>
                <div class="info-item"><strong>Operación:</strong>
                    <?php echo e(optional(optional($orden)->requisicion)->operacion_user ?? optional(optional($orden)->requisicion)->operacion->nombre ?? ($orden->operacion ?? 'N/A')); ?>

                </div>
            </div>
            <div class="clear"></div>
        </div>

        <?php $rowsPerPage = 18; $productPages = array_chunk($items, $rowsPerPage); ?>
        <?php $__currentLoopData = $productPages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pageIndex => $pageItems): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <table class="product-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="28%">Producto</th>
                    <th width="8%">Unidad</th>
                    <th width="6%">Cantidad</th>
                    <th width="6%">IVA</th>
                    <th width="12%">Valor Unitario (original)</th>
                    <th width="12%">Total (original)</th>
                    <th width="12%">Valor Unitario (COP)</th>
                    <th width="11%">Total (COP)</th>
                </tr>
            </thead>
            <tbody>
                <?php $pageGrandCop = 0; ?>
                <?php $__currentLoopData = $pageItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <?php
                    $idx = $pageIndex * $rowsPerPage + $i + 1;
                    // Base: precio_factura > precio_unitario > unit_price (sin precio_original)
                    $unitPrice = (float)($item['precio_factura'] ?? ($item['precio_unitario'] ?? ($item['unit_price'] ?? 0)));
                    $origCurrency = $item['currency'] ?? ($currency ?? 'COP');
                    $unitPriceCop = \App\Http\Controllers\requisicion\RequisicionController::convertToCop($unitPrice, $origCurrency);
                    if ($unitPriceCop === null) { $unitPriceCop = strtoupper(trim($origCurrency)) === 'COP' ? $unitPrice : 0; }
                    $ivaPercent = isset($item['iva']) ? (float)$item['iva'] : 0; $ivaRate = $ivaPercent / 100;
                    $unitIvaOrig = round($unitPrice * $ivaRate, 2);
                    $unitWithIvaOrig = round($unitPrice + $unitIvaOrig, 2);
                    $unitIvaCop = round($unitPriceCop * $ivaRate, 2);
                    $unitWithIvaCop = round($unitPriceCop + $unitIvaCop, 2);
                    $qty = (int)$item['po_amount'];
                    $lineTotalOrig = round($unitWithIvaOrig * $qty, 2);
                    $lineTotalCop = round($unitWithIvaCop * $qty, 2);
                    $pageGrandCop += $lineTotalCop;
                    ?>

                    <td><?php echo e($idx); ?></td>
                    <td><?php echo e($item['name_produc']); ?></td>
                    <td><?php echo e($item['unit_produc']); ?></td>
                    <td><?php echo e(number_format($qty, 0)); ?></td>
                    <td><?php echo e($ivaPercent > 0 ? number_format($ivaPercent, 2).'%' : '0%'); ?></td>
                    <td class="text-right"><?php echo e($origCurrency); ?> $<?php echo e(number_format($unitPrice, 2)); ?><br><small>IVA: <?php echo e($origCurrency); ?> $<?php echo e(number_format($unitIvaOrig, 2)); ?></small></td>
                    <td class="text-right"><?php echo e($origCurrency); ?> $<?php echo e(number_format($lineTotalOrig, 2)); ?></td>
                    <td class="text-right">COP $<?php echo e(number_format($unitPriceCop, 2)); ?><br><small>IVA: COP $<?php echo e(number_format($unitIvaCop, 2)); ?></small></td>
                    <td class="text-right">COP $<?php echo e(number_format($lineTotalCop, 2)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <?php if($pageIndex !== count($productPages) - 1): ?>
            <div class="page-break"></div>
        <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

         
        <?php /* Antes: tarjeta .totals-card */ ?>

        <div style="clear:both;"></div>
        <?php
            // Total general COP basado en precio_factura con fallbacks (sin precio_original)
            $grandTotalCop = 0;
            foreach($productPages as $pageItemsTmp) {
                foreach($pageItemsTmp as $it) {
                    $u = (float)($it['precio_factura'] ?? ($it['precio_unitario'] ?? ($it['unit_price'] ?? 0)));
                    $c = $it['currency'] ?? ($currency ?? 'COP');
                    $uCop = \App\Http\Controllers\requisicion\RequisicionController::convertToCop($u, $c);
                    if ($uCop === null) { $uCop = strtoupper(trim($c)) === 'COP' ? $u : 0; }
                    $iva = isset($it['iva']) ? ((float)$it['iva'] / 100) : 0;
                    $unitWithIvaCopTmp = round($uCop + ($uCop * $iva), 2);
                    $qtyTmp = (int)($it['po_amount'] ?? 0);
                    $grandTotalCop += round($unitWithIvaCopTmp * $qtyTmp, 2);
                }
            }
        ?>
        <div class="totals-box" role="region" aria-label="Total General">
            <div class="row total">
                <div class="label">TOTAL GENERAL (COP)</div>
                <div class="value">COP $<?php echo e(number_format($grandTotalCop, 2)); ?></div>
            </div>
        </div>
        <div style="clear:both;"></div>

        <?php if(!empty($observaciones)): ?>
        <div style="margin-top: 20px; padding: 10px; border-left: 4px solid #2c3e50; background: transparent;">
            <h4 style="margin-top: 0;">Observaciones:</h4>
            <p><?php echo e($observaciones); ?></p>
        </div>
        <?php endif; ?>

        <!-- Instrucciones Especiales antes de la firma -->
        <div class="instructions-box"> 
            <h4>INSTRUCCIONES ESPECIALES:</h4>
            <div>
                <p>
                    Favor confirmar recibido de esta orden al email <strong>juan.santos@vigiaplus.com</strong> y <strong>analista.administrativo@vigiaplus.com</strong>.
                </p>
                <p>
                    El plazo para la entrega no estará sujeto a prorrogas, salvo que sobrevengan hechos constitutivos de fuerza mayor o caso fortuito, que se acuerde entregas adicionales cuya realización implique ampliar dicho plazo, o que VIGIA PLUS SERVICES SAS lo autorice expresamente y por escrito. En todo caso, el proveedor tiene la obligación de dar pronto aviso por escrito si la entrega puede demorar. VIGIA PLUS SERVICES SAS no se responsabiliza por carga que no sea remitida de acuerdo con estas instrucciones. VIGIA PLUS SERVICES SAS, previo acuerdo con el proveedor, autorizará las entregas o despachos parciales.
                </p>
                <p><strong>-</strong> Todos los bienes entregados tratándose de químicos o reactivos, estos deben tener ficha técnica de manejo y destino final una vez se consuman.</p>

                <h5>PARA LA PRESENTACIÓN DE LAS FACTURAS SE DEBEN TENER EN CUENTA LAS SIGUIENTES INSTRUCCIONES:</h5>
                <ul>
                    <li>Fecha límite de recibo de facturas para pagos nacionales es el día veinticinco (25) de cada mes; las facturas recibidas con posterioridad a esta fecha límite deben traer fecha de expedición del mes siguiente, de lo contrario serán devueltas.</li>
                    <li>La factura deberá ser enviada al buzón destinado <strong>recepcionfacturas@vigiaplus.com</strong>.</li>
                    <li>En la factura debe estar relacionado el número de la orden de compra; de lo contrario será devuelta.</li>
                    <li>Al momento de la entrega el proveedor debe traer impresa la respectiva orden de compra.</li>
                </ul>
            </div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <p class="font-semibold mb-2"><?php echo e($orden->oc_user ?? session('user.name') ?? 'N/A'); ?></p>
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
                <?php if(!empty($logo)): ?>
                <img src="<?php echo e($logo); ?>" class="logo" alt="Logo de la empresa">
                <?php else: ?>
                <img src="<?php echo e(asset('images/logo.png')); ?>" class="logo" alt="Vigía Plus Logistics">
                <?php endif; ?>
            </div>
            <div class="document-info">
                <div class="title">Distribución por Centros/subcentros - Orden #<?php echo e($orden->order_oc ?? $orden->id); ?>

                </div>
                <div><strong>Fecha:</strong> <?php echo e($date_oc); ?></div>
            </div>
            <div class="clear"></div>
        </div>

        <div class="info-section">
            <div class="info-box">
                <h4>Proveedor</h4>
                <div class="info-item"><strong>Nombre:</strong> <?php echo e($proveedor->prov_name ?? 'Proveedor'); ?></div>
                <div class="info-item"><strong>NIT:</strong> <?php echo e($proveedor->prov_nit ?? ''); ?></div>
                <div class="info-item"><strong>Contacto:</strong> <?php echo e($proveedor->prov_name_c ?? ''); ?></div>
            </div>
            <div class="info-box right">
                <h4>Orden</h4>
                <div class="info-item"><strong>Número:</strong> <?php echo e($orden->order_oc ?? $orden->id); ?></div>
                <div class="info-item"><strong>Método de pago:</strong> <?php echo e($methods_oc); ?></div>
                <div class="info-item"><strong>Plazo:</strong> <?php echo e($plazo_oc); ?></div>
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
                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><strong><?php echo e($item['name_produc']); ?></strong><br><small><?php echo e($item['unit_produc']); ?></small>
                    </td>
                    <td>
                        <?php $dist = $distribucion[$item['producto_id']] ?? []; ?>
                        <?php if(count($dist)): ?>
                        <ul style="margin:0;padding-left:16px;">
                            <?php $__currentLoopData = $dist; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($row['name_centro']); ?>: <strong><?php echo e($row['amount']); ?></strong></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                        <?php else: ?>
                        <span>Sin distribución registrada</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right"><?php echo e(number_format($item['po_amount'], 0)); ?> <?php echo e($item['unit_produc']); ?>

                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <div class="footer">
            Documento generado el <?php echo e($fecha_actual); ?> | Software de Requisición de Compras
        </div>
    </div> 
</body>

</html><?php /**PATH C:\laragon\www\Requisicion\resources\views/ordenes_compra/pdf.blade.php ENDPATH**/ ?>