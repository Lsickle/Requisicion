<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Orden de Compra Cerrada</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color:#f5f7fb; padding:24px;">
    @php
        $oc = $orden ?? null;
        $orderId = $oc->id ?? null;
        $orderNumber = $numero ?? ($oc->order_oc ?? ($orderId ? ('OC-' . $orderId) : 'OC'));
        $createdAt = optional($oc->created_at)->format('d/m/Y H:i');
        $closedAt = now()->format('d/m/Y H:i');
        $requisicionId = $oc->requisicion_id ?? 'N/A';
        $createdBy = $oc->oc_user ?? $oc->user_name ?? $oc->name_user ?? $oc->email_user ?? 'Sistema';
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="background:#1e40af; padding:16px 24px; color:#ffffff;"><strong>Vigía Plus Logistics</strong></td>
        </tr>
        <tr>
            <td style="padding:24px; color:#111827;">
                <div style="font-family: Arial, sans-serif; color:#111;">
                    <h2 style="margin:0 0 12px 0;">Orden de compra cerrada</h2>
                    <p style="margin:0 0 12px 0;">Se ha cerrado la orden <strong>#{{ $orderNumber }}</strong>.</p>

                    <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:14px; margin:14px 0;">
                        <p style="margin:0 0 6px 0;"><strong>Número:</strong> {{ $orderNumber }}</p>
                        <p style="margin:0 0 6px 0;"><strong>Requisición:</strong> #{{ $requisicionId }}</p>
                        <p style="margin:0 0 6px 0;"><strong>Fecha de creación:</strong> {{ $createdAt ?: '—' }}</p>
                        <p style="margin:0 0 6px 0;"><strong>Fecha de cierre:</strong> {{ $closedAt }}</p>
                        <p style="margin:0;"><strong>Creada por:</strong> {{ $createdBy }}</p>
                    </div>

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
