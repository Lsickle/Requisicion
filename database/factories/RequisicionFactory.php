<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Requisicion;

class RequisicionFactory extends Factory
{
    protected $model = Requisicion::class;

    public function definition(): array
    {
        return [
            'date_requisicion' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'justify_requisicion' => $this->faker->paragraph,
            'detail_requisicion' => $this->faker->text,
            'prioridad_requisicion' => $this->faker->randomElement(['Alta', 'Media', 'Baja']),
            'amount_requisicion' => $this->faker->numberBetween(1, 100),
            'Recobreble' => $this->faker->randomElement(['Sí', 'No']),
        ];
    }
}