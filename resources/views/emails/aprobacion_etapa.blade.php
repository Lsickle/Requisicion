<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $subject ?? ('Requisición #'.$requisicion->id) }}</title>
    <style>
        body{font-family: Arial, Helvetica, sans-serif; color:#111;}
        .card{max-width:720px;margin:0 auto;background:#ffffff;border-radius:8px;border:1px solid #e5e7eb;padding:24px}
        .h1{font-size:20px;margin:0 0 12px;font-weight:700;color:#111827}
        .p{margin:0 0 10px;line-height:1.45}
        .meta{background:#f3f4f6;border-radius:6px;padding:12px;margin:14px 0}
        .btn{display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 16px;border-radius:6px}
        .small{color:#6b7280;font-size:12px}
        ul{padding-left:18px}
    </style>
</head>
<body>
    <div class="card">
        <h1 class="h1">Requisición #{{ $requisicion->id }}</h1>
        <p class="p">{{ $mensajePrincipal }}</p>

        <div class="meta">
            <p class="p"><strong>Operación:</strong> {{ $requisicion->operacion_user ?? 'N/A' }}</p>
            <p class="p"><strong>Solicitante:</strong> {{ $requisicion->name_user ?? 'N/A' }}</p>
            <p class="p"><strong>Prioridad:</strong> {{ ucfirst($requisicion->prioridad_requisicion ?? '') }}</p>
            <p class="p"><strong>Fecha:</strong> {{ optional($estatus->date_update ?? $estatus->created_at)->format('d/m/Y H:i') }}</p>
        </div>

        @if($requisicion->productos && count($requisicion->productos))
            <p class="p"><strong>Productos:</strong></p>
            <ul>
                @foreach($requisicion->productos as $producto)
                    <li>{{ $producto->name_produc }} ({{ $producto->pivot->pr_amount }} {{ $producto->unit_produc }})</li>
                @endforeach
            </ul>
        @endif

        <p class="p">
            <a href="{{ $panelUrl }}" class="btn" target="_blank" rel="noopener">Ir al Panel de Aprobación</a>
            &nbsp; <a href="{{ $detalleUrl }}" class="btn" style="background:#059669" target="_blank" rel="noopener">Ver Detalle</a>
        </p>

        <p class="small">Este mensaje fue generado automáticamente por el sistema de requisiciones.</p>
    </div>
</body>
</html>