<?php

namespace Database\Factories;

use App\Models\Requisicion;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrdenCompraFactory extends Factory
{
    protected $model = \App\Models\OrdenCompra::class;

    public function definition(): array
    {
        $orderOc = 'OC-' . $this->faker->unique()->numberBetween(1000,9999) . '-' . now()->format('Ymd');
        // construir subtotal simulado para hash
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $secret = config('app.key') ?? env('APP_KEY', 'basekey');
        $hashSource = '1|' . $orderOc . '|' . number_format($subtotal, 2) . '|' . now()->toDateTimeString();
        $validationHash = hash_hmac('sha256', $hashSource, $secret);

        // intentar usar una requisicion existente
        $requisicionId = Requisicion::inRandomOrder()->value('id') ?: Requisicion::factory();

        return [
            'requisicion_id' => $requisicionId,
            'oc_user' => $this->faker->name(),
            'observaciones'  => $this->faker->optional()->sentence(),
            'date_oc'        => $this->faker->date(),
            'methods_oc'     => $this->faker->randomElement(['Transferencia','Efectivo','Crédito']),
            'plazo_oc'       => $this->faker->randomElement(['30 días','15 días','Contado']),
            'order_oc'       => $orderOc,
            'validation_hash' => $validationHash,
        ];
    }
}