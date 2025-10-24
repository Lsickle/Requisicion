<div style="font-family: Arial, sans-serif; color:#333;">
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
