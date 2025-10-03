<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\producto;
use App\Models\proveedor;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si hay proveedores, si no, crear algunos
        if (Proveedor::count() == 0) {
            Proveedor::factory()->count(10)->create();
        }

        // Crear 1 producto
        Producto::factory()->count(10)->create();

        // Mostrar información en consola
        $this->command->info('Productos creados:');
        $this->command->table(
            ['ID', 'Nombre', 'Categoría', 'Proveedor ID', 'Precio', 'IVA'],
            Producto::query()
                ->orderBy('id')
                ->limit(10)
                ->get(['id', 'name_produc', 'categoria_produc', 'proveedor_id', 'price_produc', 'iva'])
                ->toArray()
        );
    }
}