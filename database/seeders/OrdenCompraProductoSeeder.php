<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\OrdenCompra;
use App\Models\Requisicion;
use Illuminate\Support\Facades\DB;

class OrdenCompraProductoSeeder extends Seeder
{
    public function run(): void
    {
        if (Producto::count() == 0) {
            $this->call(ProductoSeeder::class);
        }

        if (OrdenCompra::count() == 0) {
            $this->call(OrdenCompraSeeder::class);
        }

        $ordenes = OrdenCompra::with(['requisicion.productos'])->get();

        foreach ($ordenes as $orden) {
            // Usar los productos de la requisición asociada
            $productos = $orden->requisicion->productos ?? Producto::inRandomOrder()->limit(rand(1, 5))->get();
            
            foreach ($productos as $producto) {
                DB::table('ordencompra_producto')->insert([
                    'producto_id' => $producto->id,
                    'orden_compra_id' => $orden->id,
                    'po_amount' => rand(1, 20),
                    'precio_unitario' => $producto->price_produc,
                    'observaciones' => 'Generado desde requisición #' . $orden->requisicion->id,
                    'date_oc' => $orden->requisicion->date_requisicion,
                    'methods_oc' => fake()->randomElement(['Transferencia', 'Efectivo', 'Crédito 30 días']),
                    'plazo_oc' => fake()->randomElement(['Contado', '30 días', '60 días']),
                    'order_oc' => 'OC-' . str_pad($orden->id, 5, '0', STR_PAD_LEFT),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('¡Productos de requisición asignados a órdenes de compra exitosamente!');
    }
}