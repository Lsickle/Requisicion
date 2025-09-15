<?php

namespace Database\Seeders;

use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class OrdenCompraProductoSeeder extends Seeder
{
    public function run(): void
    {
        // Verificar si existen órdenes de compra, si no, crear algunas
        if (OrdenCompra::count() == 0) {
            $this->call(OrdenCompraSeeder::class);
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

                // Verificar si ya existe este registro para evitar duplicados
                $existeRegistro = DB::table('ordencompra_producto')
                    ->where('producto_id', $productoId)
                    ->where('orden_compras_id', $ordenCompra->id)
                    ->exists();

                if (!$existeRegistro) {
                    // Insertar en la tabla pivot (solo columnas existentes en la migración)
                    DB::table('ordencompra_producto')->insert([
                        'producto_id'      => $productoId,
                        'orden_compras_id' => $ordenCompra->id,
                        'proveedor_id'     => $proveedorId,
                        'total'            => (int)($productoReq->pr_amount ?? 1),
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            }
        }

        $this->command->info('¡Tabla ordencompra_producto poblada exitosamente!');
    }
}
