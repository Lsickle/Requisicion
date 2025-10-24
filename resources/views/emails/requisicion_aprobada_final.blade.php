<div style="font-family: Arial, sans-serif; color:#333;">
    <h2 style="margin:0 0 10px;">Requisición aprobada</h2>
    <p>Se informa que la requisición #{{ $requisicion->id }}</p>
    <p><strong>Solicitante:</strong> {{ $requisicion->name_user ?? '-' }}<br>
       <strong>Operación:</strong> {{ $requisicion->operacion_user ?? '-' }}<br>
       <strong>Prioridad:</strong> {{ ucfirst($requisicion->prioridad_requisicion ?? '-') }}</p>
    <p>En espera de la generación de la Orden de Compra.</p>
</div>
