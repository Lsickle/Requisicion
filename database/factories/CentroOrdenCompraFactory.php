<?php

namespace Database\Factories;

use App\Models\Centro;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\Factory;

class CentroOrdenCompraFactory extends Factory
{
    public function definition(): array
    {
        // Obtener un registro aleatorio de producto_requisicion
        $productoRequisicion = DB::table('producto_requisicion')
            ->inRandomOrder()
            ->first();

        return [
            'producto_requisicion_id' => $productoRequisicion->id,
            'centro_id' => Centro::factory(),
            'rc_amount' => $this->faker->randomFloat(2, 1, $productoRequisicion->pr_amount),
        ];
    }
}
