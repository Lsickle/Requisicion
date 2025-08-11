<?php

namespace Database\Factories;

use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    public function definition(): array
    {
        $categorias = [
            'Tecnología' => [
                ['nombre' => 'Computador', 'unidad' => 'unidad', 'descripcion' => 'Computador de escritorio con procesador de última generación.', 'precio' => 2500, 'stock' => 50],
                ['nombre' => 'Laptop', 'unidad' => 'unidad', 'descripcion' => 'Portátil ligero con gran autonomía y alto rendimiento.', 'precio' => 3200, 'stock' => 40],
                ['nombre' => 'Impresora', 'unidad' => 'unidad', 'descripcion' => 'Impresora multifuncional con conexión Wi-Fi.', 'precio' => 800, 'stock' => 30],
                ['nombre' => 'Teclado', 'unidad' => 'unidad', 'descripcion' => 'Teclado mecánico ergonómico.', 'precio' => 120, 'stock' => 100],
                ['nombre' => 'Mouse', 'unidad' => 'unidad', 'descripcion' => 'Mouse óptico inalámbrico.', 'precio' => 60, 'stock' => 150],
                ['nombre' => 'Monitor', 'unidad' => 'unidad', 'descripcion' => 'Monitor LED de alta resolución.', 'precio' => 950.00, 'stock' => 25]
            ],
            'Contabilidad' => [
                ['nombre' => 'Software Contable', 'unidad' => 'licencia', 'descripcion' => 'Software especializado para gestión contable.', 'precio' => 1500.00, 'stock' => 15],
                ['nombre' => 'Calculadora', 'unidad' => 'unidad', 'descripcion' => 'Calculadora científica programable.', 'precio' => 200.00, 'stock' => 60],
                ['nombre' => 'Libro Mayor', 'unidad' => 'unidad', 'descripcion' => 'Libro mayor para registro contable manual.', 'precio' => 45.00, 'stock' => 80],
                ['nombre' => 'Archivador', 'unidad' => 'unidad', 'descripcion' => 'Archivador metálico de cuatro gavetas.', 'precio' => 300.00, 'stock' => 20]
            ],
            'Talento Humano' => [
                ['nombre' => 'Formulario de Evaluación', 'unidad' => 'paquete', 'descripcion' => 'Paquete de formularios para evaluaciones de desempeño.', 'precio' => 50.00, 'stock' => 100],
                ['nombre' => 'Manual de Empleado', 'unidad' => 'unidad', 'descripcion' => 'Manual con políticas y procedimientos de la empresa.', 'precio' => 80.00, 'stock' => 60],
                ['nombre' => 'Plan de Capacitación', 'unidad' => 'paquete', 'descripcion' => 'Material impreso para capacitaciones internas.', 'precio' => 300.00, 'stock' => 25]
            ],
            'Compras' => [
                ['nombre' => 'Orden de Compra', 'unidad' => 'paquete', 'descripcion' => 'Paquete de órdenes de compra preimpresas.', 'precio' => 40.00, 'stock' => 200],
                ['nombre' => 'Catálogo de Productos', 'unidad' => 'unidad', 'descripcion' => 'Catálogo actualizado con lista de productos disponibles.', 'precio' => 25.00, 'stock' => 80],
                ['nombre' => 'Carrito de Compras', 'unidad' => 'unidad', 'descripcion' => 'Carrito metálico para compras en almacén.', 'precio' => 500.00, 'stock' => 15]
            ],
            'Calidad' => [
                ['nombre' => 'Checklist de Calidad', 'unidad' => 'paquete', 'descripcion' => 'Lista de verificación para control de calidad.', 'precio' => 30.00, 'stock' => 120],
                ['nombre' => 'Manual de Procesos', 'unidad' => 'unidad', 'descripcion' => 'Manual detallado de procesos internos.', 'precio' => 90.00, 'stock' => 40],
                ['nombre' => 'Equipo de Medición', 'unidad' => 'unidad', 'descripcion' => 'Instrumento de medición de alta precisión.', 'precio' => 750.00, 'stock' => 10]
            ],
            'HSEQ' => [
                ['nombre' => 'Casco de Seguridad', 'unidad' => 'unidad', 'descripcion' => 'Casco de seguridad industrial certificado.', 'precio' => 80.00, 'stock' => 70],
                ['nombre' => 'Chaleco Reflectivo', 'unidad' => 'unidad', 'descripcion' => 'Chaleco de alta visibilidad para trabajos en vía.', 'precio' => 60.00, 'stock' => 90],
                ['nombre' => 'Guantes Industriales', 'unidad' => 'par', 'descripcion' => 'Guantes de protección para trabajos pesados.', 'precio' => 40.00, 'stock' => 120]
            ],
            'Comercial' => [
                ['nombre' => 'Plan de Marketing', 'unidad' => 'paquete', 'descripcion' => 'Documentación impresa con estrategias de marketing.', 'precio' => 250.00, 'stock' => 20],
                ['nombre' => 'Catálogo de Ventas', 'unidad' => 'unidad', 'descripcion' => 'Catálogo con ofertas y promociones.', 'precio' => 30.00, 'stock' => 60],
                ['nombre' => 'Material POP', 'unidad' => 'paquete', 'descripcion' => 'Material publicitario para punto de venta.', 'precio' => 100.00, 'stock' => 40]
            ],
            'Operaciones' => [
                ['nombre' => 'Herramienta Industrial', 'unidad' => 'unidad', 'descripcion' => 'Herramienta manual de uso industrial.', 'precio' => 500.00, 'stock' => 25],
                ['nombre' => 'Carretilla', 'unidad' => 'unidad', 'descripcion' => 'Carretilla metálica para transporte de carga.', 'precio' => 400.00, 'stock' => 15],
                ['nombre' => 'Maquinaria Ligera', 'unidad' => 'unidad', 'descripcion' => 'Maquinaria ligera para trabajos en obra.', 'precio' => 3500.00, 'stock' => 5]
            ],
            'Financiera' => [
                ['nombre' => 'Reporte Financiero', 'unidad' => 'paquete', 'descripcion' => 'Reporte impreso con estados financieros.', 'precio' => 150.00, 'stock' => 30],
                ['nombre' => 'Software de Finanzas', 'unidad' => 'licencia', 'descripcion' => 'Programa para gestión financiera.', 'precio' => 1800.00, 'stock' => 12],
                ['nombre' => 'Libro de Cuentas', 'unidad' => 'unidad', 'descripcion' => 'Libro físico para registro de cuentas.', 'precio' => 60.00, 'stock' => 50]
            ],
            'Mantenimiento' => [
                ['nombre' => 'Lubricante', 'unidad' => 'litro', 'descripcion' => 'Aceite lubricante para maquinaria.', 'precio' => 25.00, 'stock' => 100],
                ['nombre' => 'Herramientas de Reparación', 'unidad' => 'unidad', 'descripcion' => 'Kit de herramientas para reparaciones.', 'precio' => 300.00, 'stock' => 20],
                ['nombre' => 'Repuestos', 'unidad' => 'unidad', 'descripcion' => 'Repuestos originales para equipos.', 'precio' => 150.00, 'stock' => 30]
            ],
            'otros' => [
                ['nombre' => 'Producto Genérico', 'unidad' => 'unidad', 'descripcion' => 'Artículo sin categoría específica.', 'precio' => 50.00, 'stock' => 80],
                ['nombre' => 'Artículo Varios', 'unidad' => 'unidad', 'descripcion' => 'Artículo misceláneo para uso general.', 'precio' => 40.00, 'stock' => 70],
                ['nombre' => 'Accesorio', 'unidad' => 'unidad', 'descripcion' => 'Accesorio adicional para equipos o herramientas.', 'precio' => 20.00, 'stock' => 150]
            ]
        ];

        $categoria = $this->faker->randomElement(array_keys($categorias));
        $productoData = $this->faker->randomElement($categorias[$categoria]);

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
