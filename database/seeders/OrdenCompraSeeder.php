<?php

namespace Database\Seeders;

use App\Models\OrdenCompra;
use App\Models\Requisicion;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class OrdenCompraSeeder extends Seeder
{
    public function run()
    {
        if (Requisicion::count() == 0) {
            $this->call(RequisicionSeeder::class);
        }

        $requisiciones = Requisicion::all();

        foreach ($requisiciones as $requisicion) {
            // Primero intentamos obtener el proveedor de los productos de la requisición
            $proveedorId = null;
            
            // Verificamos la estructura real de la tabla pivot
            if (Schema::hasTable('producto_requisicion')) {
                // Probamos con diferentes nombres de columnas comunes en tablas pivot
                $columnas = DB::getSchemaBuilder()->getColumnListing('producto_requisicion');
                
                // Buscamos nombres comunes para la columna de requisición
                $columnaRequisicion = null;
                if (in_array('requisicion_id', $columnas)) {
                    $columnaRequisicion = 'requisicion_id';
                } elseif (in_array('id_requisicion', $columnas)) {
                    $columnaRequisicion = 'id_requisicion';
                } elseif (in_array('requisicion', $columnas)) {
                    $columnaRequisicion = 'requisicion';
                }
                
                // Buscamos nombres comunes para la columna de producto
                $columnaProducto = null;
                if (in_array('producto_id', $columnas)) {
                    $columnaProducto = 'producto_id';
                } elseif (in_array('id_producto', $columnas)) {
                    $columnaProducto = 'id_producto';
                } elseif (in_array('producto', $columnas)) {
                    $columnaProducto = 'producto';
                }
                
                if ($columnaRequisicion && $columnaProducto) {
                    $proveedorId = DB::table('producto_requisicion')
                        ->join('productos', "producto_requisicion.{$columnaProducto}", '=', 'productos.id')
                        ->where("producto_requisicion.{$columnaRequisicion}", $requisicion->id)
                        ->select('productos.proveedor_id')
                        ->groupBy('productos.proveedor_id')
                        ->orderByRaw('COUNT(*) DESC')
                        ->value('productos.proveedor_id');
                }
            }

            // Si no encontramos proveedor en los productos, seleccionar uno aleatorio
            if (!$proveedorId) {
                $proveedorId = DB::table('proveedores')->inRandomOrder()->value('id');
                
                // Si no hay proveedores, crear uno
                if (!$proveedorId) {
                    $proveedorId = DB::table('proveedores')->insertGetId([
                        'nombre' => 'Proveedor Default',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            OrdenCompra::create([
                'requisicion_id' => $requisicion->id,
                'proveedor_id' => $proveedorId,
                'observaciones' => null,
                'date_oc' => now(),
                'methods_oc' => 'Transferencia',
                'plazo_oc' => '30 días',
                'order_oc' => 'OC-' . str_pad($requisicion->id, 6, '0', STR_PAD_LEFT),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->command->info('¡Órdenes de compra creadas exitosamente!');
    }
}