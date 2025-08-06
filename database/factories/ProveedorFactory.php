<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Proveedor;

class ProveedorFactory extends Factory
{
    protected $model = Proveedor::class;

    public function definition(): array
    {
        return [
            'prov_name' => $this->faker->company,
            'prov_descrip' => $this->faker->paragraph,
            'prov_nit' => $this->faker->unique()->numerify('###########'),
            'prov_name_c' => $this->faker->name,
            'prov_phone' => $this->faker->phoneNumber,
            'prov_adress' => $this->faker->address,
            'prov_city' => $this->faker->city,
        ];
    }
}