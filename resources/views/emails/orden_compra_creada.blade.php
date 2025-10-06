<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Orden de Compra Creada</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111
        }

        .card {
            max-width: 760px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px
        }

        .h1 {
            margin: 0 0 10px;
            font-size: 20px
        }

        .p {
            margin: 0 0 8px
        }

        .meta {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            margin: 14px 0
        }

        .btn {
            display: inline-block;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 6px
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px
        }

        th,
        td {
            border-top: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left
        }

        th {
            background: #f3f4f6
        }

        .small {
            color: #6b7280;
            font-size: 12px;
            margin-top: 14px
        }
    </style>
</head>

<body>
    <div class="card">
        <h1 class="h1">Orden de compra creada</h1>
        <p class="p">Se ha creado la orden <strong>#{{ $orden->order_oc ?? ('OC-'.$orden->id) }}</strong>.</p>

        <div class="meta">
            <p class="p"><strong>Requisición:</strong> #{{ $orden->requisicion_id }}</p>
            <p class="p"><strong>Proveedor:</strong> {{ optional(optional($orden->ordencompraProductos->first())->proveedor)->prov_name ?? 'N/A' }}</p>
            <p class="p"><strong>Fecha:</strong> {{ $orden->created_at ? $orden->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</p>
            <p class="p"><strong>Creada por (OC):</strong> {{ $orden->oc_user ?? $orden->user_name ?? $orden->name_user ?? $orden->email_user ?? 'N/A' }}</p>
        </div>

        @if($orden->ordencompraProductos && $orden->ordencompraProductos->count())
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Unidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orden->ordencompraProductos as $p)
                <tr>
                    <td>{{ optional($p->producto)->name_produc ?? 'Producto eliminado' }}</td>
                    <td>{{ $p->total }}</td>
                    <td>{{ optional($p->producto)->unit_produc }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <p class="small">Este mensaje fue generado automáticamente por el sistema de órdenes de compra.</p>
    </div>
</body>

</html>