<?php

namespace Database\Factories;

use App\Models\Estatus_Requisicion;
use App\Models\Estatus;
use App\Models\Requisicion;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstatusRequisicionFactory extends Factory
{
    protected $model = Estatus_Requisicion::class;

    public function definition()
    {
        $estatus = Estatus::inRandomOrder()->first() ?? Estatus::factory()->create([
            'status_name' => 'Iniciada',
            'status_date' => now(),
        ]);

        $requisicion = Requisicion::inRandomOrder()->first() ?? Requisicion::factory()->create();

        $fecha = $this->faker->dateTimeBetween('-2 months', 'now');

        return [
            'estatus_id' => $estatus->id,
            'requisicion_id' => $requisicion->id,
            'date_update' => $fecha,
            'estatus' => 0,
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ];
    }
}
