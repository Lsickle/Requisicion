<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $now = now();

        $units = ['unidad', 'kg', 'caja', 'resma', 'paquete', 'litro'];
        $categories = ['Insumos', 'Equipos', 'Ferretería', 'Oficina', 'Limpieza', 'Electrónica'];

        $data = [];
        for ($i = 0; $i < 20; $i++) {
            $name = ucfirst($faker->words($faker->numberBetween(1,4), true));

            // generar SKU alfanumérico único aproximado de 8 caracteres
            $sku = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', $name) . bin2hex(random_bytes(3)), 0, 8));

            $data[] = [
                'sku' => $sku,
                'categoria_produc' => $faker->randomElement($categories),
                'name_produc' => $name,
                'stock_produc' => $faker->numberBetween(0, 500),
                'description_produc' => $faker->sentence(8),
                // IVA as percentage number (e.g. 0, 5, 19)
                'iva' => $faker->randomElement([0, 5.0, 10.0, 19.0]),
                'unit_produc' => $faker->randomElement($units),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($data)) {
            DB::table('productos')->insert($data);
        }
    }
}
