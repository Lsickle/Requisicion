<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Proveedor;

class ProductoSeeder extends Seeder
{
    public function run()
    {
        // Verificar si hay proveedores
        if (Proveedor::count() == 0) {
            // Crear 5 proveedores de prueba
            Proveedor::factory()->count(5)->create();
        }

        // Crear 20 productos de prueba
        Producto::factory()->count(20)->create();
        
        $this->command->info('¡Productos y proveedores de prueba creados exitosamente!');
    }
}