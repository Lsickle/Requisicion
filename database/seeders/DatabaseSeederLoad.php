<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
{
    $this->call([
        // Base
        CentroSeeder::class,
        ClienteSeeder::class,
        EstatusSeeder::class,
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

        // Llamadas a seeders existentes
        ProveedoresSeeder::class,
        ProductosSeeder::class,
        ProductoxProveedorSeeder::class,
    ]);
}
}