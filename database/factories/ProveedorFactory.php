<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Proveedor;

class ProveedorFactory extends Factory
{
    protected $model = Proveedor::class;

    public function definition(): array
    {
        $faker = $this->faker;

        // Función para generar datos de un proveedor
        $generarProveedor = function ($nombreEmpresa, $nitBase) use ($faker) {
            return [
                'prov_name'    => $nombreEmpresa,
                'prov_descrip' => $faker->text(100),
                'prov_nit'     => $nitBase . $faker->numerify('###'),
                'prov_name_c'  => $faker->name(),
                'prov_phone'   => $faker->phoneNumber(),
                'prov_adress'  => $faker->address(),
                'prov_city'    => $faker->city(),
            ];
        };

        // Lista de proveedores predefinidos con datos base
        $proveedores = [
            $generarProveedor('Tech Solutions S.A.S', '900123'),
            $generarProveedor('Insumos Contables Ltda.', '800456'),
            $generarProveedor('Recursos Humanos Plus', '901789'),
            $generarProveedor('Distribuidora Compras Global', '802147'),
            $generarProveedor('Calidad Total S.A.', '901258'),
            $generarProveedor('HSEQ Seguros & Servicios', '803369'),
            $generarProveedor('Comercializadora ABC', '901478'),
            $generarProveedor('Operaciones Industriales SAS', '804589'),
            $generarProveedor('Financiera Proyectos', '905698'),
            $generarProveedor('Mantenimiento Express', '806741'),
        ];

        // Escoger un proveedor al azar de la lista
        $proveedor = $faker->randomElement($proveedores);

        return [
            'prov_name'    => $proveedor['prov_name'],
            'prov_descrip' => $proveedor['prov_descrip'],
            'prov_nit'     => $proveedor['prov_nit'],
            'prov_name_c'  => $proveedor['prov_name_c'],
            'prov_phone'   => $proveedor['prov_phone'],
            'prov_adress'  => $proveedor['prov_adress'],
            'prov_city'    => $proveedor['prov_city'],
        ];
    }
}
