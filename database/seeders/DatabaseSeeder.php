<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
{
    $this->call([
        // Base
        ProveedorSeeder::class,
        CentroSeeder::class,
        ClienteSeeder::class,
        EstatusSeeder::class,
        ProductoSeeder::class,
        NuevoProductoSeeder::class,

        // Requisiciones + relaciones
        RequisicionSeeder::class,
        Estatus_RequisicionSeeder::class,
        CentroProductoSeeder::class,

        // Estatus de OC (catálogo)
        EstatusOrdenCompraSeeder::class,

        // Orden de compra + pivotes y estatus
        OrdenCompraSeeder::class,
        OrdenCompraProductoSeeder::class,
        OrdenCompraCentroProductoSeeder::class,
        OrdenCompraEstatusSeeder::class,
    ]);
}
}