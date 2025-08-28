<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Estatus;
use Carbon\Carbon;

class EstatusSeeder extends Seeder
{
    public function run()
    {
        $estatus = [
                ['status_name' => 'Iniciada'],
                ['status_name' => 'Revision'],
                ['status_name' => 'Aprobación Gerencia'],
                ['status_name' => 'Aprobación Financiera'],
                ['status_name' => 'Contacto con proveedor'],
                ['status_name' => 'Entrega aproximada'],
                ['status_name' => 'Recibido en bodega'],
                ['status_name' => 'Recogido por coordinador'],
                ['status_name' => 'Rechazado'],
                ['status_name' => 'Completado'],
        ];

        foreach ($estatus as $estado) {
            Estatus::create($estado);
        }

        $this->command->info('¡Estatus creados exitosamente!');
    }
}