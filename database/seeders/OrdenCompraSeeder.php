<?php

namespace Database\Seeders;

use App\Models\OrdenCompra;
use App\Models\Requisicion;
use Illuminate\Database\Seeder;

class OrdenCompraSeeder extends Seeder
{
    public function run(): void
    {
        // Verificar si hay requisiciones, si no, crear algunas
        if (Requisicion::count() == 0) {
            $this->call(RequisicionSeeder::class);
        }

        $requisiciones = Requisicion::all();

        foreach ($requisiciones as $requisicion) {
            // Verificar si ya existe una orden de compra para esta requisición
            $existeOrden = OrdenCompra::where('requisicion_id', $requisicion->id)->exists();
            
            if (!$existeOrden) {
                OrdenCompra::create([
                    'requisicion_id' => $requisicion->id,
                    'created_at' => $requisicion->created_at ?? now(),
                    'updated_at' => $requisicion->updated_at ?? now()
                ]);
            }
        }

        $this->command->info('¡Órdenes de compra creadas exitosamente!');
    }
}