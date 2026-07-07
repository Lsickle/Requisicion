<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Orden de Compra Creada</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; background-color:#f5f7fb; padding:24px;">
    @php
        // Detectar objeto de orden robustamente
        $oc = $orden ?? ($ordenCompra ?? ($orden_compra ?? null));
        if (is_array($oc)) { $oc = (object) $oc; }

        $orderId = $oc->id ?? null;
        $orderNumber = $oc->order_oc ?? ($orderId ? ('OC-' . $orderId) : 'OC');

        // Usar fecha formateada enviada por el Mailable si viene
        $createdAt = isset($createdAtStr) && $createdAtStr ? $createdAtStr : (function() use ($oc){
            $dtCreated = optional($oc->created_at);
            $dateBase = !empty($oc->date_oc) ? \Carbon\Carbon::parse($oc->date_oc) : ($dtCreated ?: now());
            $timePart = $dtCreated ? $dtCreated->format('H:i') : now()->format('H:i');
            return $dateBase->format('d/m/Y') . ' ' . $timePart;
        })();

        $createdBy = $oc->oc_user ?? $oc->user_name ?? $oc->name_user ?? $oc->email_user ?? 'Sistema';
        $requisicionId = $oc->requisicion_id ?? 'N/A';

        // Tomar proveedor directamente del Mailable si viene
        $provArr = is_array($proveedor ?? null) ? $proveedor : null;
        $provName   = $provArr['prov_name']   ?? null;
        $provNit    = $provArr['prov_nit']    ?? null;
        $provContact= $provArr['prov_name_c'] ?? null;
        $provPhone  = $provArr['prov_phone']  ?? null;
        $provAdress = $provArr['prov_adress'] ?? null;
        $provCity   = $provArr['prov_city']   ?? null;
        $methodsOc  = $provArr['methods_oc']  ?? null;
        $plazoOc    = $provArr['plazo_oc']    ?? null;

        // Si el Mailable no entregó proveedor, intentar un mínimo fallback (join directo)
        if ($provName === null && !empty($orderId)) {
            try {
                $provRow = \Illuminate\Support\Facades\DB::table('ordencompra_producto as ocp')
                    ->join('proveedores as prov', 'prov.id', '=', 'ocp.proveedor_id')
                    ->where('ocp.orden_compras_id', $orderId)
                    ->whereNull('ocp.deleted_at')
                    ->select('prov.*')
                    ->first();
                if ($provRow) {
                    $provName   = $provRow->prov_name ?? '';
                    $provNit    = $provRow->prov_nit  ?? '';
                    $provContact= $provRow->prov_name_c ?? '';
                    $provPhone  = $provRow->prov_phone ?? '';
                    $provAdress = $provRow->prov_adress ?? '';
                    $provCity   = $provRow->prov_city ?? '';
                    $methodsOc  = $provRow->methods_oc ?? '';
                    $plazoOc    = $provRow->plazo_oc ?? '';
                }
            } catch (\Throwable $e) { /* noop */ }
        }

        // Defaults si siguiera vacío
        $provName = $provName !== null && $provName !== '' ? $provName : 'Proveedor';
        $provNit = $provNit ?? '';
        $provContact = $provContact ?? '';
        $provPhone = $provPhone ?? '';
        $provAdress = $provAdress ?? '';
        $provCity = $provCity ?? '';
        $methodsOc = $methodsOc ?? '';
        $plazoOc = $plazoOc ?? '';

        // Construir filas de productos
        $rows = [];
        try {
            $relLoaded = (is_object($oc) && method_exists($oc, 'relationLoaded') && $oc->relationLoaded('ordencompraProductos'));
            if ($relLoaded && ($oc->ordencompraProductos?->count() ?? 0) > 0) {
                foreach ($oc->ordencompraProductos as $p) {
                    $rows[] = [
                        'name' => optional($p->producto)->name_produc ?? 'Producto',
                        'unit' => optional($p->producto)->unit_produc ?? '',
                        'qty'  => (int)($p->total ?? 0),
                    ];
                }
            } elseif (!empty($orderId)) {
                $q = \Illuminate\Support\Facades\DB::table('ordencompra_producto as ocp')
                    ->leftJoin('productos as prd', 'prd.id', '=', 'ocp.producto_id')
                    ->where('ocp.orden_compras_id', $orderId)
                    ->whereNull('ocp.deleted_at')
                    ->select('prd.name_produc', 'prd.unit_produc', 'ocp.total')
                    ->get();
                foreach ($q as $r) {
                    $rows[] = [
                        'name' => $r->name_produc ?? 'Producto',
                        'unit' => $r->unit_produc ?? '',
                        'qty'  => (int)($r->total ?? 0),
                    ];
                }
            }
        } catch (\Throwable $e) { /* noop */ }
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="background:#1e40af; padding:16px 24px; color:#ffffff;"><strong>Vigía Plus Logistics</strong></td>
        </tr>
        <tr>
            <td style="padding:24px; color:#111827;">
                <div style="font-family: Arial, sans-serif; color:#111;">
                    <h2 style="margin:0 0 12px 0;">Orden de compra creada</h2>
                    <p style="margin:0 0 12px 0;">Se ha creado la orden <strong>#{{ $orderNumber }}</strong>.</p>

                    <!-- Un solo cuadro con toda la información -->
                    <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:14px; margin:14px 0;">
                        <p style="margin:0 0 6px 0;"><strong>Proveedor:</strong> {{ $provName }}</p>
                        @if($provNit !== '')<p style="margin:0 0 6px 0;"><strong>NIT:</strong> {{ $provNit }}</p>@endif
                        @if($provContact !== '')<p style="margin:0 0 6px 0;"><strong>Contacto:</strong> {{ $provContact }}</p>@endif
                        @if($provPhone !== '')<p style="margin:0 0 6px 0;"><strong>Teléfono:</strong> {{ $provPhone }}</p>@endif
                        @php $fullAddr = trim(($provAdress ?? '') . (($provCity ?? '') !== '' ? ', ' . $provCity : '')); @endphp
                        @if($fullAddr !== '')<p style="margin:0 0 6px 0;"><strong>Dirección:</strong> {{ $fullAddr }}</p>@endif

                        <hr style="border:none; border-top:1px solid #e5e7eb; margin:10px 0;">

                        <p style="margin:0 0 6px 0;"><strong>Número:</strong> {{ $orderNumber }}</p>
                        <p style="margin:0 0 6px 0;"><strong>Fecha:</strong> {{ $createdAt }}</p>
                        <p style="margin:0 0 6px 0;"><strong>Requisición:</strong> #{{ $requisicionId }}</p>
                        <p style="margin:0 0 6px 0;"><strong>Método de pago:</strong> {{ ($methodsOc ?? '') !== '' ? $methodsOc : '—' }}</p>
                        <p style="margin:0 0 6px 0;"><strong>Plazo de pago:</strong> {{ ($plazoOc ?? '') !== '' ? $plazoOc : '—' }}</p>
                        <p style="margin:0;"><strong>Creada por:</strong> {{ $createdBy }}</p>
                    </div>

                    @if(count($rows))
                        <table style="width:100%; border-collapse:collapse; font-size:14px;">
                            <thead>
                                <tr>
                                    <th style="text-align:left; padding:8px; background:#f3f4f6; border-top:1px solid #e5e7eb;">Producto</th>
                                    <th style="text-align:left; padding:8px; background:#f3f4f6; border-top:1px solid #e5e7eb;">Cantidad</th>
                                    <th style="text-align:left; padding:8px; background:#f3f4f6; border-top:1px solid #e5e7eb;">Unidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $r)
                                <tr>
                                    <td style="padding:8px; border-top:1px solid #e5e7eb;">{{ $r['name'] }}</td>
                                    <td style="padding:8px; border-top:1px solid #e5e7eb;">{{ (int)$r['qty'] }}</td>
                                    <td style="padding:8px; border-top:1px solid #e5e7eb;">{{ $r['unit'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    <p style="color:#6b7280; font-size:12px; margin-top:14px;">Este mensaje fue generado automáticamente por el sistema VPL-Compras.</p>
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 24px; color:#6b7280; font-size:12px; background:#f9fafb;">© {{ date('Y') }} Vigía Plus Logistics</td>
        </tr>
    </table>
</body>

</html>