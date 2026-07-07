<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subcentro;
use App\Models\Centro;

class SubcentroSeeder extends Seeder
{
    public function run()
    {
        $names = [
            'Cedi frio',
            'Cedi frio - Mantenimiento',
            'Mary Kay',
            'Oriflame',
            'Sony',
            'Macmillan',
            'Kw',
            'Transportes Vigia',
            'Cumbria',
            'Ortopedicos Futuro',
            'Naos',
            'Mattel',
            'Huawei',
            'Cedi Frio Agrofruit',
            'Cedi Frio Kikes',
            'Cedi Frio La Fazenda',
            'Cedi Frio Calypso',
            'Cedi Frio Ibazan',
            'Cedi Frio Todos Comemos',
            'Cedi Frio Food Box',
            'Seguridad',
            'Inventarios',
            'Transportes',
            'Mejoramiento Contínuo',
            'HSEQ',
            'Calidad',
            'Talento Humano',
            'Financiero',
            'Tecnología',
            'Compras'
        ];

        // Obtener centros existentes
        $centros = Centro::all();
        if ($centros->isEmpty()) {
            // crear un centro por defecto para mantener integridad referencial
            $default = Centro::create(['name_centro' => 'General']);
            $centros = Centro::all();
            $this->command->info("No se encontraron centros: creado centro por defecto 'General'.");
        }

        // construir mapa normalizado de centros
        $map = [];
        foreach ($centros as $c) {
            $map[$this->normalizeKey($c->name_centro)] = $c;
        }

        $firstCentro = $centros->first();

        foreach ($names as $name) {
            // evitar duplicados exactos por name_subcentro + centro_id
            $existsAny = Subcentro::where('name_subcentro', $name)->exists();
            if ($existsAny) continue;

            $norm = $this->normalizeKey($name);
            $centro = null;

            // coincidencia exacta en mapa
            if (isset($map[$norm])) {
                $centro = $map[$norm];
            } else {
                // buscar coincidencia por substring en nombres de centros
                foreach ($map as $key => $c) {
                    if (strpos($norm, $key) !== false || strpos($key, $norm) !== false) { $centro = $c; break; }
                }
            }

            if (!$centro) {
                // asignar primer centro como fallback
                $centro = $firstCentro;
            }

            Subcentro::create([
                'name_subcentro' => $name,
                'centro_id' => $centro->id,
            ]);
        }

        $this->command->info('Subcentros creados/actualizados (lista fija).');
    }

    private function normalizeKey($txt)
    {
        $t = mb_strtolower(trim((string)$txt), 'UTF-8');
        $t = strtr($t, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','ñ'=>'n','Ñ'=>'n','ü'=>'u','Ü'=>'u']);
        $t = preg_replace('/[^a-z0-9\s\-]/u', '', $t);
        $t = preg_replace('/\s+/', ' ', $t);
        return $t;
    }
}
