<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstatusSeeder extends Seeder
{
    public function run()
    {
        $names = [
            'Requisición creada',
            'Revisado por compras',
            'Aprobado Gerencia',
            'Aprobado Financiera',
            'Orden de compra generada',
            'Cancelada',
            'Recibido en bodega',
            'Recibido por coordinador',
            'Rechazado financiera',
            'Completado',
            'Ajustes requeridos',
            'Entregado parcial',
            'Rechazado gerencia',
        ];

        // Mapeo de sinónimos/históricos a nombres canónicos
        $renameMap = [
            'Iniciada' => 'Requisición creada',
            'Revision' => 'Revisado por compras',
            'Aprobación Gerencia' => 'Aprobado Gerencia',
            'Aprobación Financiera' => 'Aprobado Financiera',
            'Recogido por coordinador' => 'Recibido por coordinador',
            'Cancelado' => 'Cancelada',
        ];

        // Estatus obsoletos que no pertenecen al set de 13
        $obsolete = [
            'Contacto con proveedor',
            'Entrega aproximada',
        ];

        DB::transaction(function () use ($names, $renameMap, $obsolete) {
            // Renombrar sinónimos a los nombres canónicos
            foreach ($renameMap as $from => $to) {
                DB::table('estatus')->where('status_name', $from)
                    ->update(['status_name' => $to, 'updated_at' => now()]);
            }

            // Eliminar estatus obsoletos
            if (!empty($obsolete)) {
                DB::table('estatus')->whereIn('status_name', $obsolete)->delete();
            }

            // Eliminar cualquier estatus que no esté en el set canónico
            DB::table('estatus')->whereNotIn('status_name', $names)->delete();

            // Upsert de los 13 canónicos
            foreach ($names as $name) {
                DB::table('estatus')->updateOrInsert(
                    ['status_name' => $name],
                    ['status_name' => $name, 'updated_at' => now(), 'created_at' => DB::raw('COALESCE(created_at, NOW())')]
                );
            }

            // Limpiar duplicados por nombre (mantener el de menor id)
            $dupes = DB::table('estatus')
                ->select('status_name', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as c'))
                ->groupBy('status_name')
                ->having('c', '>', 1)
                ->get();
            foreach ($dupes as $d) {
                DB::table('estatus')
                    ->where('status_name', $d->status_name)
                    ->where('id', '!=', $d->keep_id)
                    ->delete();
            }
        });

        $this->command->info('Estatus normalizados a 13 (renombrados, obsoletos eliminados y duplicados limpiados).');
    }
}