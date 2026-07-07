<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Centro;

class CentroSeeder extends Seeder
{
    public function run()
    {
        $centros = [
            'Calidad', 'Operaciones', 'HSEQ', 'Financiero', 'Seguridad',
        ];

        foreach ($centros as $centro) {
            Centro::create(['name_centro' => $centro]);
        }

        $this->command->info('¡Centros de costo creados exitosamente!');
    }
}
