<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\proveedor;

class ProveedorSeeder extends Seeder
{
    public function run()
    {
        Proveedor::factory()->count(15)->create();
        $this->command->info('¡15 proveedores de prueba creados exitosamente!');
    }
}