<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class OrdenCompraEstatusSeeder extends Seeder
{
    public function run(): void
    {
        // Si no hay órdenes, crearlas primero
        if (OrdenCompra::count() === 0) {
            $this->call(OrdenCompraSeeder::class);
        }

        $estatus = DB::table('estatus_orden_compra')->get()->keyBy('status_name');
        if ($estatus->isEmpty()) {
            $this->call(EstatusOrdenCompraSeeder::class);
            $estatus = DB::table('estatus_orden_compra')->get()->keyBy('status_name');
        }

        $ordenes = OrdenCompra::all();
        foreach ($ordenes as $oc) {
            if (DB::table('orden_compra_estatus')->where('orden_compra_id', $oc->id)->exists()) {
                continue;
            }

            $created = $oc->date_oc ? Carbon::parse($oc->date_oc) : ($oc->created_at ?? now());

            $sec = [
                ['name' => 'Orden de compra creada', 'date' => $created],
            ];

            // 60% pasa a Recibido
            if (rand(1,100) <= 60) {
                $sec[] = ['name' => 'Recibido', 'date' => end($sec)['date']->copy()->addDays(rand(2, 10))];
            }

            // 40% termina
            if (rand(1,100) <= 40) {
                $last = end($sec)['date'] ?? $created;
                $sec[] = ['name' => 'Orden de compra terminada', 'date' => $last->copy()->addDays(rand(2, 10))];
            }

            $lastId = null;
            foreach ($sec as $step) {
                $st = $estatus[$step['name']] ?? null;
                if (!$st) continue;

                DB::table('orden_compra_estatus')->insert([
                    'estatus_id'     => $st->id,
                    'orden_compra_id'=> $oc->id,
                    'recepcion_id'   => null,
                    'activo'         => 0,
                    'date_update'    => $step['date'],
                    'user_id'        => null,
                    'user_name'      => $oc->oc_user,
                    'created_at'     => $step['date'],
                    'updated_at'     => $step['date'],
                ]);

                $lastId = $st->id;
            }

            if ($lastId) {
                DB::table('orden_compra_estatus')
                    ->where('orden_compra_id', $oc->id)
                    ->where('estatus_id', $lastId)
                    ->update(['activo' => 1]);
            }
        }

        $this->command->info('Estatus de órdenes de compra asignados.');
    }
}
