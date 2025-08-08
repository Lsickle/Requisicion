<?php

namespace Database\Seeders;

use App\Models\nuevo_producto;
use Illuminate\Database\Seeder;
use App\Models\NuevoProducto;

class NuevoProductoSeeder extends Seeder
{
    public function run()
    {
        $productos = [
            ['nombre' => 'Laptop', 'descripcion' => 'Laptop de última generación con 16GB RAM'],
            ['nombre' => 'Teclado Mecánico', 'descripcion' => 'Teclado mecánico RGB switches azules'],
            ['nombre' => 'Monitor 24"', 'descripcion' => 'Monitor Full HD 144Hz'],
            ['nombre' => 'Mouse Inalámbrico', 'descripcion' => 'Mouse ergonómico 1600DPI'],
            ['nombre' => 'Impresora Multifuncional', 'descripcion' => 'Impresora láser a color'],
            ['nombre' => 'Disco Duro SSD', 'descripcion' => 'SSD 1TB NVMe'],
            ['nombre' => 'Router WiFi', 'descripcion' => 'Router dual band AC1200'],
            ['nombre' => 'Webcam HD', 'descripcion' => 'Cámara web 1080p con micrófono'],
        ];

        foreach ($productos as $producto) {
            nuevo_producto::create($producto);
        }

        $this->command->info('¡8 nuevos productos creados exitosamente!');
    }
}