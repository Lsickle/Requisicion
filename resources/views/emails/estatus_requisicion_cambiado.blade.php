<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estatus de Requisición Actualizado</title>
</head>
<body>
    <h2>Estatus de Requisición Actualizado</h2>
    
    <p>La requisición #{{ $requisicion->id }} ha cambiado de estatus.</p>
    
    <p><strong>Nuevo Estatus:</strong> {{ $nombreEstatus }}</p>
    <p><strong>Usuario:</strong> {{ $nombreUsuario }}</p>
    <p><strong>Fecha de Actualización:</strong> {{ $estatusRequisicion->date_update->format('d/m/Y H:i') }}</p>
    
    @if($estatusRequisicion->comentario)
    <p><strong>Comentario:</strong> {{ $estatusRequisicion->comentario }}</p>
    @endif
    
    <p>Puede revisar la requisición en el sistema de gestión.</p>
</body>
</html>