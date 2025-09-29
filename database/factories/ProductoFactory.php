<?php

namespace Database\Factories;

use App\Models\Proveedor;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        $unidades = ['unidades', 'paquetes', 'kg', 'litros', 'metros', 'cajas'];
        $categorias = [
            'Tecnología', 'Contabilidad', 'Talento Humano', 'Compras', 'Calidad', 'HSEQ',
            'Comercial', 'Operaciones', 'Financiera', 'Mantenimiento', 'Otros'
        ];

        return [
            'proveedor_id' => Proveedor::factory(),
            'categoria_produc' => $this->faker->randomElement($categorias),
            'name_produc' => $this->faker->words(2, true),
            'stock_produc' => $this->faker->boolean(30) ? 0 : $this->faker->numberBetween(5, 200),
            'description_produc' => $this->faker->sentence(10),
            'price_produc' => $this->faker->randomFloat(2, 20, 5000),
            'iva' => $this->faker->randomElement([0, 5, 12]),
            'unit_produc' => $this->faker->randomElement($unidades),
        ];
    }
}
