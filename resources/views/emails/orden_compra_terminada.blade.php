<div style="font-family: Arial, sans-serif; color:#333;">
    <h2 style="margin:0 0 10px;">Orden de compra cerrada</h2>
    <p>Se ha cerrado la orden de compra {{ $numero }}.</p>
    <p><strong>Requisición:</strong> #{{ $orden->requisicion_id }}<br>
       <strong>Fecha de creación:</strong> {{ optional($orden->created_at)->format('d/m/Y H:i') }}</p>
</div>
