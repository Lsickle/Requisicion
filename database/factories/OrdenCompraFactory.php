<?php

namespace Database\Factories;

use App\Models\Requisicion;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrdenCompraFactory extends Factory
{
    public function definition(): array
    {
        // Obtener una requisición existente o crear una nueva
        $requisicion = Requisicion::inRandomOrder()->first() ?? Requisicion::factory()->create();
        
        // Obtener un proveedor existente o crear uno nuevo
        $proveedor = Proveedor::inRandomOrder()->first() ?? Proveedor::factory()->create();

        return [
            'requisicion_id' => $requisicion->id,
            'proveedor_id' => $proveedor->id,
            'created_at' => $requisicion->date_requisicion, // Usar la fecha de la requisición
            'updated_at' => $requisicion->date_requisicion,
        ];
    }
}