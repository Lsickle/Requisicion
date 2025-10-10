<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Centro;
use App\Models\Requisicion;
use Illuminate\Support\Facades\DB;

class CentroProductoSeeder extends Seeder
{
    public function run()
    {
        $centros = Centro::all();
        if ($centros->isEmpty()) {
            throw new \RuntimeException('No hay centros para distribuir productos. Ejecute primero el seeder de centros.');
        }

        $requisiciones = Requisicion::all();

        foreach ($requisiciones as $requisicion) {
            $fechaBase = $requisicion->created_at ?? now();

            // Productos de la requisición
            $items = DB::table('producto_requisicion')
                ->where('id_requisicion', $requisicion->id)
                ->get();

            foreach ($items as $item) {
                $total = (int) $item->pr_amount;
                if ($total <= 0) continue;

                // Elegir 1–3 centros, sin exceder el total ni la cantidad de centros disponibles
                $centrosCount = min(max(1, rand(1, 3)), $centros->count(), $total);
                $seleccion = $centros->random($centrosCount)->values();

                // Particionar el total en enteros que sumen exactamente $total
                $partes = [];
                $restante = $total;
                for ($i = 0; $i < $centrosCount; $i++) {
                    if ($i === $centrosCount - 1) {
                        $partes[$i] = $restante;
                    } else {
                        $minRestante = ($centrosCount - $i - 1);
                        $max = max(1, $restante - $minRestante);
                        $partes[$i] = rand(1, $max);
                        $restante -= $partes[$i];
                    }
                }

                // Insertar distribución por centro
                for ($i = 0; $i < $centrosCount; $i++) {
                    DB::table('centro_producto')->insert([
                        'requisicion_id' => $requisicion->id,
                        'producto_id'    => $item->id_producto,
                        'centro_id'      => $seleccion[$i]->id,
                        'amount'         => $partes[$i],
                        'created_at'     => $fechaBase,
                        'updated_at'     => $fechaBase,
                    ]);
                }
            }
        }

        $this->command->info('¡Distribución centro-producto creada para todas las requisiciones!');
    }
}