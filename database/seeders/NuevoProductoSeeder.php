<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\nuevo_producto;

class NuevoProductoSeeder extends Seeder
{
    public function run()
    {
        $productos = [
            [
                'name_user' => 'Juan Pérez',
                'email_user' => 'juan@example.com',
                'nombre' => 'Laptop',
                'descripcion' => 'Laptop de última generación con 16GB RAM'
            ],
            [
                'name_user' => 'Ana Gómez',
                'email_user' => 'ana@example.com',
                'nombre' => 'Teclado Mecánico',
                'descripcion' => 'Teclado mecánico RGB switches azules'
            ],
            [
                'name_user' => 'Carlos López',
                'email_user' => 'carlos@example.com',
                'nombre' => 'Monitor 24"',
                'descripcion' => 'Monitor Full HD 144Hz'
            ],
            [
                'name_user' => 'María Rodríguez',
                'email_user' => 'maria@example.com',
                'nombre' => 'Mouse Inalámbrico',
                'descripcion' => 'Mouse ergonómico 1600DPI'
            ],
            [
                'name_user' => 'Pedro Martínez',
                'email_user' => 'pedro@example.com',
                'nombre' => 'Impresora Multifuncional',
                'descripcion' => 'Impresora láser a color'
            ],
            [
                'name_user' => 'Laura Torres',
                'email_user' => 'laura@example.com',
                'nombre' => 'Disco Duro SSD',
                'descripcion' => 'SSD 1TB NVMe'
            ],
            [
                'name_user' => 'Jorge Ramírez',
                'email_user' => 'jorge@example.com',
                'nombre' => 'Router WiFi',
                'descripcion' => 'Router dual band AC1200'
            ],
            [
                'name_user' => 'Sofía Herrera',
                'email_user' => 'sofia@example.com',
                'nombre' => 'Webcam HD',
                'descripcion' => 'Cámara web 1080p con micrófono'
            ],
        ];

        foreach ($productos as $producto) {
            Nuevo_Producto::create($producto);
        }

        $this->command->info('¡8 nuevos productos creados exitosamente!');
    }
}
