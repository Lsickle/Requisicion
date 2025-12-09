<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Entrega registrada</title>
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
                    <h2 style="margin:0 0 10px;">Entrega registrada - Requisición #{{ $requisicion->id }}</h2>
                    <p>Hola {{ $requisicion->name_user ?? 'Usuario' }},</p>
                    <p>Se ha registrado una entrega de los siguientes productos. Por favor ingresa al sistema para confirmar su recepción.</p>

                    @if(!empty($items))
                    <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse; margin:10px 0;">
                        <thead>
                            <tr>
                                <th align="left" style="border-bottom:1px solid #ddd;">Producto</th>
                                <th align="right" style="border-bottom:1px solid #ddd;">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $it)
                            <tr>
                                <td style="border-bottom:1px solid #f1f1f1;">{{ $it['nombre'] }}</td>
                                <td align="right" style="border-bottom:1px solid #f1f1f1;">{{ $it['cantidad'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif

                    <p style="margin-top: 15px;">Gracias.</p>
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 24px; color:#6b7280; font-size:12px; background:#f9fafb;">© {{ date('Y') }} Vigía Plus Logistics</td>
        </tr>
    </table>
</body>
</html>
