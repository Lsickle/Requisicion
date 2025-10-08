<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Estatus_RequisicionSeeder extends Seeder
{
    public function run()
    {
        // Catálogo canónico (IDs fijos 1..13)
        $catalog = [
            1  => 'Requisición creada',
            2  => 'Revisado por compras',
            3  => 'Aprobado Gerencia',
            4  => 'Aprobado Financiera',
            5  => 'Orden de compra generada',
            6  => 'Cancelada',
            7  => 'Recibido en bodega',
            8  => 'Recibido por coordinador',
            9  => 'Rechazado financiera',
            10 => 'Completado',
            11 => 'Ajustes requeridos',
            12 => 'Entregado parcial',
            13 => 'Rechazado gerencia',
        ];

        // Asegurar que la tabla estatus tenga exactamente esos registros con esos IDs/nombres
        foreach ($catalog as $id => $name) {
            $exists = DB::table('estatus')->where('id', $id)->exists();
            if ($exists) {
                DB::table('estatus')->where('id', $id)->update([
                    'status_name' => $name,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('estatus')->insert([
                    'id' => $id,
                    'status_name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Mapa nombre=>id para uso rápido
        $nameToId = array_flip($catalog);

        $requisiciones = DB::table('requisicion')->get();

        foreach ($requisiciones as $requisicion) {
            // Desactivar estatus activos previos
            DB::table('estatus_requisicion')
                ->where('requisicion_id', $requisicion->id)
                ->update(['estatus' => 0]);

            $secuencia = $this->getEstadoSecuencia($requisicion);
            $ultimoEstadoId = null;

            foreach ($secuencia as $nombreEstado => $fecha) {
                $estadoId = $nameToId[$nombreEstado] ?? null;
                if (!$estadoId) continue;

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

        $this->command->info('Historial de estatus generado con IDs canónicos 1..13.');
    }

    protected function getEstadoSecuencia($requisicion)
    {
        // Base date
        $base = $requisicion->created_at ?? null;
        $fecha = ($base instanceof \Carbon\Carbon)
            ? $base->copy()
            : Carbon::parse($base ?? now())->copy();

        // Helper para sumar días aleatorios y devolver la fecha actualizada
        $cursor = $fecha->copy();
        $seq = [];
        $add = function(string $nombre, int $minDays, int $maxDays) use (&$cursor, &$seq) {
            $cursor = $cursor->copy()->addDays(rand($minDays, $maxDays));
            $seq[$nombre] = $cursor->copy();
        };

        // Estados canónicos en orden base
        $baseChain = [
            'Requisición creada',
            'Revisado por compras',
            'Aprobado Gerencia',
            'Aprobado Financiera',
            'Orden de compra generada',
        ];

        // Escenarios finales ponderados (suman aprox. 100)
        $scenarios = [
            'creada'         => 6,
            'revisado'       => 10,
            'aprob_ger'      => 10,
            'aprob_fin'      => 12,
            'oc'             => 15,
            'ent_parcial'    => 10,
            'rec_bod'        => 10,
            'rec_coord'      => 10,
            'completado'     => 7,
            'cancelada'      => 5,
            'ajustes'        => 3,
            'rech_ger'       => 1,
            'rech_fin'       => 1,
        ];

        $pickScenario = function(array $weights) {
            $sum = array_sum($weights);
            $r = rand(1, max(1, $sum));
            $acc = 0;
            foreach ($weights as $k => $w) {
                $acc += $w;
                if ($r <= $acc) return $k;
            }
            return array_key_first($weights);
        };

        $scenario = $pickScenario($scenarios);

        // Siempre iniciar con "Requisición creada"
        $seq['Requisición creada'] = $cursor->copy();

        // Construir la ruta hasta el escenario final
        switch ($scenario) {
            case 'creada':
                // solo creada
                break;
            case 'revisado':
                $add('Revisado por compras', 1, 2);
                break;
            case 'aprob_ger':
                $add('Revisado por compras', 1, 2);
                $add('Aprobado Gerencia', 1, 3);
                break;
            case 'aprob_fin':
                $add('Revisado por compras', 1, 2);
                $add('Aprobado Gerencia', 1, 3);
                $add('Aprobado Financiera', 2, 4);
                break;
            case 'oc':
                $add('Revisado por compras', 1, 2);
                $add('Aprobado Gerencia', 1, 3);
                $add('Aprobado Financiera', 2, 4);
                $add('Orden de compra generada', 1, 3);
                break;
            case 'ent_parcial':
                $add('Revisado por compras', 1, 2);
                $add('Aprobado Gerencia', 1, 3);
                $add('Aprobado Financiera', 2, 4);
                $add('Orden de compra generada', 1, 3);
                $add('Entregado parcial', 1, 3);
                break;
            case 'rec_bod':
                $add('Revisado por compras', 1, 2);
                $add('Aprobado Gerencia', 1, 3);
                $add('Aprobado Financiera', 2, 4);
                $add('Orden de compra generada', 1, 3);
                // opcionalmente pasó por entregado parcial
                if (rand(1, 100) <= 40) { $add('Entregado parcial', 1, 2); }
                $add('Recibido en bodega', 2, 4);
                break;
            case 'rec_coord':
                $add('Revisado por compras', 1, 2);
                $add('Aprobado Gerencia', 1, 3);
                $add('Aprobado Financiera', 2, 4);
                $add('Orden de compra generada', 1, 3);
                if (rand(1, 100) <= 40) { $add('Entregado parcial', 1, 2); }
                $add('Recibido en bodega', 2, 4);
                $add('Recibido por coordinador', 1, 3);
                break;
            case 'completado':
                $add('Revisado por compras', 1, 2);
                $add('Aprobado Gerencia', 1, 3);
                $add('Aprobado Financiera', 2, 4);
                $add('Orden de compra generada', 1, 3);
                if (rand(1, 100) <= 40) { $add('Entregado parcial', 1, 2); }
                $add('Recibido en bodega', 2, 4);
                $add('Recibido por coordinador', 1, 3);
                $add('Completado', 1, 3);
                break;
            case 'cancelada':
                // punto de cancelación aleatorio después de creada
                $path = ['Revisado por compras', 'Aprobado Gerencia', 'Aprobado Financiera', 'Orden de compra generada'];
                $steps = rand(0, count($path));
                for ($i = 0; $i < $steps; $i++) {
                    $name = $path[$i];
                    $add($name, 1, 3);
                }
                $add('Cancelada', 1, 2);
                break;
            case 'ajustes':
                $add('Revisado por compras', 1, 2);
                $add('Ajustes requeridos', 1, 2);
                break;
            case 'rech_ger':
                $add('Revisado por compras', 1, 2);
                $add('Rechazado gerencia', 1, 2);
                break;
            case 'rech_fin':
                $add('Revisado por compras', 1, 2);
                $add('Aprobado Gerencia', 1, 3);
                $add('Rechazado financiera', 1, 2);
                break;
            default:
                // Fallback mínimo
                $add('Revisado por compras', 1, 2);
                break;
        }

        return $seq;
    }
}