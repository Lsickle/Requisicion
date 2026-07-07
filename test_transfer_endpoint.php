<?php
require 'vendor/autoload.php';

$client = new GuzzleHttp\Client();
try {
    $response = $client->post('http://127.0.0.1:8000/inventario/transferencia', [
        'json' => [
            'bodega_origen_id' => 12,
            'bodega_destino_id' => 13,
            'producto_id' => 42,
            'cantidad' => 1,
            'nombre_origen' => 'Test',
            'cedula_origen' => '123',
            'firma_origen' => 'data:image/png;base64,iVBORw0KGgo=',
            'nombre_destino' => 'Test Dest',
            'cedula_destino' => '456',
            'firma_destino' => 'data:image/png;base64,iVBORw0KGgo=',
        ],
        'headers' => ['X-CSRF-TOKEN' => 'test']
    ]);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Body: " . $response->getBody() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
