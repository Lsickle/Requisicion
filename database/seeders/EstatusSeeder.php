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
            ['status_name' => 'Iniciada', 'status_date' => Carbon::now(), 'status_curso' => true],
            ['status_name' => 'Aprobación Gerencia', 'status_date' => Carbon::now(), 'status_curso' => false],
            ['status_name' => 'Aprobación Financiera', 'status_date' => Carbon::now(), 'status_curso' => false],
            ['status_name' => 'Contacto con proveedor', 'status_date' => Carbon::now(), 'status_curso' => true],
            ['status_name' => 'Entrega aprox 3 días habiles', 'status_date' => Carbon::now(), 'status_curso' => false],
            ['status_name' => 'Recibido en bodega', 'status_date' => Carbon::now(), 'status_curso' => false],
            ['status_name' => 'Recogido por coordinador', 'status_date' => Carbon::now(), 'status_curso' => false],
            ['status_name' => 'Cancelado', 'status_date' => Carbon::now(), 'status_curso' => false],
            ['status_name' => 'Completado', 'status_date' => Carbon::now(), 'status_curso' => false],
        ];

        foreach ($estatus as $estado) {
            Estatus::create($estado);
        }

        $this->command->info('¡Estatus creados exitosamente!');
    }
}