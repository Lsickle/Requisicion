<?php

namespace App\Http\Controllers\ordencompra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\OrdencompraProducto;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\Centro;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class OrdenCompraController extends Controller
{
    /**
     * Mostrar listado de órdenes de compra
     */
    public function index()
    {
        $ordenes = OrdenCompra::with(['requisicion', 'proveedor'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('ordenes_compra.lista', compact('ordenes'));
    }

    /**
     * Formulario de creación
     */
    public function create(Request $request)
    {
        $requisiciones = Requisicion::select('requisicion.*')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('productos')
                    ->join('producto_requisicion', 'productos.id', '=', 'producto_requisicion.id_producto')
                    ->whereColumn('requisicion.id', 'producto_requisicion.id_requisicion')
                    ->whereNull('productos.deleted_at')
                    ->orderBy('productos.id', 'asc'); //  Corregido
            })
            ->get();

        $requisicion = null;
        $productosDisponibles = collect();
        $productoSeleccionado = null;
        $proveedores = Proveedor::all();

        // Si seleccionó una requisición
        if ($request->has('requisicion_id') && $request->requisicion_id != 0) {
            $requisicion = Requisicion::find($request->requisicion_id);

            if ($requisicion) {
                $productosDisponibles = Producto::select('productos.*')
                    ->join('producto_requisicion', 'productos.id', '=', 'producto_requisicion.id_producto')
                    ->where('producto_requisicion.id_requisicion', $requisicion->id)
                    ->whereNull('productos.deleted_at')
                    ->orderBy('productos.id', 'asc') //  Corregido
                    ->get();
            }
        }

        // Si seleccionó un producto
        if ($request->has('producto_id') && $request->producto_id != 0) {
            $productoSeleccionado = Producto::with('proveedor')->find($request->producto_id);
        }

        return view('ordenes_compra.create', compact(
            'requisiciones',
            'requisicion',
            'productosDisponibles',
            'productoSeleccionado',
            'proveedores'
        ));
    }

    /**
     * Guardar nueva orden
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proveedor_id'   => 'required|exists:proveedores,id',
            'methods_oc'     => 'nullable|string|max:255',
            'plazo_oc'       => 'nullable|string|max:255',
            'observaciones'  => 'nullable|string',
            'requisicion_id' => 'required|exists:requisicion,id',
            'productos'      => 'required|array|min:1',
            'productos.*'    => 'exists:productos,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Crear la orden de compra
            $orden = OrdenCompra::create([
                'requisicion_id' => $request->requisicion_id,
                'proveedor_id'   => $request->proveedor_id,
                'observaciones'  => $request->observaciones,
                'methods_oc'     => $request->methods_oc,
                'plazo_oc'       => $request->plazo_oc,
                'date_oc'        => now(),
                'order_oc'       => 'OC-' . now()->format('YmdHis'),
            ]);

            // Guardar productos asociados
            foreach ($request->productos as $productoId) {
                $pivot = DB::table('producto_requisicion')
                    ->where('id_producto', $productoId)
                    ->where('id_requisicion', $request->requisicion_id)
                    ->first();

                OrdencompraProducto::create([
                    'producto_id'             => $productoId,
                    'orden_compras_id'        => $orden->id,
                    'producto_requisicion_id' => $pivot->id ?? null,
                    'proveedor_id'            => $request->proveedor_id, // ✅ corregido
                    'observaciones'           => $request->observaciones,
                    'methods_oc'              => $request->methods_oc,
                    'plazo_oc'                => $request->plazo_oc,
                    'date_oc'                 => now(),
                    'order_oc'                => $orden->order_oc,
                ]);
            }

            DB::commit();
            return redirect()->route('ordenes_compra.lista')
                ->with('success', 'Orden de compra creada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }


    /**
     * Mostrar detalle de una orden
     */
    public function show($id)
    {
        $orden = OrdenCompra::with(['requisicion', 'proveedor', 'ordencompraProductos.producto'])
            ->findOrFail($id);

        return view('ordenes_compra.create', compact('orden'));
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        Log::info("=== EDIT ORDEN ===");
        Log::info("ID recibido (Orden): " . $id);

        // Buscar la orden de compra con su proveedor y requisición
        $ordenCompra = OrdenCompra::with(['proveedor', 'requisicion'])->findOrFail($id);

        // Traer la requisición asociada
        $requisicion = Requisicion::with('productos')->findOrFail($ordenCompra->requisicion_id);

        $centros = Centro::all();

        // Obtener distribución por centro desde la requisición real
        $distribucion = [];
        foreach ($requisicion->productos as $producto) {
            $distribucion[$producto->id] = DB::table('centro_producto')
                ->where('requisicion_id', $requisicion->id)
                ->where('producto_id', $producto->id)
                ->pluck('amount', 'centro_id')
                ->toArray();
        }

        return view('ordenes_compra.edit', compact('ordenCompra', 'requisicion', 'centros', 'distribucion'));
    }




    public function update(Request $request, $id)
    {
        $ordenCompra = OrdenCompra::with('requisicion')->findOrFail($id);
        $requisicion = $ordenCompra->requisicion;

        DB::beginTransaction();
        try {
            // 1. Actualizar datos generales de la orden
            $ordenCompra->update([
                'date_oc' => $request->date_oc,
                'observaciones' => $request->observaciones,
                'methods_oc' => $request->methods_oc,
                'plazo_oc' => $request->plazo_oc,
            ]);

            // 2. Soft delete de productos previos
            OrdencompraProducto::where('orden_compras_id', $ordenCompra->id)->delete();

            DB::table('centro_producto')
                ->where('requisicion_id', $requisicion->id)
                ->update(['deleted_at' => now()]);

            // 3. Insertar productos y su distribución
            foreach ($request->productos as $productoData) {
                // actualizar cantidad en pivot requisición-producto
                DB::table('producto_requisicion')
                    ->where('id_requisicion', $requisicion->id)
                    ->where('id_producto', $productoData['id'])
                    ->update(['pr_amount' => $productoData['cantidad']]);

                // Crear nuevo registro en ordencompra_producto
                OrdencompraProducto::create([
                    'producto_id' => $productoData['id'],
                    'orden_compras_id' => $ordenCompra->id,
                    'producto_requisicion_id' => DB::table('producto_requisicion')
                        ->where('id_requisicion', $requisicion->id)
                        ->where('id_producto', $productoData['id'])
                        ->value('id'),
                    'proveedor_id' => $ordenCompra->proveedor_id,
                    'observaciones' => $ordenCompra->observaciones,
                    'methods_oc' => $ordenCompra->methods_oc,
                    'plazo_oc' => $ordenCompra->plazo_oc,
                    'date_oc' => now(),
                    'order_oc' => $ordenCompra->order_oc,
                ]);

                // Insertar distribuciones nuevas
                foreach ($productoData['centros'] as $centroId => $cantidad) {
                    DB::table('centro_producto')->insert([
                        'requisicion_id' => $requisicion->id,
                        'producto_id'    => $productoData['id'],
                        'centro_id'      => $centroId,
                        'amount'         => $cantidad,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('ordenes_compra.lista')
                ->with('success', 'Orden de compra actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }


    /**
     * Eliminar (borrado permanente)
     */
    public function destroy($id)
    {
        $orden = OrdenCompra::findOrFail($id);
        $orden->forceDelete(); // Eliminación directa (no soft delete)

        return redirect()->route('ordenes_compra.lista')
            ->with('success', 'Orden de compra eliminada permanentemente.');
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf($id)
    {
        $orden = OrdenCompra::with(['requisicion', 'proveedor', 'ordencompraProductos.producto'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('ordenes_compra.pdf', compact('orden'));
        return $pdf->download("orden_compra_{$orden->id}.pdf");
    }
}
