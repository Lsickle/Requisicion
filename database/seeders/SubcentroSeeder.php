<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subcentro;
use App\Models\Centro;

class SubcentroSeeder extends Seeder
{
    public function run()
    {
        $centros = Centro::all();

        if ($centros->isEmpty()) {
            $this->command->info('No hay centros para crear subcentros. Ejecuta primero CentroSeeder.');
            return;
        }

        // lista de ids para asignación aleatoria
        $centroIds = $centros->pluck('id')->toArray();

        // Para cada nombre de centro, crear entre 1 y 3 subcentros con ese nombre
        foreach ($centros as $centro) {
            $copies = random_int(1, 3);
            for ($i = 0; $i < $copies; $i++) {
                // asignar centro_id aleatorio (puede coincidir o no con el del nombre)
                $randomCentroId = $centroIds[array_rand($centroIds)];

                Subcentro::create([
                    'name_subcentro' => trim($centro->name_centro),
                    'centro_id' => $randomCentroId,
                ]);
            }
        }

        $this->command->info('¡Subcentros creados exitosamente con asignación aleatoria de centros!');
    }
}
