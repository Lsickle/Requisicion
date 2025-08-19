<!DOCTYPE html>
<html>

<head>
    <title>Nueva Requisición Creada #{{ $requisicion->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 1px solid #e9ecef; }
        .content { padding: 20px 0; }
        .details { margin-bottom: 20px; }
        .detail-item { margin-bottom: 10px; }
        .detail-label { font-weight: bold; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef; text-align: center; font-size: 0.9em; color: #6c757d; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Nueva Requisición Creada</h1>
            <p>Número de Requisición: #{{ $requisicion->id }}</p>
        </div>

        <div class="content">
            <div class="details">
                <div class="detail-item">
                    <span class="detail-label">Prioridad:</span>
                    <span>{{ ucfirst($requisicion->prioridad_requisicion) }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Tipo:</span>
                    <span>{{ $requisicion->Recobrable }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Justificación:</span>
                    <span>{{ $requisicion->justify_requisicion }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Cantidad Total:</span>
                    <span>{{ $requisicion->amount_requisicion }}</span>
                </div>
            </div>

            <p>Se ha creado una nueva requisición en el sistema. Puedes ver los detalles completos accediendo al sistema o descargando el PDF adjunto.</p>
        </div>

        <div class="footer">
            <p>Este es un mensaje automático, por favor no responda a este correo.</p>
            <p>&copy; {{ date('Y') }} Sistema de Requisiciones</p>
        </div>
    </div>
</body>

</html>