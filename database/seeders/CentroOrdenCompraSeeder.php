<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Centro;
use App\Models\Producto;

class CentroOrdenCompraSeeder extends Seeder
{
    public function run()
    {
        if (Centro::count() == 0) {
            $this->call(CentroSeeder::class);
        }

        if (Producto::count() == 0) {
            $this->call(ProductoSeeder::class);
        }

        $productos = Producto::all();
        $centros = Centro::all();

        foreach ($productos as $producto) {
            $centrosAleatorios = $centros->random(rand(1, min(3, $centros->count())));

            foreach ($centrosAleatorios as $centro) {
                $producto->centros()->attach($centro->id, [
                    'rc_amount' => rand(1, 100),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $this->command->info('¡Relaciones centro-producto creadas exitosamente!');
    }
}
