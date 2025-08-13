<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\DB;

class OrdenCompraProductoSeeder extends Seeder
{
    public function run()
    {
        // Crear productos si no existen
        if (Producto::count() == 0) {
            $this->call(ProductoSeeder::class);
        }

        // Crear órdenes de compra si no existen
        if (OrdenCompra::count() == 0) {
            for ($i = 0; $i < rand(5, 10); $i++) {
                OrdenCompra::create([
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // Obtener IDs existentes de órdenes de compra
        $ordenesIds = OrdenCompra::pluck('id')->toArray();

        // Insertar registros en la tabla pivote
        foreach (Producto::all() as $producto) {
            for ($i = 0; $i < rand(1, 3); $i++) {
                DB::table('ordencompra_producto')->insert([
                    'producto_id'     => $producto->id,
                    'orden_compra_id' => fake()->randomElement($ordenesIds),
                    'po_amount'       => rand(1, 100),
                    'precio_unitario' => $producto->price_produc ?? rand(1000, 5000),
                    'observaciones'   => 'Observación para producto ' . $producto->id,
                    'date_oc'         => now()->subDays(rand(0, 30))->toDateString(),
                    'methods_oc'      => fake()->randomElement(['Transferencia', 'Caja menor']),
                    'plazo_oc'        => fake()->randomElement(['Pago de contado', 'Credito 30 dias', 'Credito 45 dias']),
                    'order_oc'        => rand(1, 99999),
                    'created_at'      => now(),
                    'updated_at'      => now()
                ]);
            }
        }

        $this->command->info('¡Registros en ordencompra_producto creados exitosamente!');
    }
}
