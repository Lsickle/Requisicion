<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\OrdenCompra;

class OrdenCompraFactory extends Factory
{
    protected $model = OrdenCompra::class;

    public function definition(): array
    {
        static $orderNumber = 1000; // autoincremento manual

        $ordenes = [
            [
                'date_oc' => '2025-01-15',
                'methods_oc' => 'Contado',
                'plazo_oc' => '15 días',
            ],
            [
                'date_oc' => '2025-02-01',
                'methods_oc' => 'Crédito 30 días',
                'plazo_oc' => '30 días',
            ],
            [
                'date_oc' => '2025-03-10',
                'methods_oc' => 'Crédito 60 días',
                'plazo_oc' => '45 días',
            ],
            [
                'date_oc' => '2025-04-05',
                'methods_oc' => 'Contado',
                'plazo_oc' => '15 días',
            ],
            [
                'date_oc' => '2025-05-20',
                'methods_oc' => 'Crédito 30 días',
                'plazo_oc' => '30 días',
            ]
        ];

        $orden = $this->faker->randomElement($ordenes);

        return [
            'date_oc' => $orden['date_oc'],
            'methods_oc' => $orden['methods_oc'],
            'plazo_oc' => $orden['plazo_oc'],
            'order_oc' => $orderNumber++, // autoincrementa en el numero en cada registro
        ];
    }
}
