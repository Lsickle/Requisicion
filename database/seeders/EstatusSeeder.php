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
                ['status_name' => 'Revisado por area de compras'],
                ['status_name' => 'Aprobado Gerencia'],
                ['status_name' => 'Aprobado Financiera'],
                ['status_name' => 'Orden de compra generada'],
                ['status_name' => 'Cancelada'],
                ['status_name' => 'Recibido en bodega'],
                ['status_name' => 'Recibido por coordinador'],
                ['status_name' => 'Rechazado'],
                ['status_name' => 'Completado'],
                ['status_name' => 'Corregir requisicion'],
                ['status_name' => 'Entrega parcial'],
        ];

        foreach ($estatus as $estado) {
            Estatus::create($estado);
        }

        $this->command->info('¡Estatus creados exitosamente!');
    }
}