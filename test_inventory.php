<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$inv = App\Models\InventarioBodega::where('cantidad','>',0)->with('bodega','producto')->limit(10)->get();
foreach($inv as $item) {
    echo "Bodega: " . ($item->bodega->name_centro ?? 'N/A') . " | Producto: " . ($item->producto->name_produc ?? 'N/A') . " | Cantidad: " . $item->cantidad . "\n";
}
