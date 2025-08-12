<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Requisición #{{ $requisicion->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; }
        .details { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .signature { margin-top: 50px; }
        .footer { margin-top: 30px; font-size: 12px; text-align: center; }
        .centros-lista { margin-top: 20px; }
        .centros-lista ul { list-style-type: none; padding-left: 0; }
        .centros-lista li { margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">REQUISICIÓN #{{ $requisicion->id }}</div>
        <div>Fecha: {{ $requisicion->date_requisicion->format('d/m/Y') }}</div>
    </div>

    <div class="details">
        <p><strong>Prioridad:</strong> {{ ucfirst($requisicion->prioridad_requisicion) }}</p>
        <p><strong>Recobrable:</strong> {{ $requisicion->Recobreble }}</p>
        <p><strong>Detalles:</strong> {{ $requisicion->detail_requisicion }}</p>
        <p><strong>Justificación:</strong> {{ $requisicion->justify_requisicion }}</p>
    </div>


    <h3>Productos solicitados</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Asignación a centros</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requisicion->productos as $producto)
            <tr>
                <td>{{ $producto->name_produc }}</td>
                <td>{{ $producto->pivot->pr_amount }}</td>
                <td>
                    <ul>
                        @foreach($producto->centros as $centro)
                        <li>{{ $centro->name_centro }} ({{ $centro->pivot->amount }})</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature">
        <p>_________________________</p>
        <p>Solicitante</p>  
    </div>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>