<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\DB;

class OrdenCompraCentroProductoSeeder extends Seeder
{
    public function run(): void
    {
        $ordenes = OrdenCompra::all();
        foreach ($ordenes as $oc) {
            // Traer productos de la OC
            $pivotProductos = DB::table('ordencompra_producto')
                ->where('orden_compras_id', $oc->id)
                ->get();

            foreach ($pivotProductos as $pp) {
                // Obtener distribución de centros desde centro_producto para la requisición y producto
                $centros = DB::table('centro_producto')
                    ->where('requisicion_id', $oc->requisicion_id)
                    ->where('producto_id', $pp->producto_id)
                    ->get();

                foreach ($centros as $c) {
                    $exists = DB::table('ordencompra_centro_producto')
                        ->where('orden_compra_id', $oc->id)
                        ->where('producto_id', $pp->producto_id)
                        ->where('centro_id', $c->centro_id)
                        ->exists();

                    if (!$exists) {
                        DB::table('ordencompra_centro_producto')->insert([
                            'orden_compra_id' => $oc->id,
                            'producto_id'     => $pp->producto_id,
                            'centro_id'       => $c->centro_id,
                            'amount'          => (int) $c->amount,
                            'created_at'      => $oc->created_at ?? now(),
                            'updated_at'      => $oc->created_at ?? now(),
                        ]);
                    }
                }
            }
        }

        $this->command->info('Distribución ordencompra_centro_producto creada según centro_producto.');
    }
}
