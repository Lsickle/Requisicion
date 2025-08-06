<?php

namespace Database\Factories;

use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_proveedores' => Proveedor::factory(), # Relación con proveedor
            'name_produc' => $this->faker->unique()->words(3, true), # Nombre de producto más realista
            'stock_produc' => $this->faker->numberBetween(0, 500), # Stock entre 0 y 500
            'description_produc' => $this->faker->paragraph(2), # Descripción de 2 párrafos
            'price_produc' => $this->faker->randomFloat(2, 1, 1000), # Precio entre 1 y 1000 con 2 decimales
            'unit_produc' => $this->faker->randomElement(['unidad', 'kg', 'litro', 'caja', 'paquete', 'metro']),
        ];
    }
}