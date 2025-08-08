<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Centro;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class CentroProductoSeeder extends Seeder
{
    public function run()
    {
        // Obtener todos los productos y centros
        $productos = Producto::all();
        $centros = Centro::all();

        // Para cada producto, asignar a 1-3 centros aleatorios
        foreach ($productos as $producto) {
            $centrosAleatorios = $centros->random(rand(1, 3));
            
            foreach ($centrosAleatorios as $centro) {
                DB::table('centro_producto')->insert([
                    'producto_id' => $producto->id,
                    'centro_id' => $centro->id,
                    'amount' => rand(1, 100),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $this->command->info('¡Relaciones centro-producto creadas exitosamente!');
    }
}