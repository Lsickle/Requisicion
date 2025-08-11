<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generar 50 productos con datos de prueba usando la Factory
        Producto::factory()->count(50)->create();

        // Obtener productos en orden ascendente por id
        $productos = Producto::orderBy('id', 'asc')->get();

        // Mostrar en consola para verificar el orden
        foreach ($productos as $producto) {
            echo $producto->id . ' - ' . $producto->name_produc . PHP_EOL;
        }
    }
}
