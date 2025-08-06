<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;

class AreaSeeder extends Seeder
{
    public function run()
    {
        $areas = [
            'Tecnología',
            'Contabilidad',
            'Talento Humano',
            'Compras',
            'Calidad',
            'HSEQ',
            'Comercial',
            'Operaciones',
            'Finanaciera',
            'Mantenimiento',
        ];

        foreach ($areas as $area) {
            Area::create(['area_name' => $area]);
        }

        $this->command->info('¡Áreas creadas exitosamente!');
    }
}