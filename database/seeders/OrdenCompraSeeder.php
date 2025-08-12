<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use Carbon\Carbon;

class OrdenCompraSeeder extends Seeder
{
    public function run()
    {
        $metodosPago = ['Contado', 'Crédito 30 días', 'Crédito 60 días', 'Transferencia'];
        $plazos = ['Inmediato', '15 días', '30 días', '45 días', '60 días'];
        $estados = ['pendiente', 'aprobada', 'rechazada', 'completada'];

        // Crear 15 órdenes de compra
        for ($i = 0; $i < 15; $i++) {
            $orden = OrdenCompra::create([
                'proveedor_id' => Proveedor::inRandomOrder()->first()->id,
                'date_oc' => Carbon::now()->subDays(rand(1, 60)),
                'methods_oc' => $metodosPago[array_rand($metodosPago)],
                'plazo_oc' => $plazos[array_rand($plazos)],
                'order_oc' => $i + 1, // Solo el número
                'estado' => $estados[array_rand($estados)],
                'observaciones' => 'Observación ' . ($i + 1),
            ]);

            // Asignar 1-5 productos aleatorios a cada orden
            $productos = \App\Models\Producto::inRandomOrder()
                ->limit(rand(1, 5))
                ->get();

            foreach ($productos as $producto) {
                $orden->productos()->attach($producto->id, [
                    'po_amount' => rand(1, 100),
                    'precio_unitario' => $producto->price_produc,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $this->command->info('¡15 órdenes de compra con productos creadas exitosamente!');
    }
}
