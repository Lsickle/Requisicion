<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Requisicion;
use App\Models\Estatus;
use App\Models\Estatus_Requisicion;
use Carbon\Carbon;

class Estatus_RequisicionSeeder extends Seeder
{
    public function run()
    {
        $requisiciones = Requisicion::all();
        $estatus = Estatus::all()->keyBy('status_name');

        foreach ($requisiciones as $requisicion) {
            $estadosSecuencia = $this->getEstadoSecuencia($requisicion);
            $ultimoEstado = null;

            // Creamos todos los estados con estatus = 0
            foreach ($estadosSecuencia as $nombreEstado => $fecha) {
                if (isset($estatus[$nombreEstado])) {
                    Estatus_Requisicion::create([
                        'estatus_id' => $estatus[$nombreEstado]->id,
                        'requisicion_id' => $requisicion->id,
                        'estatus' => 0,
                        'date_update' => $fecha,
                        'comentario' => '', // Cambiado de null a cadena vacía
                        'entrega_id' => null,
                        'created_at' => $fecha,
                        'updated_at' => $fecha,
                    ]);
                    $ultimoEstado = $estatus[$nombreEstado]->id;
                }
            }

            // Actualizamos el último estado a estatus = 1
            if ($ultimoEstado) {
                Estatus_Requisicion::where('requisicion_id', $requisicion->id)
                    ->where('estatus_id', $ultimoEstado)
                    ->update(['estatus' => 1]);
            }
        }

        $this->command->info('¡Relaciones estatus-requisicion creadas exitosamente con un solo estatus activo!');
    }

    protected function getEstadoSecuencia($requisicion)
    {
        $fechaBase = Carbon::parse($requisicion->date_requisicion);
        $secuencia = [];

        // Secuencia básica
        $secuencia['Iniciada'] = $fechaBase;

        // Nuevo estatus agregado: Revision
        if (rand(1, 100) <= 90) { // 90% de probabilidad de pasar a Revision
            $secuencia['Revision'] = $fechaBase->copy()->addDays(rand(1, 2));
        }

        if (rand(1, 100) <= 80) {
            $fechaUltimo = end($secuencia);
            $secuencia['Aprobación Gerencia'] = $fechaUltimo->copy()->addDays(rand(1, 3));

            if (rand(1, 100) <= 70) {
                $fechaUltimo = end($secuencia);
                $secuencia['Aprobación Financiera'] = $fechaUltimo->copy()->addDays(rand(2, 4));

                if (rand(1, 100) <= 60) {
                    $fechaUltimo = end($secuencia);
                    $secuencia['Contacto con proveedor'] = $fechaUltimo->copy()->addDays(rand(2, 3));

                    if (rand(1, 100) <= 50) {
                        $fechaUltimo = end($secuencia);
                        $secuencia['Entrega aproximada'] = $fechaUltimo->copy()->addDays(rand(3, 5));
                        $fechaUltimo = end($secuencia);
                        $secuencia['Recibido en bodega'] = $fechaUltimo->copy()->addDays(rand(3, 4));
                        $fechaUltimo = end($secuencia);
                        $secuencia['Recogido por coordinador'] = $fechaUltimo->copy()->addDays(rand(2, 3));
                        $fechaUltimo = end($secuencia);
                        $secuencia['Completado'] = $fechaUltimo->copy()->addDays(rand(2, 4));
                    }
                }
            }
        }

        // Opción de cancelación
        if (rand(1, 100) <= 10) {
            $ultimoEstado = array_key_last($secuencia);
            $secuencia['Cancelado'] = Carbon::parse($secuencia[$ultimoEstado])->addDays(rand(1, 3));
            $posicionCancelado = array_search('Cancelado', array_keys($secuencia));
            $secuencia = array_slice($secuencia, 0, $posicionCancelado + 1, true);
        }

        return $secuencia;
    }
}