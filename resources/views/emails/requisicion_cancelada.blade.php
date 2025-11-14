<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Requisición Cancelada</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color:#f5f7fb; padding:24px;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="background:#dc2626; padding:16px 24px; color:#ffffff;">
                <strong>Vigía Plus Logistics</strong>
            </td>
        </tr>
        <tr>
            <td style="padding:24px; color:#111827;">
                <div style="font-family: Arial, sans-serif; color:#111;">
                    <h2 style="margin:0 0 12px 0;">Requisición Cancelada</h2>
                    <p style="margin:0 0 12px 0;">Hola {{ $nombreSolicitante ?? 'usuario' }}, usted ha cancelado el proceso de requisición. No continuará el proceso.</p>
                    <p style="margin:0 0 6px 0;"><strong>Número:</strong> #{{ $requisicion->id }}</p>
                    <p style="margin:0 0 6px 0;"><strong>Prioridad:</strong> {{ ucfirst($requisicion->prioridad_requisicion) }}</p>
                    <p style="margin:0 0 6px 0;"><strong>Tipo:</strong> {{ $requisicion->Recobrable }}</p>
                    @if(!empty($requisicion->justify_requisicion))
                        <p style="margin:0 0 6px 0;"><strong>Justificación:</strong> {{ $requisicion->justify_requisicion }}</p>
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 24px; color:#6b7280; font-size:12px; background:#f9fafb;">
                © {{ date('Y') }} Vigía Plus Logistics
            </td>
        </tr>
    </table>
</body>
</html>