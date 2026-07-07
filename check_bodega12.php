<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Inventario actual en bodega 12 ===\n";
$inv = App\Models\InventarioBodega::where('bodega_id', 12)->where('cantidad', '>', 0)->with('producto')->orderBy('cantidad', 'desc')->get();
foreach($inv as $i) {
    echo "PID: " . $i->producto_id . " (" . $i->producto->name_produc . ") = " . $i->cantidad . "\n";
}
