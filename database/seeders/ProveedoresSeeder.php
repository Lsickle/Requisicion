<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProveedoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $now = now();
        $data = [];

        for ($i = 0; $i < 6; $i++) {
            $data[] = [
                'prov_name' => $faker->company,
                'prov_descrip' => $faker->catchPhrase,
                'prov_nit' => $faker->numerify('#########-#'),
                'prov_name_c' => $faker->name,
                'prov_phone' => $faker->e164PhoneNumber,
                'prov_adress' => $faker->address,
                'prov_city' => $faker->city,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($data)) {
            DB::table('proveedores')->insert($data);
        }
    }
}
