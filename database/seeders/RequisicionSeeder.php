<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Requisicion;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RequisicionSeeder extends Seeder
{
    public function run()
    {
        // Crear exactamente 20 requisiciones
        $requisiciones = Requisicion::factory()->count(43)->create();

        foreach ($requisiciones as $requisicion) {
            // Fecha base aleatoria (últimos 120 días)
            $fechaBase = now()->subDays(rand(5, 120))->startOfDay()->addMinutes(rand(0, 1200));
            DB::table('requisicion')->where('id', $requisicion->id)->update([
                'created_at' => $fechaBase,
                'updated_at' => $fechaBase,
            ]);

            // Asociar entre 1 y 5 productos con cantidades coherentes (siempre >= 1)
            $productos = Producto::inRandomOrder()->limit(rand(1, 5))->get();
            if ($productos->isEmpty()) {
                // Si no hay productos, lanzamos excepción para no dejar requisiciones incompletas
                throw new \RuntimeException('No hay productos para asociar a las requisiciones. Ejecute primero el seeder de productos.');
            }

            $totalCantidad = 0;

            foreach ($productos as $producto) {
                $cantidad = rand(3, 20);
                DB::table('producto_requisicion')->insert([
                    'id_producto'   => $producto->id,
                    'id_requisicion'=> $requisicion->id,
                    'pr_amount'     => $cantidad,
                    'created_at'    => $fechaBase,
                    'updated_at'    => $fechaBase,
                ]);
                $totalCantidad += $cantidad;
            }

            // Guardar el total de cantidades de la requisición (campo es texto)
            DB::table('requisicion')->where('id', $requisicion->id)->update([
                'amount_requisicion' => (string) $totalCantidad,
            ]);
        }

        $this->command->info('¡20 requisiciones con productos creadas y totalizadas!');
    }
}