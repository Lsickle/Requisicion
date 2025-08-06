<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Cliente;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'cli_name' => $this->faker->company,
            'cli_nit' => $this->faker->unique()->numerify('###########'),
            'cli_descrip' => $this->faker->paragraph,
            'cli_contacto' => $this->faker->name,
            'cli_telefono' => $this->faker->phoneNumber,
            'cli_mail' => $this->faker->unique()->companyEmail,
        ];
    }
}