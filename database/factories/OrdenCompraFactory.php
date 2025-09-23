<?php

namespace Database\Factories;

use App\Models\Requisicion;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrdenCompraFactory extends Factory
{
    protected $model = \App\Models\OrdenCompra::class;

    public function definition(): array
    {
        return [
            'requisicion_id' => Requisicion::factory(),
            'observaciones'  => $this->faker->optional()->sentence(),
            'date_oc'        => $this->faker->date(),
            'methods_oc'     => $this->faker->randomElement(['Transferencia','Efectivo','Crédito']),
            'plazo_oc'       => $this->faker->randomElement(['30 días','15 días','Contado']),
            'order_oc'       => 'OC-' . $this->faker->unique()->numberBetween(1000,9999) . '-' . now()->format('Ymd'),
            'validation_hash' => null,
            'pdf_file'       => null,
        ];
    }
}