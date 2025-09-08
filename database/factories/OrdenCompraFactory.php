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

        return [
            'requisicion_id' => $requisicion->id,
            'created_at' => $requisicion->created_at ?? now(),
            'updated_at' => $requisicion->updated_at ?? now(),
        ];
    }
}