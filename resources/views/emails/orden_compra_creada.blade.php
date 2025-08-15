<!DOCTYPE html>
<html>

<head>
    <title>Actualización de Estatus - Orden de Compra #{{ $orden->id }}</title>
</head>

<body>
    <h1>Estatus Actualizado - Orden de compra #{{ $orden->id }}</h1>
    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam et aliquet arcu. Curabitur et faucibus ex, sed
        pharetra dolor. Fusce sit amet varius augue, sollicitudin fringilla justo. Nam ut dui iaculis, facilisis felis
        eget, vehicula mauris. Ut sit amet pharetra mi, ut malesuada eros. Pellentesque habitant morbi tristique
        senectus et netus et malesuada fames ac turpis egestas. Aenean fermentum pharetra orci. Etiam non erat id lectus
        facilisis blandit quis auctor tortor.</p>
    <a href="{{ route('exportar.ordenes-compra', ['id' => $orden->id]) }}">Descargar</a>
</body>

</html>