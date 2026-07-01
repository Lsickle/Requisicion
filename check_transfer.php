<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check last transferencia
$transferencias = App\Models\Transferencia::latest()->limit(5)->get();
echo "=== Últimas 5 transferencias ===\n";
foreach($transferencias as $t) {
    echo "ID: {$t->id}, Origen: {$t->bodega_origen_id}, Destino: {$t->bodega_destino_id}, Producto: {$t->producto_id}, Cantidad: {$t->cantidad}, Estado: {$t->estado}, Fecha: {$t->created_at}\n";
}

// Check inventario_bodega for producto 42 (Aromatica)
echo "\n=== Inventario para Aromatica (producto 42) ===\n";
$inventarios = App\Models\InventarioBodega::where('producto_id', 42)->get();
foreach($inventarios as $inv) {
    $bodega = $inv->bodega->name_centro ?? 'N/A';
    echo "Bodega: {$bodega} (ID: {$inv->bodega_id}), Cantidad: {$inv->cantidad}\n";
}
