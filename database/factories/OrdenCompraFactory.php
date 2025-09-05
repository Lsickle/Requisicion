<?php

namespace Database\Factories;

use App\Models\Requisicion;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrdenCompraFactory extends Factory
{
    protected $model = \App\Models\OrdenCompra::class;

    public function definition(): array
    {
        // Obtener una requisición existente o crear una nueva
        $requisicion = Requisicion::inRandomOrder()->first() ?? Requisicion::factory()->create();
        
        // Obtener un proveedor existente o crear uno nuevo
        $proveedor = Proveedor::inRandomOrder()->first() ?? Proveedor::factory()->create();

        return [
            'requisicion_id' => $requisicion->id,
            'proveedor_id' => $proveedor->id,
            'observaciones' => $this->faker->optional()->sentence(),
            'date_oc' => $this->faker->date(),
            'methods_oc' => $this->faker->randomElement(['Transferencia', 'Cheque', 'Efectivo', 'Tarjeta']),
            'plazo_oc' => $this->faker->randomElement(['30 días', '60 días', '90 días', 'Contado']),
            'order_oc' => 'OC-' . str_pad($this->faker->unique()->numberBetween(1, 1000), 6, '0', STR_PAD_LEFT),
            'created_at' => $requisicion->date_requisicion ?? now(),
            'updated_at' => $requisicion->date_requisicion ?? now(),
        ];
    }
}