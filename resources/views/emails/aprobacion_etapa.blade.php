<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $subject ?? ('Requisición #'.$requisicion->id) }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color:#f5f7fb; padding:24px;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:720px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="background:#1e40af; padding:16px 24px; color:#ffffff;">
                <strong>Vigía Plus Logistics</strong>
            </td>
        </tr>
        <tr>
            <td style="padding:24px; color:#111827;">
                <h1 style="font-size:20px; margin:0 0 12px; font-weight:700; color:#111827;">Requisición #{{ $requisicion->id }}</h1>
                <p style="margin:0 0 10px; line-height:1.45;">{{ $mensajePrincipal }}</p>

                <div style="background:#f3f4f6; border-radius:6px; padding:12px; margin:14px 0;">
                    <p style="margin:0 0 6px;"><strong>Operación:</strong> {{ $requisicion->operacion_user ?? 'N/A' }}</p>
                    <p style="margin:0 0 6px;"><strong>Solicitante:</strong> {{ $requisicion->name_user ?? 'N/A' }}</p>
                    <p style="margin:0 0 6px;"><strong>Prioridad:</strong> {{ ucfirst($requisicion->prioridad_requisicion ?? '') }}</p>
                    <p style="margin:0;"><strong>Fecha:</strong> {{ optional($estatus->date_update ?? $estatus->created_at)->format('d/m/Y H:i') }}</p>
                </div>

                @if($requisicion->productos && count($requisicion->productos))
                    <p style="margin:0 0 6px;"><strong>Productos:</strong></p>
                    <ul style="padding-left:18px; margin:0 0 10px;">
                        @foreach($requisicion->productos as $producto)
                            <li>{{ $producto->name_produc }} ({{ $producto->pivot->pr_amount }} {{ $producto->unit_produc }})</li>
                        @endforeach
                    </ul>
                @endif

                <p style="margin:12px 0 0;">
                    <a href="{{ $panelUrl }}" target="_blank" rel="noopener" style="display:inline-block; background:#2563eb; color:#fff; text-decoration:none; padding:10px 16px; border-radius:6px;">Ir al Panel de Aprobación</a>
                    &nbsp;
                    <a href="{{ $detalleUrl }}" target="_blank" rel="noopener" style="display:inline-block; background:#059669; color:#fff; text-decoration:none; padding:10px 16px; border-radius:6px;">Ver Detalle</a>
                </p>

                <p style="color:#6b7280; font-size:12px; margin-top:14px;">Este mensaje fue generado automáticamente por el sistema de requisiciones.</p>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 24px; color:#6b7280; font-size:12px; background:#f9fafb;">© {{ date('Y') }} Vigía Plus Logistics</td>
        </tr>
    </table>
</body>
</html>