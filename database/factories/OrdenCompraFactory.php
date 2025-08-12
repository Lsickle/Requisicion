<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\OrdenCompra;
use App\Models\Proveedor;

class OrdenCompraFactory extends Factory
{
    protected $model = OrdenCompra::class;

    public function definition(): array
    {
        static $orderNumber = 1;

        $metodosPago = ['Contado', 'Crédito 30 días', 'Crédito 60 días', 'Transferencia'];
        $plazos = ['Inmediato', '15 días', '30 días', '45 días', '60 días'];
        $estados = ['pendiente', 'aprobada', 'rechazada', 'completada'];

        return [
            'proveedor_id' => Proveedor::factory(),
            'date_oc' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'methods_oc' => $this->faker->randomElement($metodosPago),
            'plazo_oc' => $this->faker->randomElement($plazos),
            'order_oc' => 'OC-' . str_pad($orderNumber++, 5, '0', STR_PAD_LEFT),
            'estado' => $this->faker->randomElement($estados),
            'observaciones' => $this->faker->sentence(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (OrdenCompra $orden) {
            // Asignar 1-5 productos aleatorios
            $productos = \App\Models\Producto::factory()
                ->count(rand(1, 5))
                ->create();

            foreach ($productos as $producto) {
                $orden->productos()->attach($producto->id, [
                    'po_amount' => rand(1, 100),
                    'precio_unitario' => $producto->price_produc,
                ]);
            }
        });
    }
}