<?php

namespace Database\Factories;

use App\Models\Centro;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class CentroProductoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'centro_id' => Centro::factory(),
            'amount' => $this->faker->randomFloat(2, 1, 100),
        ];
    }
}