<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductoxProveedorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $productos = DB::table('productos')->pluck('id')->toArray();
            $proveedores = DB::table('proveedores')->pluck('id')->toArray();

            if (empty($productos) || empty($proveedores)) {
                Log::warning('ProductoxProveedorSeeder: no hay productos o proveedores para relacionar');
                return;
            }

            $now = now();
            $inserts = [];
            foreach ($productos as $p) {
                // asignar aleatoriamente entre 1 y 3 proveedores por producto
                shuffle($proveedores);
                $count = min(3, max(1, rand(1, count($proveedores))));
                $selected = array_slice($proveedores, 0, $count);
                foreach ($selected as $prov) {
                    $inserts[] = [
                        'producto_id' => $p,
                        'proveedor_id' => $prov,
                        'price_produc' => round(rand(1000, 100000) / 100, 2),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (!empty($inserts)) {
                DB::table('productoxproveedor')->insert($inserts);
            }
        } catch (\Throwable $e) {
            Log::error('ProductoxProveedorSeeder error: ' . $e->getMessage());
        }
    }
}
