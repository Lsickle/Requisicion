<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Requisicion;

class RequisicionFactory extends Factory
{
    protected $model = Requisicion::class;

    public function definition(): array
    {
        $requisiciones = [
            [
                'justify_requisicion' => 'Se necesita adquisición de equipos para la nueva sede.',
                'detail_requisicion' => 'No aplica',
                'prioridad_requisicion' => 'Alta',
                'amount_requisicion' => 15,
                'Recobrable' => 'Sí'
            ],
            [
                'justify_requisicion' => 'Reposición de herramientas dañadas.',
                'detail_requisicion' => 'Que sean dorados.',
                'prioridad_requisicion' => 'Media',
                'amount_requisicion' => 25,
                'Recobrable' => 'No'
            ],
            [
                'justify_requisicion' => 'Abastecimiento de insumos de oficina.',
                'detail_requisicion' => 'Marca X',
                'prioridad_requisicion' => 'Baja',
                'amount_requisicion' => 200,
                'Recobrable' => 'Sí'
            ],
            [
                'justify_requisicion' => 'Materiales de construcción para remodelación.',
                'detail_requisicion' => 'No aplica',
                'prioridad_requisicion' => 'Alta',
                'amount_requisicion' => 50,
                'Recobrable' => 'No'
            ],
            [
                'justify_requisicion' => 'Equipos de seguridad industrial.',
                'detail_requisicion' => 'Con calidad certificada X',
                'prioridad_requisicion' => 'Alta',
                'amount_requisicion' => 100,
                'Recobrable' => 'Sí'
            ]
        ];

        $req = $this->faker->randomElement($requisiciones);

        return array_merge($req, [
            'user_id' => (string) $this->faker->numberBetween(1, 100), // id simulado de la API
        ]);
    }
}
