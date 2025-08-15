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
                'date_requisicion' => '2025-01-10',
                'justify_requisicion' => 'se nescesita adquisición de equipos para la nueva sede.',
                'detail_requisicion' => 'no',
                'prioridad_requisicion' => 'Alta',
                'amount_requisicion' => 15,
                'Recobrable' => 'Sí'
            ],
            [
                'date_requisicion' => '2025-02-05',
                'justify_requisicion' => 'Reposición de herramientas dañadas.',
                'detail_requisicion' => 'que sean dorados.',
                'prioridad_requisicion' => 'Media',
                'amount_requisicion' => 25,
                'Recobrable' => 'No'
            ],
            [
                'date_requisicion' => '2025-03-12',
                'justify_requisicion' => 'Abastecimiento de insumos de oficina.',
                'detail_requisicion' => 'marca x',
                'prioridad_requisicion' => 'Baja',
                'amount_requisicion' => 200,
                'Recobrable' => 'Sí'
            ],
            [
                'date_requisicion' => '2025-04-18',
                'justify_requisicion' => 'Materiales de construcción para remodelación.',
                'detail_requisicion' => 'no',
                'prioridad_requisicion' => 'Alta',
                'amount_requisicion' => 50,
                'Recobrable' => 'No'
            ],
            [
                'date_requisicion' => '2025-05-25',
                'justify_requisicion' => 'Equipos de seguridad industrial.',
                'detail_requisicion' => 'con calidad certificada x',
                'prioridad_requisicion' => 'Alta',
                'amount_requisicion' => 100,
                'Recobrable' => 'Sí'
            ]
        ];

        return $this->faker->randomElement($requisiciones);
    }
}
