<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Solicitud rechazada</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color:#f5f7fb; padding:24px;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="background:#991b1b; padding:16px 24px; color:#ffffff;">
                <strong>Vigía Plus Logistics</strong>
            </td>
        </tr>
        <tr>
            <td style="padding:24px; color:#111827;">
                <div style="font-family: Arial, sans-serif; color:#111;">
                    <h2 style="margin:0 0 12px 0;">Solicitud rechazada</h2>
                    <p style="margin:0 0 16px 0;">Hola {{ $data['name_user'] ?? 'usuario' }},</p>
                    <p style="margin:0 0 12px 0;">Lamentamos informarte que tu solicitud para el producto <strong>{{ $data['nombre'] ?? '' }}</strong> ha sido rechazada.</p>
                    @if(!empty($data['comentario']))
                        <p><strong>Motivo:</strong> {{ $data['comentario'] }}</p>
                    @endif
                    <p style="margin:16px 0 0 0;">Si tienes preguntas, por favor contacta al equipo.</p>
                    <p style="margin:0;">Saludos,<br/>Equipo</p>
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
