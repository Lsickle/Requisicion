<?php

namespace Database\Seeders;

use App\Models\Estatus_Requisicion;
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
        NuevoProductoSeeder::class, 
        RequisicionSeeder::class,
        OrdenCompraSeeder::class,
        Estatus_RequisicionSeeder::class,
        CentroProductoSeeder::class,
        CentroOrdenCompraSeeder::class,
        OperacionSeeder::class,
    ]);
}
}