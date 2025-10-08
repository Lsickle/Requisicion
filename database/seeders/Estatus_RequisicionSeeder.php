<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Estatus_RequisicionSeeder extends Seeder
{
    public function run()
    {
        // Usar exclusivamente los 13 estatus canónicos (no insertar nuevos aquí)
        $allowed = [
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

        $estatus = DB::table('estatus')
            ->select('id', 'status_name')
            ->whereIn('status_name', $allowed)
            ->get()
            ->keyBy('status_name');

        $requisiciones = DB::table('requisicion')->get();

        foreach ($requisiciones as $requisicion) {
            // Desactivar cualquier estatus activo previo
            DB::table('estatus_requisicion')
                ->where('requisicion_id', $requisicion->id)
                ->update(['estatus' => 0]);

            $secuencia = $this->getEstadoSecuencia($requisicion);
            $ultimoEstadoId = null;

            foreach ($secuencia as $nombreEstado => $fecha) {
                if (!isset($estatus[$nombreEstado])) continue; // saltar si no existe en catálogo
                $estadoId = $estatus[$nombreEstado]->id;

                DB::table('estatus_requisicion')->insert([
                    'estatus_id'     => $estadoId,
                    'requisicion_id' => $requisicion->id,
                    'estatus'        => 0,
                    'date_update'    => $fecha,
                    'comentario'     => '',
                    'entrega_id'     => null,
                    'created_at'     => $fecha,
                    'updated_at'     => $fecha,
                ]);

                $ultimoEstadoId = $estadoId;
            }

            if ($ultimoEstadoId) {
                DB::table('estatus_requisicion')
                    ->where('requisicion_id', $requisicion->id)
                    ->where('estatus_id', $ultimoEstadoId)
                    ->update(['estatus' => 1, 'updated_at' => now()]);
            }
        }

        $this->command->info('Historial de estatus generado usando solo los 13 estatus definidos.');
    }

    protected function getEstadoSecuencia($requisicion)
    {
        // Normalizar a Carbon la fecha base aunque venga como string (DB::table)
        $base = $requisicion->created_at ?? null;
        $fecha = ($base instanceof \Carbon\Carbon)
            ? $base->copy()
            : Carbon::parse($base ?? now())->copy();

        // Secuencia alineada con el catálogo de 13
        $seq = [];
        $seq['Requisición creada'] = $fecha->copy();
        $seq['Revisado por compras'] = $seq['Requisición creada']->copy()->addDays(rand(1, 2));
        $seq['Aprobado Gerencia'] = $seq['Revisado por compras']->copy()->addDays(rand(1, 3));
        $seq['Aprobado Financiera'] = $seq['Aprobado Gerencia']->copy()->addDays(rand(2, 4));
        $seq['Orden de compra generada'] = $seq['Aprobado Financiera']->copy()->addDays(rand(1, 3));
        if (rand(1, 100) <= 30) {
            $seq['Entregado parcial'] = $seq['Orden de compra generada']->copy()->addDays(rand(1, 3));
        }
        $seq['Recibido en bodega'] = ($seq['Entregado parcial'] ?? $seq['Orden de compra generada'])->copy()->addDays(rand(2, 4));
        $seq['Recibido por coordinador'] = $seq['Recibido en bodega']->copy()->addDays(rand(1, 3));
        $seq['Completado'] = $seq['Recibido por coordinador']->copy()->addDays(rand(1, 3));

        // 15%: Cancelada en lugar de Completado
        if (rand(1, 100) <= 15) {
            $seq['Cancelada'] = $seq['Recibido por coordinador']->copy()->addDays(rand(1, 3));
            unset($seq['Completado']);
        }

        return $seq;
    }
}