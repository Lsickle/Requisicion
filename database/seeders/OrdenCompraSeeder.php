<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrdenCompra;
use Carbon\Carbon;

class OrdenCompraSeeder extends Seeder
{
    public function run()
    {
        $metodosPago = ['Contado', 'Crédito 30 días', 'Crédito 60 días', 'Transferencia'];
        $plazos = ['Inmediato', '15 días', '30 días', '45 días', '60 días'];

        // Crear 15 órdenes de compra
        for ($i = 0; $i < 15; $i++) {
            OrdenCompra::create([
                'date_oc' => Carbon::now()->subDays(rand(1, 60)),
                'methods_oc' => $metodosPago[array_rand($metodosPago)],
                'plazo_oc' => $plazos[array_rand($plazos)],
                'order_oc' => 1 + $i, #secuencia de números que empieza desde 1
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->command->info('¡15 órdenes de compra creadas exitosamente!');
    }
}