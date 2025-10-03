<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\centro;
use App\Models\producto;
use App\Models\requisicion;
use Illuminate\Support\Facades\DB;

class CentroProductoSeeder extends Seeder
{
    public function run()
    {
        // Obtener todas las requisiciones, productos y centros
        $requisiciones = Requisicion::all();
        $productos = Producto::all();
        $centros = Centro::all();

        // Para cada requisición, asignar productos a centros
        foreach ($requisiciones as $requisicion) {
            // Seleccionar 3-8 productos aleatorios para esta requisición
            $productosAleatorios = $productos->random(rand(3, 8));
            
            foreach ($productosAleatorios as $producto) {
                // Para cada producto, asignar a 1-3 centros aleatorios
                $centrosAleatorios = $centros->random(rand(1, 3));
                
                foreach ($centrosAleatorios as $centro) {
                    DB::table('centro_producto')->insert([
                        'requisicion_id' => $requisicion->id,
                        'producto_id' => $producto->id,
                        'centro_id' => $centro->id,
                        'amount' => rand(1, 100),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }

        $this->command->info('¡Relaciones centro-producto-requisicion creadas exitosamente!');
    }
}