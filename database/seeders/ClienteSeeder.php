<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\cliente;

class ClienteSeeder extends Seeder
{
    public function run()
    {
        Cliente::factory()->count(20)->create();
        $this->command->info('¡20 clientes de prueba creados exitosamente!');
    }
}