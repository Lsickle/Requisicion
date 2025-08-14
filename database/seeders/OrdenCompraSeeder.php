<?php

namespace Database\Seeders;

use App\Models\OrdenCompra;
use App\Models\Requisicion;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class OrdenCompraSeeder extends Seeder
{
    public function run()
    {
        if (Requisicion::count() == 0) {
            $this->call(RequisicionSeeder::class);
        }

        $requisiciones = Requisicion::all();

        foreach ($requisiciones as $requisicion) {
            // Obtener el proveedor más común de los productos en esta requisición
            $proveedorId = DB::table('producto_requisicion')
                ->join('productos', 'producto_requisicion.id_producto', '=', 'productos.id')
                ->where('producto_requisicion.id_requisicion', $requisicion->id)
                ->select('productos.proveedor_id')
                ->groupBy('productos.proveedor_id')
                ->orderByRaw('COUNT(*) DESC')
                ->value('proveedor_id');

            // Si no hay productos, seleccionar un proveedor aleatorio
            if (!$proveedorId) {
                $proveedorId = DB::table('proveedores')->inRandomOrder()->value('id');
            }

            OrdenCompra::create([
                'requisicion_id' => $requisicion->id,
                'proveedor_id' => $proveedorId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->command->info('¡Órdenes de compra creadas con proveedores basados en productos de requisición!');
    }
}