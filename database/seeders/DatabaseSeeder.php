<?php

namespace Database\Seeders;

use App\Models\Estatus_Requisicion;
use App\Models\OrdenCompra;
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
        OrdenCompraProductoSeeder::class,
        NuevoProductoSeeder::class, 
        RequisicionSeeder::class,
        Estatus_RequisicionSeeder::class,
        CentroProductoSeeder::class,
        CentroOrdenCompraSeeder::class,
    ]);
}
}