<?php

namespace Database\Seeders;

use App\Models\Operaciones;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OperacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $operaciones = [
            'Tecnoogía',
            'Huawei',
            'KW',
            'Macmillan',
            'Mary Kay',
            'Sony',
            'Mattel',
            'Naos',
            'Cuarto Frios',
            'Oriflame',
            'Ortopédicos futuro',
            'Transporte',
        ];

        foreach ($operaciones as $operacion) {
            Operaciones::create(['op_name' => $operacion]);
        }

        $this->command->info('¡Operaciones se han creadas exitosamente!');
    }
}
