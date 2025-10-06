<?php

namespace Database\Seeders;

use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Estatus;
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

        // IDs de estatus válidos para asociar requisiciones a OCs huérfanas
        $etapasConOC = [
            'Contacto con proveedor',
            'Entrega aproximada',
            'Recibido en bodega',
            'Recogido por coordinador',
            'Completado',
        ];
        $estatusIdsPermitidos = Estatus::whereIn('status_name', $etapasConOC)->pluck('id');

        $ordenesCompra = OrdenCompra::with('requisicion')->get();

        foreach ($ordenesCompra as $ordenCompra) {
            // Si la OC no tiene requisición asociada, intentar asociar una disponible
            if (empty($ordenCompra->requisicion_id)) {
                $requisicionesUsadas = DB::table('orden_compras')
                    ->whereNotNull('requisicion_id')
                    ->pluck('requisicion_id');

                $requisicionLibre = DB::table('estatus_requisicion')
                    ->where('estatus', 1)
                    ->whereIn('estatus_id', $estatusIdsPermitidos)
                    ->whereNotIn('requisicion_id', $requisicionesUsadas)
                    ->orderBy('date_update', 'desc')
                    ->value('requisicion_id');

                if ($requisicionLibre) {
                    DB::table('orden_compras')->where('id', $ordenCompra->id)
                        ->update(['requisicion_id' => $requisicionLibre, 'updated_at' => now()]);
                    // Refrescar en memoria
                    $ordenCompra->requisicion_id = $requisicionLibre;
                } else {
                    $this->command->warn("OC #{$ordenCompra->id} no tiene requisición asociada y no se encontró disponible. Saltando...");
                    continue;
                }
            }

            // Obtener productos relacionados con la requisición de esta orden
            $productosRequisicion = DB::table('producto_requisicion')
                ->where('id_requisicion', $ordenCompra->requisicion_id)
                ->get();

            if ($productosRequisicion->isEmpty()) {
                $this->command->warn("La requisición #{$ordenCompra->requisicion_id} no tiene productos. Saltando...");
                continue;
            }

            foreach ($productosRequisicion as $productoReq) {
                $productoId = $productoReq->id_producto;
                $producto = Producto::find($productoId);

                if (!$producto) {
                    $this->command->warn("Producto con ID {$productoId} no encontrado. Saltando...");
                    continue;
                }

                $proveedorId = $producto->proveedor_id ?? Proveedor::inRandomOrder()->value('id');
                if (!$proveedorId) {
                    $proveedorId = Proveedor::factory()->create()->id;
                }

                $existeRegistro = DB::table('ordencompra_producto')
                    ->where('producto_id', $productoId)
                    ->where('orden_compras_id', $ordenCompra->id)
                    ->exists();

                if (!$existeRegistro) {
                    DB::table('ordencompra_producto')->insert([
                        'producto_id'      => $productoId,
                        'orden_compras_id' => $ordenCompra->id,
                        'proveedor_id'     => $proveedorId,
                        'total'            => (int)($productoReq->pr_amount ?? 1),
                        'stock_e'          => null,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            }
        }

        $this->command->info('¡Tabla ordencompra_producto poblada exitosamente, asegurando requisicion_id en OCs!');
    }
}
