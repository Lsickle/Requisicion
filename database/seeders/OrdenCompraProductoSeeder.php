<?php

namespace Database\Seeders;

use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class OrdenCompraProductoSeeder extends Seeder
{
    public function run(): void
    {
        // Verificar si existen órdenes de compra, si no, crear algunas
        if (OrdenCompra::count() == 0) {
            $this->call(OrdenCompraSeeder::class);
        }

        // Verificar si existe la tabla producto_requisicion
        if (!Schema::hasTable('producto_requisicion')) {
            $this->command->error('La tabla producto_requisicion no existe. Ejecuta primero su migración.');
            return;
        }

        $ordenesCompra = OrdenCompra::with('requisicion')->get();

        foreach ($ordenesCompra as $ordenCompra) {
            // Obtener productos relacionados con la requisición de esta orden
            $productosRequisicion = DB::table('producto_requisicion')
                ->where('id_requisicion', $ordenCompra->requisicion_id)
                ->get();

            // Si no hay productos en la requisición, saltar a la siguiente orden
            if ($productosRequisicion->isEmpty()) {
                $this->command->warn("La requisición #{$ordenCompra->requisicion_id} no tiene productos. Saltando...");
                continue;
            }

            foreach ($productosRequisicion as $productoReq) {
                // Obtener el ID del producto usando la columna correcta id_producto
                $productoId = $productoReq->id_producto;
                
                // Obtener el producto
                $producto = Producto::find($productoId);
                
                if (!$producto) {
                    $this->command->warn("Producto con ID {$productoId} no encontrado. Saltando...");
                    continue;
                }

                // Obtener un proveedor (usar el del producto o uno aleatorio)
                $proveedorId = $producto->proveedor_id ?? Proveedor::inRandomOrder()->value('id');
                
                if (!$proveedorId) {
                    // Si no hay proveedores, crear uno
                    $proveedorId = Proveedor::factory()->create()->id;
                }

                // Obtener el ID de la relación usando la columna id
                $productoRequisicionId = $productoReq->id;

                // Verificar si ya existe este registro para evitar duplicados
                $existeRegistro = DB::table('ordencompra_producto')
                    ->where('producto_id', $productoId)
                    ->where('orden_compras_id', $ordenCompra->id)
                    ->where('producto_requisicion_id', $productoRequisicionId)
                    ->exists();
                
                if (!$existeRegistro) {
                    // Insertar en la tabla pivot
                    DB::table('ordencompra_producto')->insert([
                        'producto_id' => $productoId,
                        'orden_compras_id' => $ordenCompra->id,
                        'producto_requisicion_id' => $productoRequisicionId,
                        'proveedor_seleccionado' => $proveedorId,
                        'observaciones' => rand(0, 1) ? 'Producto urgente' : null,
                        'date_oc' => now()->subDays(rand(1, 30)),
                        'methods_oc' => $this->getRandomPaymentMethod(),
                        'plazo_oc' => $this->getRandomPaymentTerm(),
                        'order_oc' => 'OC-' . str_pad($ordenCompra->id, 6, '0', STR_PAD_LEFT) . '-P' . str_pad($producto->id, 3, '0', STR_PAD_LEFT),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }

        $this->command->info('¡Tabla pivot ordencompra_producto poblada exitosamente!');
    }

    /**
     * Obtener un método de pago aleatorio
     */
    private function getRandomPaymentMethod(): string
    {
        $methods = ['Transferencia', 'Cheque', 'Efectivo', 'Tarjeta de Crédito', 'Tarjeta de Débito'];
        return $methods[array_rand($methods)];
    }

    /**
     * Obtener un plazo de pago aleatorio
     */
    private function getRandomPaymentTerm(): string
    {
        $terms = ['Contado', '15 días', '30 días', '45 días', '60 días', '90 días'];
        return $terms[array_rand($terms)];
    }
}