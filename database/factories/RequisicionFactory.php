<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Requisicion;

class RequisicionFactory extends Factory
{
    protected $model = Requisicion::class;

    public function definition(): array
    {
        $operaciones = [
            'MARY KAY', 'COLTABACO', 'ADMINISTRACION', 'CEDI FRIO', 'ORIFLAME',
            'INVENTARIOS', 'HUAWEI', 'MULTICLIENTE 1E', 'OVERHEAD', 'MATTEL',
            'NAOS', 'ORTOPEDICOS', 'COMERCIAL', 'MULTICLIENTE 12G', 'MANTENIMIENTO',
            'SONY', 'TRANSPORTES', 'SEGURIDAD', 'MAC MILLAN', 'TECNOLOGIA',
            'INNOVACION Y DESARROLLO', 'MULTICLIENTE', 'KW COLOMBIA', 'LAFAZENDA',
            'MC MILLAN', 'HSEQ', 'TODOS COMEMOS', 'KIKES', 'MEJORAMIENTO CONTINUO',
            'CALIDAD', 'FRIO', 'ORIFALME', 'COMPRAS', 'ORTOPEDICOS FUTURO',
            'AGROFRUT', 'TALENTO HUMANO', 'SULFOQUIMICA', 'OVERHED'
        ];

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
            'name_user' => $this->faker->name(), // nombre simulado del usuario
            'email_user' => $this->faker->email(), // email simulado del usuario
            'operacion_user' => $this->faker->randomElement($operaciones), // operación/centro de costo aleatorio
        ]);
    }
}