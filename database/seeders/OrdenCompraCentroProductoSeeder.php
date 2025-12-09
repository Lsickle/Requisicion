<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\DB;

class OrdenCompraCentroProductoSeeder extends Seeder
{
    public function run(): void
    {
        // Si no hay productos en OCs, intenta generarlos primero
        if (DB::table('ordencompra_producto')->count() === 0) {
            $this->call(OrdenCompraProductoSeeder::class);
        }

        $ordenes = OrdenCompra::all();
        foreach ($ordenes as $oc) {
            if (empty($oc->requisicion_id)) {
                // No se puede distribuir por centro sin requisición
                $this->command->warn("OC #{$oc->id} sin requisición asociada. Saltando distribución por centros...");
                continue;
            }

            $pivotProductos = DB::table('ordencompra_producto')
                ->where('orden_compras_id', $oc->id)
                ->get();

            if ($pivotProductos->isEmpty()) {
                $this->command->warn("OC #{$oc->id} no tiene productos en ordencompra_producto. Saltando...");
                continue;
            }

            foreach ($pivotProductos as $pp) {
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
