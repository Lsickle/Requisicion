<?php

namespace Database\Factories;

use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    public function definition(): array
    {
        $faker = $this->faker;

        // Función para generar un producto con Faker
        $generarProducto = function($unidad) use ($faker) {
            return [
                'nombre' => ucfirst($faker->words(2, true)), // nombre de 2 palabras
                'unidad' => $unidad,
                'descripcion' => $faker->sentence(10), // descripción de 10 palabras
                'precio' => $faker->randomFloat(2, 20, 5000), // precio entre 20 y 5000
                'stock' => $faker->numberBetween(5, 200) // stock entre 5 y 200
            ];
        };

        $categorias = [
            'Tecnología' => array_map(fn() => $generarProducto('unidad'), range(1, 6)),
            'Contabilidad' => array_map(fn() => $generarProducto('unidad'), range(1, 4)),
            'Talento Humano' => array_map(fn() => $generarProducto('paquete'), range(1, 3)),
            'Compras' => array_map(fn() => $generarProducto('paquete'), range(1, 3)),
            'Calidad' => array_map(fn() => $generarProducto('paquete'), range(1, 3)),
            'HSEQ' => array_map(fn() => $generarProducto('unidad'), range(1, 3)),
            'Comercial' => array_map(fn() => $generarProducto('paquete'), range(1, 3)),
            'Operaciones' => array_map(fn() => $generarProducto('unidad'), range(1, 3)),
            'Financiera' => array_map(fn() => $generarProducto('unidad'), range(1, 3)),
            'Mantenimiento' => array_map(fn() => $generarProducto('unidad'), range(1, 3)),
            'Otros' => array_map(fn() => $generarProducto('unidad'), range(1, 3)),
        ];

        // Escoger una categoría y un producto aleatorio
        $categoria = $faker->randomElement(array_keys($categorias));
        $productoData = $faker->randomElement($categorias[$categoria]);

        return [
            'id_proveedores' => Proveedor::factory(),
            'categoria_produc' => $categoria,
            'name_produc' => $productoData['nombre'],
            'stock_produc' => $productoData['stock'],
            'description_produc' => $productoData['descripcion'],
            'price_produc' => $productoData['precio'],
            'unit_produc' => $productoData['unidad'],
        ];
    }
}
