<?php

namespace Database\Factories;

use App\Models\Centro;
use App\Models\Producto;
use App\Models\Requisicion;
use Illuminate\Database\Eloquent\Factories\Factory;

class CentroProductoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'requisicion_id' => Requisicion::factory(),
            'producto_id' => Producto::factory(),
            'centro_id' => Centro::factory(),
            'amount' => $this->faker->numberBetween(1, 100),
        ];
    }
}