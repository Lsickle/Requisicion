<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
{
    $this->call([
        ProveedorSeeder::class,
        CentroSeeder::class,
        ClienteSeeder::class,
        EstatusSeeder::class,
        ProductoSeeder::class,
        NuevoProductoSeeder::class, 
        RequisicionSeeder::class,
        Estatus_RequisicionSeeder::class,
        CentroProductoSeeder::class,
        OrdenCompraSeeder::class,
        OrdenCompraProductoSeeder::class,
        EstatusTableSeeder::class,
        EstatusOrdenCompraSeeder::class,
        EstatusTableSeeder::class,
    ]);
}
}