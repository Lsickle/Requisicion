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

            // Primero creamos todos los estados con estatus = 0
            foreach ($estadosSecuencia as $nombreEstado => $fecha) {
                if (isset($estatus[$nombreEstado])) {
                    Estatus_Requisicion::create([
                        'estatus_id' => $estatus[$nombreEstado]->id,
                        'requisicion_id' => $requisicion->id,
                        'estatus' => 0, // Todos se crean inicialmente con 0
                        'date_update' => $fecha,
                        'created_at' => $fecha,
                        'updated_at' => $fecha,
                    ]);
                    $ultimoEstado = $estatus[$nombreEstado]->id;
                }
            }

            // Si hay al menos un estado, actualizamos el último a estatus = 1
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

        $secuencia['Iniciada'] = $fechaBase;

        if (rand(1, 100) <= 80) {
            $secuencia['Aprobación Gerencia'] = $fechaBase->copy()->addDays(rand(1, 3));

            if (rand(1, 100) <= 70) {
                $secuencia['Aprobación Financiera'] = $fechaBase->copy()->addDays(rand(3, 5));

                if (rand(1, 100) <= 60) {
                    $secuencia['Contacto con proveedor'] = $fechaBase->copy()->addDays(rand(5, 7));

                    if (rand(1, 100) <= 50) {
                        $secuencia['Entrega aprox 3 días habiles'] = $fechaBase->copy()->addDays(rand(7, 10));
                        $secuencia['Recibido en bodega'] = $fechaBase->copy()->addDays(rand(10, 14));
                        $secuencia['Recogido por coordinador'] = $fechaBase->copy()->addDays(rand(14, 16));
                        $secuencia['Completado'] = $fechaBase->copy()->addDays(rand(16, 20));
                    }
                }
            }
        }

        if (rand(1, 100) <= 10) {
            $ultimoEstado = array_key_last($secuencia);
            $secuencia['Cancelado'] = Carbon::parse($secuencia[$ultimoEstado])->addDays(rand(1, 3));
            $posicionCancelado = array_search('Cancelado', array_keys($secuencia));
            $secuencia = array_slice($secuencia, 0, $posicionCancelado + 1, true);
        }

        return $secuencia;
    }
}