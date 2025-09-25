<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstatusOrdenCompraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'Orden de compra creada',
            'Recibido',
            'Orden de compra terminada'
        ];

        foreach ($names as $name) {
            if (!DB::table('estatus_orden_compra')->where('status_name', $name)->exists()) {
                DB::table('estatus_orden_compra')->insert([
                    'status_name' => $name,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
