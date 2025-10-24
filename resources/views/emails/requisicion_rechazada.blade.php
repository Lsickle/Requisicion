<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Requisición - Notificación</title>
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
                    <h2 style="margin:0 0 10px;">
                        @if($estatusId===11)
                            Se requiere corrección
                        @elseif($estatusId===13)
                            Rechazo por Gerencia
                        @elseif($estatusId===9)
                            Rechazo por Gerencia Financiera
                        @else
                            Requisición rechazada
                        @endif
                        - Requisición #{{ $requisicion->id }}
                    </h2>
                    <p>Hola {{ $requisicion->name_user ?? 'Usuario' }},</p>
                    @if($estatusId===11)
                        <p>Se necesita que haga correcciones en la requisición para poder ser aprobada. Por favor ingrese al sistema, revise los comentarios y realice los ajustes necesarios.</p>
                    @elseif($estatusId===13)
                        <p>Tu requisición fue rechazada por Gerencia.</p>
                    @elseif($estatusId===9)
                        <p>Tu requisición fue rechazada por Gerencia Financiera.</p>
                    @else
                        <p>Tu requisición fue rechazada.</p>
                    @endif

                    @if(!empty($comentario))
                        <p><strong>Comentario:</strong> {{ $comentario }}</p>
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
