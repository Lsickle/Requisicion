<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Centro;

class CentroSeeder extends Seeder
{
    public function run()
    {
        $centros = [
            'MARY KAY', 'COLTABACO', 'ADMINISTRACION', 'CEDI FRIO', 'ORIFLAME',
            'INVENTARIOS', 'HUAWEI', 'MULTICLIENTE 1E', 'OVERHEAD', 'MATTEL',
            'NAOS', 'ORTOPEDICOS', 'COMERCIAL', 'MULTICLIENTE 12G', 'MANTENIMIENTO',
            'SONY', 'TRANSPORTES', 'SEGURIDAD', 'MAC MILLAN', 'TECNOLOGIA',
            'INNOVACION Y DESARROLLO', 'MULTICLIENTE', 'KW COLOMBIA', 'LAFAZENDA',
            'MC MILLAN', 'HSEQ', 'TODOS COMEMOS', 'KIKES', 'MEJORAMIENTO CONTINUO',
            'CALIDAD', 'FRIO', 'ORIFALME', 'COMPRAS', 'ORTOPEDICOS FUTURO',
            'AGROFRUT', 'TALENTO HUMANO', 'SULFOQUIMICA', 'OVERHED'
        ];

        foreach ($centros as $centro) {
            Centro::create(['name_centro' => $centro]);
        }

        $this->command->info('¡Centros de costo creados exitosamente!');
    }
}