<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrdenCompra;
use App\Models\ProductoRequisicion;
use Illuminate\Support\Facades\DB;

class OrdenCompraProductoSeeder extends Seeder
{
    public function run()
    {
        // Verificar que existan órdenes de compra y productos en requisición
        if (OrdenCompra::count() == 0) {
            $this->call(OrdenCompraSeeder::class);
        }

        if (DB::table('producto_requisicion')->count() == 0) {
            $this->call(RequisicionSeeder::class);
        }

        // Obtener todas las relaciones producto_requisicion
        $productosRequisicion = DB::table('producto_requisicion')->get();

        foreach ($productosRequisicion as $pr) {
            // Asignar a una orden de compra aleatoria
            $ordenCompra = OrdenCompra::inRandomOrder()->first();

            DB::table('ordencompra_producto')->insert([
                'producto_requisicion_id' => $pr->id,
                'orden_compra_id' => $ordenCompra->id,
                'po_amount' => $pr->pr_amount, // Usar la misma cantidad que en la requisición
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->command->info('¡Relaciones orden-compra-producto creadas exitosamente!');
    }
}