<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Proveedor;

class ProveedorFactory extends Factory
{
    protected $model = Proveedor::class;

    public function definition(): array
    {
        $proveedores = [
            [
                'prov_name' => 'TecnoSoluciones S.A.S.',
                'prov_descrip' => 'Proveedor de equipos tecnológicos y soluciones informáticas.',
                'prov_nit' => '9001234567',
                'prov_name_c' => 'Carlos Pérez',
                'prov_phone' => '3001234567',
                'prov_adress' => 'Calle 45 #12-34',
                'prov_city' => 'Bogotá'
            ],
            [
                'prov_name' => 'OfiExpress Ltda.',
                'prov_descrip' => 'Distribuidor de insumos de oficina y papelería.',
                'prov_nit' => '9007654321',
                'prov_name_c' => 'Ana Martínez',
                'prov_phone' => '3109876543',
                'prov_adress' => 'Carrera 15 #23-45',
                'prov_city' => 'Medellín'
            ],
            [
                'prov_name' => 'Seguridad Industrial S.A.',
                'prov_descrip' => 'Venta de equipos de protección personal y seguridad industrial.',
                'prov_nit' => '8004567890',
                'prov_name_c' => 'Jorge Ramírez',
                'prov_phone' => '3156789012',
                'prov_adress' => 'Av. 68 #70-55',
                'prov_city' => 'Cali'
            ],
            [
                'prov_name' => 'Papelería El Lápiz Feliz',
                'prov_descrip' => 'Papelería mayorista y minorista.',
                'prov_nit' => '8309876543',
                'prov_name_c' => 'María Torres',
                'prov_phone' => '3204567890',
                'prov_adress' => 'Calle 10 #5-67',
                'prov_city' => 'Barranquilla'
            ],
            [
                'prov_name' => 'Mecánica y Repuestos Ltda.',
                'prov_descrip' => 'Proveedor de repuestos y herramientas mecánicas.',
                'prov_nit' => '8901230987',
                'prov_name_c' => 'Luis Fernández',
                'prov_phone' => '3229876543',
                'prov_adress' => 'Cra 22 #33-44',
                'prov_city' => 'Bucaramanga'
            ]
        ];

        // Escoger un proveedor random de la lista
        $proveedor = $this->faker->randomElement($proveedores);

        return [
            'prov_name' => $proveedor['prov_name'],
            'prov_descrip' => $proveedor['prov_descrip'],
            'prov_nit' => $proveedor['prov_nit'],
            'prov_name_c' => $proveedor['prov_name_c'],
            'prov_phone' => $proveedor['prov_phone'],
            'prov_adress' => $proveedor['prov_adress'],
            'prov_city' => $proveedor['prov_city'],
        ];
    }
}
