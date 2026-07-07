<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Requisición Completada</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color:#f5f7fb; padding:24px;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="background:#1e40af; padding:16px 24px; color:#ffffff;">
                <strong>Vigía Plus Logistics</strong>
            </td>
        </tr>
        <tr>
            <td style="padding:24px; color:#111827;">
                <div style="font-family: Arial, sans-serif; color:#111;">
                    <h2 style="margin:0 0 12px 0;">Requisición #{{ $requisicion->id }} Completada</h2>
                    <p style="margin:0 0 12px 0;">Hola {{ $requisicion->name_user ?? 'Usuario' }},</p>
                    <p style="margin:0 0 12px 0;">Tu requisición ha sido marcada como <strong>completada</strong>. Todas las cantidades requeridas han sido recibidas.</p>

                    <p style="margin:0 0 6px 0;"><strong>Prioridad:</strong> {{ ucfirst($requisicion->prioridad_requisicion) }}</p>
                    <p style="margin:0 0 6px 0;"><strong>Tipo:</strong> {{ $requisicion->Recobrable }}</p>
                    <p style="margin:0 0 6px 0;"><strong>Cantidad total solicitada:</strong> {{ $requisicion->amount_requisicion }}</p>
                    @if(!empty($requisicion->justify_requisicion))
                        <p style="margin:0 0 6px 0;"><strong>Justificación:</strong> {{ $requisicion->justify_requisicion }}</p>
                    @endif

                    @if($requisicion->productos && count($requisicion->productos))
                        <p style="margin:12px 0 6px 0;"><strong>Productos:</strong></p>
                        <ul style="margin:0; padding-left:18px;">
                            @foreach($requisicion->productos as $producto)
                                <li>{{ $producto->name_produc }} ({{ $producto->pivot->pr_amount }} {{ $producto->unit_produc }})</li>
                            @endforeach
                        </ul>
                    @endif

                    <p style="margin:16px 0 0 0;">Gracias por usar el sistema.</p>
                    <p style="margin:8px 0 0 0;">
                        <a href="{{ route('requisiciones.estatus', $requisicion->id) }}" style="display:inline-block; background:#1e40af; color:#ffffff; text-decoration:none; padding:10px 16px; border-radius:6px; font-weight:bold;">Ver detalle</a>
                    </p>
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 24px; color:#6b7280; font-size:12px; background:#f9fafb;">© {{ date('Y') }} Vigía Plus Logistics</td>
        </tr>
    </table>
</body>
</html>
