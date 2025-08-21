<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Solicitud de Nuevo Producto</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #3b82f6; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9fafb; padding: 20px; border-radius: 0 0 5px 5px; }
        .detail { margin-bottom: 15px; }
        .label { font-weight: bold; color: #4b5563; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nueva Solicitud de Producto</h1>
        </div>
        <div class="content">
            <div class="detail">
                <span class="label">Producto:</span> {{ $producto->nombre }}
            </div>
            <div class="detail">
                <span class="label">Descripción:</span> {{ $producto->descripcion }}
            </div>
            <div class="detail">
                <span class="label">Fecha de solicitud:</span> {{ $producto->created_at->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</body>
</html>