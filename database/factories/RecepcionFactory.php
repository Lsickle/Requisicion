<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Recepcion;
use App\Models\Producto;
use App\Models\OrdenCompra;

class RecepcionFactory extends Factory
{
    protected $model = Recepcion::class;

    public function definition()
    {
        $producto = Producto::inRandomOrder()->first();
        $oc = OrdenCompra::inRandomOrder()->first();
        $cantidad = $this->faker->numberBetween(1, 50);
        $cantidad_recibido = $this->faker->numberBetween(0, $cantidad);

        return [
            'orden_compra_id' => $oc->id ?? 1,
            'producto_id' => $producto->id ?? 1,
            'cantidad' => $cantidad,
            'cantidad_recibido' => $cantidad_recibido,
            'reception_user' => session('user.name') ?? session('user.email') ?? 'seed-user',
            'fecha' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
        ];
    }
}
