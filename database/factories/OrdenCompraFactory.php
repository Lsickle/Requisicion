<?php

namespace Database\Factories;

use App\Models\Requisicion;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrdenCompraFactory extends Factory
{
    protected $model = \App\Models\OrdenCompra::class;

    public function definition(): array
    {
        // Obtener una requisición existente o crear una nueva
        $requisicion = Requisicion::inRandomOrder()->first() ?? Requisicion::factory()->create();
        $date = $this->faker->dateTimeBetween('-6 months', 'now');

        return [
            'requisicion_id' => $requisicion->id,
            'observaciones'  => $this->faker->optional()->sentence(8),
            'date_oc'        => $date->format('Y-m-d'),
            'methods_oc'     => $this->faker->optional()->randomElement(['Efectivo', 'Transferencia']),
            'plazo_oc'       => $this->faker->optional()->randomElement(['Contado', '30 días', '45 días']),
            'order_oc'       => 'OC-' . $this->faker->unique()->numerify('#####'),
            'created_at'     => $date,
            'updated_at'     => $date,
        ];
    }
}