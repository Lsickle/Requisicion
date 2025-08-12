<?php

namespace Database\Factories;

use App\Models\Centro;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class CentroOrdenCompraFactory extends Factory
{
    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'centro_id' => Centro::factory(),
            'rc_amount' => $this->faker->numberBetween(1, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}