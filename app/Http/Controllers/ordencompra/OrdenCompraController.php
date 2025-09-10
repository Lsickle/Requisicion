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
                    ->orderBy('productos.id', 'asc');
            })
            ->get();

        $requisicion = null;
        $productosDisponibles = collect();
        $productoSeleccionado = null;
        $proveedores = Proveedor::all();

        if ($request->has('requisicion_id') && $request->requisicion_id != 0) {
            $requisicion = Requisicion::find($request->requisicion_id);

            if ($requisicion) {
                $productosDisponibles = Producto::select('productos.*')
                    ->join('producto_requisicion', 'productos.id', '=', 'producto_requisicion.id_producto')
                    ->where('producto_requisicion.id_requisicion', $requisicion->id)
                    ->whereNull('productos.deleted_at')
                    ->orderBy('productos.id', 'asc')
                    ->get();
            }
        }

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
            $orden = OrdenCompra::create([
                'requisicion_id' => $request->requisicion_id,
                'proveedor_id'   => $request->proveedor_id,
                'observaciones'  => $request->observaciones,
                'methods_oc'     => $request->methods_oc,
                'plazo_oc'       => $request->plazo_oc,
                'date_oc'        => now(),
                'order_oc'       => 'OC-' . now()->format('YmdHis'),
            ]);

            foreach ($request->productos as $productoId) {
                OrdencompraProducto::create([
                    'producto_id'      => $productoId,
                    'orden_compras_id' => $orden->id,
                    'proveedor_id'     => $request->proveedor_id,
                    'observaciones'    => $request->observaciones,
                    'methods_oc'       => $request->methods_oc,
                    'plazo_oc'         => $request->plazo_oc,
                    'date_oc'          => now(),
                    'order_oc'         => $orden->order_oc,
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
     * Mostrar detalle
     */
    public function show($id)
    {
        $orden = OrdenCompra::with(['requisicion', 'proveedor', 'ordencompraProductos.producto'])
            ->findOrFail($id);

        return view('ordenes_compra.create', ['ordenCompra' => $orden]);
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        Log::info("=== EDIT ORDEN ===");
        Log::info("ID recibido (Orden): " . $id);

        $ordenCompra = OrdenCompra::with(['proveedor', 'requisicion'])->findOrFail($id);
        $requisicion = Requisicion::with('productos')->findOrFail($ordenCompra->requisicion_id);

        $centros = Centro::all();

        $distribucion = [];
        foreach ($requisicion->productos as $producto) {
            $distribucion[$producto->id] = DB::table('centro_producto')
                ->where('requisicion_id', $requisicion->id)
                ->where('producto_id', $producto->id)
                ->pluck('amount', 'centro_id')
                ->toArray();
        }

        return view('ordenes_compra.edit', [
            'ordenCompra' => $ordenCompra,
            'requisicion' => $requisicion,
            'centros' => $centros,
            'distribucion' => $distribucion,
        ]);
    }

    /**
     * Actualizar orden
     */
    public function update(Request $request, $id)
    {
        $ordenCompra = OrdenCompra::with('requisicion')->findOrFail($id);
        $requisicion = $ordenCompra->requisicion;

        DB::beginTransaction();
        try {
            $ordenCompra->update([
                'date_oc' => now(),
                'observaciones' => $request->observaciones,
                'methods_oc' => $request->methods_oc,
                'plazo_oc' => $request->plazo_oc,
            ]);

            if ($request->has('productos')) {
                foreach ($request->productos as $productoId => $data) {
                    if (isset($data['eliminar']) && $data['eliminar'] == 1) {
                        OrdencompraProducto::where('orden_compras_id', $ordenCompra->id)
                            ->where('producto_id', $productoId)
                            ->delete();

                        DB::table('centro_producto')
                            ->where('requisicion_id', $requisicion->id)
                            ->where('producto_id', $productoId)
                            ->update(['deleted_at' => now()]);
                        continue;
                    }

                    OrdencompraProducto::where('orden_compras_id', $ordenCompra->id)
                        ->where('producto_id', $productoId)
                        ->delete();

                    DB::table('centro_producto')
                        ->where('requisicion_id', $requisicion->id)
                        ->where('producto_id', $productoId)
                        ->update(['deleted_at' => now()]);

                    OrdencompraProducto::create([
                        'producto_id'      => $productoId,
                        'orden_compras_id' => $ordenCompra->id,
                        'proveedor_id'     => $ordenCompra->proveedor_id,
                        'observaciones'    => $ordenCompra->observaciones,
                        'methods_oc'       => $ordenCompra->methods_oc,
                        'plazo_oc'         => $ordenCompra->plazo_oc,
                        'date_oc'          => now(),
                        'order_oc'         => $ordenCompra->order_oc,
                    ]);

                    if (isset($data['centros'])) {
                        foreach ($data['centros'] as $centroId => $cantidad) {
                            DB::table('centro_producto')->insert([
                                'requisicion_id' => $requisicion->id,
                                'producto_id'    => $productoId,
                                'centro_id'      => $centroId,
                                'amount'         => $cantidad,
                                'created_at'     => now(),
                                'updated_at'     => now(),
                            ]);
                        }
                    }
                }
            }

            if ($request->has('nuevos')) {
                foreach ($request->nuevos as $nuevo) {
                    $producto = Producto::create([
                        'name_produc' => $nuevo['nombre'],
                    ]);

                    DB::table('producto_requisicion')->insert([
                        'id_producto'   => $producto->id,
                        'id_requisicion'=> $requisicion->id,
                        'pr_amount'     => $nuevo['cantidad'],
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    OrdencompraProducto::create([
                        'producto_id'      => $producto->id,
                        'orden_compras_id' => $ordenCompra->id,
                        'proveedor_id'     => $ordenCompra->proveedor_id,
                        'observaciones'    => $ordenCompra->observaciones,
                        'methods_oc'       => $ordenCompra->methods_oc,
                        'plazo_oc'         => $ordenCompra->plazo_oc,
                        'date_oc'          => now(),
                        'order_oc'         => $ordenCompra->order_oc,
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
     * Eliminar
     */
    public function destroy($id)
    {
        $orden = OrdenCompra::findOrFail($id);
        $orden->forceDelete();

        return redirect()->route('ordenes_compra.lista')
            ->with('success', 'Orden de compra eliminada permanentemente.');
    }

    /**
     * Exportar PDF
     */
    public function exportPdf($id)
    {
        $orden = OrdenCompra::with(['requisicion', 'proveedor', 'ordencompraProductos.producto'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('ordenes_compra.pdf', ['ordenCompra' => $orden]);
        return $pdf->download("orden_compra_{$orden->id}.pdf");
    }
}
