<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Centro;
use Illuminate\Support\Facades\DB;

class CentroRequisicionSeeder extends Seeder
{
    public function run()
    {
        // Obtener todos los centros y relaciones producto_requisicion
        $centros = Centro::all();
        $productoRequisiciones = DB::table('producto_requisicion')->get();

        // Para cada relación producto_requisicion, asignar a 1-2 centros
        foreach ($productoRequisiciones as $pr) {
            $centrosAleatorios = $centros->random(rand(1, 2));
            foreach ($centrosAleatorios as $centro) {
                DB::table('centro_requisicion')->insert([
                    'producto_requisicion_id' => $pr->id,
                    'centro_id' => $centro->id,
                    'rc_amount' => rand(1, $pr->pr_amount),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $this->command->info('¡Relaciones centro-requisición creadas exitosamente!');
    }
}