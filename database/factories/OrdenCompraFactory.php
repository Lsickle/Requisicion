<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\OrdenCompra;

class OrdenCompraFactory extends Factory
{
    protected $model = OrdenCompra::class;

    public function definition(): array
    {
        return [
            'date_oc' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'methods_oc' => $this->faker->randomElement(['Contado', 'Crédito 30 días', 'Crédito 60 días']),
            'plazo_oc' => $this->faker->randomElement(['15 días', '30 días', '45 días']),
            'order_oc' => $this->faker->unique()->numberBetween(1000, 9999),
        ];
    }
}