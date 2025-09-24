<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recepcion;

class RecepcionSeeder extends Seeder
{
    public function run()
    {
        // Crear 20 recepciones de ejemplo
        Recepcion::factory()->count(20)->create();
    }
}
