<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cambio de Estatus - Requisición #{{ $requisicion->id }}</title>
</head>
<body>
    <h2>Requisición #{{ $requisicion->id }}</h2>
    <p>Se ha actualizado el estatus de la requisición.</p>

    <p><strong>Nuevo estatus:</strong> {{ $estatus->estatus->status_name ?? 'Desconocido' }}</p>
    <p><strong>Fecha:</strong> {{ $estatus->created_at->format('d/m/Y H:i') }}</p>

    <h3>Detalles de la requisición:</h3>
    <ul>
        @foreach($requisicion->productos as $producto)
            <li>{{ $producto->name_produc }} ({{ $producto->pivot->pr_amount }} {{ $producto->unit_produc }})</li>
        @endforeach
    </ul>

    <p>Saludos,</p>
    <p>El sistema de requisiciones</p>
</body>
</html>
