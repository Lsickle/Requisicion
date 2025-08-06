<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
{
    $this->call([
        AreaSeeder::class,
        ProveedorSeeder::class,
        CentroSeeder::class,
        ClienteSeeder::class,
        EstatusSeeder::class,
        ProductoSeeder::class,
        RequisicionSeeder::class,
        OrdenCompraSeeder::class, // Añadir esta línea
    ]);
}
}