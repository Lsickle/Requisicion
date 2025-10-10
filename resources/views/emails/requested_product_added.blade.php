<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Solicitud aprobada</title>
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
                <h2 style="margin:0 0 12px 0;">¡Solicitud aprobada!</h2>
                <p style="margin:0 0 16px 0;">Hola {{ $data['name_user'] ?? 'usuario' }},</p>
                <p style="margin:0 0 12px 0;">Tu solicitud de producto fue aprobada y el artículo fue añadido al catálogo.</p>
                <div style="margin:16px 0; padding:12px 16px; background:#f3f4f6; border-radius:6px;">
                    <p style="margin:0;"><strong>Producto:</strong> {{ $data['nombre'] ?? '' }}</p>
                    <p style="margin:4px 0 0 0;"><strong>Descripción:</strong> {{ $data['descripcion'] ?? '' }}</p>
                </div>
                <p style="margin:16px 0 0 0;">Gracias por ayudarnos a mejorar el inventario.</p>
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
