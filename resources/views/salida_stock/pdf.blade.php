<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salida de Stock - {{ $numero }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 14px;
            color: #666;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h3 {
            font-size: 14px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        .info-value {
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        table .number {
            text-align: right;
        }
        .firma-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .firma-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 40px;
        }
        .firma-box {
            width: 45%;
            text-align: center;
        }
        .firma-box img {
            max-width: 200px;
            max-height: 80px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
        }
        .firma-line {
            border-top: 1px solid #333;
            padding-top: 5px;
            font-size: 11px;
        }
        .observaciones {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .observaciones h4 {
            font-size: 12px;
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        @media print {
            body {
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SALIDA DE STOCK</h1>
        <div class="subtitle">Documento de Extracción de Inventario</div>
    </div>

    <div class="info-section">
        <h3>Información General</h3>
        <div class="info-row">
            <span class="info-label">Número de documento:</span>
            <span class="info-value">{{ $numero }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fecha:</span>
            <span class="info-value">{{ $fecha }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Hora:</span>
            <span class="info-value">{{ $hora }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Usuario:</span>
            <span class="info-value">{{ $userName }}</span>
        </div>
    </div>

    <div class="info-section">
        <h3>Productos Extraídos</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th class="number">Cantidad</th>
                    <th>Unidad</th>
                    <th class="number">Stock Anterior</th>
                    <th class="number">Stock Nuevo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productos as $index => $p)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $p['producto_nombre'] }}</td>
                    <td class="number">{{ $p['cantidad'] }}</td>
                    <td>{{ $p['unidad'] ?? 'UND' }}</td>
                    <td class="number">{{ $p['stock_anterior'] }}</td>
                    <td class="number">{{ $p['stock_nuevo'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="number"><strong>Total:</strong></td>
                    <td class="number"><strong>{{ array_sum(array_column($productos, 'cantidad')) }}</strong></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($observaciones)
    <div class="observaciones">
        <h4>Observaciones:</h4>
        <p>{{ $observaciones }}</p>
    </div>
    @endif

    <div class="firma-section">
        <div class="firma-container">
            <div class="firma-box">
                @if($firma)
                <img src="{{ $firma }}" alt="Firma">
                @endif
                <div class="firma-line">
                    {{ $nombreFirma }}<br>
                    Fecha: {{ $fecha }}
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Documento generado automáticamente el {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Sistema de Gestión de Requisiciones</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>