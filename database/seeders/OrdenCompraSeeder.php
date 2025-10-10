<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrdenCompra;
use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use App\Models\Estatus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrdenCompraSeeder extends Seeder
{
    public function run()
    {
        // Crear una OC por cada requisición cuyo estatus activo sea 'Contacto con proveedor' o posterior
        $etapasConOC = [
            'Contacto con proveedor',
            'Entrega aproximada',
            'Recibido en bodega',
            'Recogido por coordinador',
            'Completado',
        ];

        $estatusMap = Estatus::whereIn('status_name', $etapasConOC)->get()->keyBy('status_name');

        $reqIds = Estatus_Requisicion::query()
            ->where('estatus', 1)
            ->whereIn('estatus_id', $estatusMap->pluck('id')->values())
            ->pluck('requisicion_id')
            ->unique();

        $requisiciones = Requisicion::whereIn('id', $reqIds)->get();

        foreach ($requisiciones as $req) {
            if (OrdenCompra::where('requisicion_id', $req->id)->exists()) {
                continue;
            }

            $date = ($req->created_at ?? now())->copy()->addDays(rand(5, 20));

            // Usar DB::table para evitar problemas de fillable y asegurar que se guarde requisicion_id
            DB::table('orden_compras')->insert([
                'requisicion_id' => $req->id,
                'oc_user'        => $req->name_user ?? 'Seeder',
                'observaciones'  => 'OC generada por seeder',
                'date_oc'        => $date->toDateString(),
                'methods_oc'     => 'Transferencia',
                'plazo_oc'       => rand(1, 2) === 1 ? 'Contado' : '30 días',
                'order_oc'       => 'OC-' . strtoupper(Str::random(6)),
                'validation_hash'=> null,
                'created_at'     => $date,
                'updated_at'     => $date,
            ]);
        }

        $this->command->info('Órdenes de compra generadas para requisiciones en etapa de OC (con requisicion_id asegurado).');
    }
}