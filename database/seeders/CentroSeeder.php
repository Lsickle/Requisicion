<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\centro;

class CentroSeeder extends Seeder
{
    public function run()
    {
        $centros = [
            'Mary kay', 'Coltabaco', 'Administración', 'Cedi Frio',
            'Oriflame', 'Inventarios', 'Huawei', 'Overhead', 'Mattel',
            'Naos', 'Ortopedicos', 'Comercial', 'Mantenimiento', 'Sony',
            'Transportes', 'Seguridad', 'Mac Millan', 'Tecnologia', 'Kw Colombia',
            'Lafazenda', 'HSEQ', 'Todos comemos', 'Kikes', 'Mejoramiento continuo',
            'Calidad', 'Compras', 'Ortopedicos Futuro', 'Agrofruit',
            'Talento humano', 'Calypso', 'Ibazan', 'Financiero', 
            'Gerencia General', 'Corporativo', 'Proyectos', 'Operaciones',
        ];

        foreach ($centros as $centro) {
            Centro::create(['name_centro' => $centro]);
        }

        $this->command->info('¡Centros de costo creados exitosamente!');
    }
}
