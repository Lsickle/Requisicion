<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Requisicion;
use App\Models\Estatus;
use App\Models\Estatus_Requisicion;
use Illuminate\Support\Carbon;

class Estatus_RequisicionSeeder extends Seeder
{
    public function run()
    {
        // Asegurar estatus base
        $base = [
            'Iniciada', 'Revision', 'Aprobación Gerencia', 'Aprobación Financiera',
            'Contacto con proveedor', 'Entrega aproximada', 'Recibido en bodega',
            'Recogido por coordinador', 'Completado', 'Cancelado'
        ];
        foreach ($base as $name) {
            Estatus::firstOrCreate(['status_name' => $name]);
        }

        $requisiciones = Requisicion::all();
        $estatus = Estatus::all()->keyBy('status_name');

        foreach ($requisiciones as $requisicion) {
            $secuencia = $this->getEstadoSecuencia($requisicion);
            $ultimoEstado = null;

            foreach ($secuencia as $nombreEstado => $fecha) {
                if (!isset($estatus[$nombreEstado])) continue;

                Estatus_Requisicion::create([
                    'estatus_id'     => $estatus[$nombreEstado]->id,
                    'requisicion_id' => $requisicion->id,
                    'estatus'        => 0,
                    'date_update'    => $fecha,
                    'comentario'     => '',
                    'entrega_id'     => null,
                    'created_at'     => $fecha,
                    'updated_at'     => $fecha,
                ]);

                $ultimoEstado = $estatus[$nombreEstado]->id;
            }

            if ($ultimoEstado) {
                Estatus_Requisicion::where('requisicion_id', $requisicion->id)
                    ->where('estatus_id', $ultimoEstado)
                    ->update(['estatus' => 1]);
            }
        }

        $this->command->info('¡Todas las requisiciones tienen estatus en secuencia y uno activo!');
    }

    protected function getEstadoSecuencia($requisicion)
    {
        $fecha = ($requisicion->created_at ?? now())->copy();
        $seq = [];

        // Pipeline completo
        $seq['Iniciada'] = $fecha->copy();
        $seq['Revision'] = $seq['Iniciada']->copy()->addDays(rand(1, 2));
        $seq['Aprobación Gerencia'] = $seq['Revision']->copy()->addDays(rand(1, 3));
        $seq['Aprobación Financiera'] = $seq['Aprobación Gerencia']->copy()->addDays(rand(2, 4));
        $seq['Contacto con proveedor'] = $seq['Aprobación Financiera']->copy()->addDays(rand(1, 3));
        $seq['Entrega aproximada'] = $seq['Contacto con proveedor']->copy()->addDays(rand(3, 5));
        $seq['Recibido en bodega'] = $seq['Entrega aproximada']->copy()->addDays(rand(2, 4));
        $seq['Recogido por coordinador'] = $seq['Recibido en bodega']->copy()->addDays(rand(1, 3));
        $seq['Completado'] = $seq['Recogido por coordinador']->copy()->addDays(rand(1, 3));

        // 20%: Cancelado al final (sustituye Completado)
        if (rand(1, 100) <= 20) {
            $seq['Cancelado'] = $seq['Recogido por coordinador']->copy()->addDays(rand(1, 3));
            unset($seq['Completado']);
        }

        return $seq;
    }
}