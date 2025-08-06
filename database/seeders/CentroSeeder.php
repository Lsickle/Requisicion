<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Centro;

class CentroSeeder extends Seeder
{
    public function run()
    {
        $centros = [
            'Gestión Tecnologíca',
            'Gestión Contable',
            'Gestión de Talento Humano',
            'Gestión Compras',
            'Gestión de Calidad',
            'Gestión HSEQ',
            'Gestión Comercial',
            'Gestión Operaciones',
            'Gestión Finanaciera',
            'Gestión Mantenimiento',
        ];

        foreach ($centros as $centro) {
            Centro::create(['name_centro' => $centro]);
        }

        $this->command->info('¡Centros de costo creados exitosamente!');
    }
}