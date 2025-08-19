<!DOCTYPE html>
<html>
<head>
    <title>Email de prueba</title>
</head>
<body>
    <h1>¡Funciona!</h1>
    <p>Este es un email de prueba enviado desde {{ config('app.name') }}.</p>
    <p>Fecha: {{ now() }}</p>
</body>
</html>