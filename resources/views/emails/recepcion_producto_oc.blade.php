<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Recepción registrada</title>
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
                    <h2 style="margin:0 0 10px;">Recepción registrada</h2>
                    <p style="margin:0 0 6px;">Se ha registrado la recepción del producto <strong>{{ $producto->name_produc }}</strong> en la orden {{ $numero }}.</p>
                    <p style="margin:0 0 6px;"><strong>Cantidad recibida:</strong> {{ $cantidad }}</p>
                    <p style="margin:0 0 6px;"><strong>Recibido por:</strong> {{ $usuario }}</p>
                    <p style="margin:16px 0 0 0;">Saludos,<br/>Equipo</p>
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 24px; color:#6b7280; font-size:12px; background:#f9fafb;">© {{ date('Y') }} Vigía Plus Logistics</td>
        </tr>
    </table>
</body>
</html>
