<?php

namespace Database\Seeders;

use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Estatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
            // Si la OC no tiene requisición asociada, intentar asociar una disponible que tenga productos
            if (empty($ordenCompra->requisicion_id)) {
                $requisicionesUsadas = DB::table('orden_compras')
                    ->whereNotNull('requisicion_id')
                    ->pluck('requisicion_id');

                $candidatas = DB::table('estatus_requisicion')
                    ->where('estatus', 1)
                    ->whereIn('estatus_id', $estatusIdsPermitidos);

                if ($requisicionesUsadas->isNotEmpty()) {
                    $candidatas->whereNotIn('requisicion_id', $requisicionesUsadas);
                }

                // Debe tener productos asociados en producto_requisicion
                $candidatas->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                      ->from('producto_requisicion')
                      ->whereColumn('producto_requisicion.id_requisicion', 'estatus_requisicion.requisicion_id');
                });

                $requisicionLibre = $candidatas->orderBy('date_update', 'desc')->value('requisicion_id');

                if ($requisicionLibre) {
                    $affected = DB::table('orden_compras')
                        ->where('id', $ordenCompra->id)
                        ->update(['requisicion_id' => $requisicionLibre, 'updated_at' => now()]);

                    if ($affected === 0) {
                        $newId = DB::table('orden_compras')->insertGetId([
                            'requisicion_id' => $requisicionLibre,
                            'oc_user'        => $ordenCompra->oc_user ?? 'Seeder',
                            'observaciones'  => 'OC clonada por seeder para asociar requisición',
                            'date_oc'        => now()->toDateString(),
                            'methods_oc'     => 'Transferencia',
                            'plazo_oc'       => '30 días',
                            'order_oc'       => 'OC-' . strtoupper(Str::random(6)),
                            'validation_hash'=> null,
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                        $ordenCompra->id = $newId;
                        $ordenCompra->requisicion_id = $requisicionLibre;
                    } else {
                        $ordenCompra->requisicion_id = $requisicionLibre;
                    }
                } else {
                    $this->command->warn("OC #{$ordenCompra->id} no tiene requisición asociada y no se encontró disponible con productos. Saltando...");
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
                        'requisicion_id'   => $ordenCompra->requisicion_id, // <-- CORREGIDO: SE ASIGNA requisicion_id
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