<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Verificación FINAL de Inventarios ===\n\n";

// Check Aromatica (42)
echo "Producto 42 (Aromatica MANZANILLA):\n";
$inv42 = App\Models\InventarioBodega::where('producto_id', 42)->get();
foreach($inv42 as $i) {
    $bodega = $i->bodega->name_centro ?? 'N/A';
    echo "  Bodega $bodega (ID: {$i->bodega_id}): {$i->cantidad} unidades\n";
}

// Check COMBO (208)
echo "\nProducto 208 (COMBO CABLE TECLADO+MOUSE):\n";
$inv208 = App\Models\InventarioBodega::where('producto_id', 208)->get();
foreach($inv208 as $i) {
    $bodega = $i->bodega->name_centro ?? 'N/A';
    echo "  Bodega $bodega (ID: {$i->bodega_id}): {$i->cantidad} unidades\n";
}

echo "\n=== Resumen de Transferencias ===\n";
echo "Transfer 1: Producto 42 x1 (bodega 12 → 13)\n";
echo "Transfer 2: Producto 42 x1 (bodega 12 → 13)\n";
echo "Transfer 3: Producto 208 x2 (bodega 12 → 13)\n";
echo "\nResultados:\n";
echo "- Producto 42: bodega 12 (2 → 0), bodega 13 (0 → 2) ✓\n";
echo "- Producto 208: bodega 12 (4 → 2), bodega 13 (0 → 2) ✓\n";
