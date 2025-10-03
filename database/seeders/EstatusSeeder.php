<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\estatus;
use Carbon\Carbon;

class EstatusSeeder extends Seeder
{
    public function run()
    {
        $estatus = [
                ['status_name' => 'Requisición creada'],
                ['status_name' => 'Revisado por compras'],
                ['status_name' => 'Aprobado Gerencia'],
                ['status_name' => 'Aprobado Financiera'],
                ['status_name' => 'Orden de compra generada'],
                ['status_name' => 'Cancelada'],
                ['status_name' => 'Recibido en bodega'],
                ['status_name' => 'Recibido por coordinador'],
                ['status_name' => 'Rechazado financiera'],
                ['status_name' => 'Completado'],
                ['status_name' => 'Ajustes requeridos'],
                ['status_name' => 'Entregado parcial'],
                ['status_name' => 'Rechazado gerencia'],
        ];

        foreach ($estatus as $estado) {
            Estatus::create($estado);
        }

        $this->command->info('¡Estatus creados exitosamente!');
    }
}