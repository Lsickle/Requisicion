<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Requisicion;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class RequisicionSeeder extends Seeder
{
    public function run()
    {
        // Crear 20 requisiciones
        $requisiciones = Requisicion::factory()->count(20)->create();

        // Asociar productos a cada requisición
        foreach ($requisiciones as $requisicion) {
            $productos = Producto::inRandomOrder()->limit(rand(1, 5))->get();
            
            foreach ($productos as $producto) {
                DB::table('producto_requisicion')->insert([
                    'id_producto' => $producto->id,
                    'id_requisicion' => $requisicion->id,
                    'pr_amount' => rand(1, 20),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $this->command->info('¡20 requisiciones con productos creadas exitosamente!');
    }
}