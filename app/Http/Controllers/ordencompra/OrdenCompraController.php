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
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

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

        return view('ordenes_compra.index', compact('ordenes'));
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
                    ->orderBy('productos.id', 'asc'); // ✅ Corregido
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
                    ->orderBy('productos.id', 'asc') // ✅ Corregido
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
            // Crear la orden
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
                    'producto_id'            => $productoId,
                    'orden_compras_id'       => $orden->id,
                    'producto_requisicion_id' => $pivot->id ?? null,
                    'proveedor_seleccionado' => $request->proveedor_id,
                    'observaciones'          => $request->observaciones,
                    'methods_oc'             => $request->methods_oc,
                    'plazo_oc'               => $request->plazo_oc,
                    'date_oc'                => now(),
                    'order_oc'               => $orden->order_oc,
                ]);
            }

            DB::commit();
            return redirect()->route('ordenes_compra.index')
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

        return view('ordenes_compra.show', compact('orden'));
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        $orden = OrdenCompra::with('ordencompraProductos.producto')->findOrFail($id);
        $proveedores = Proveedor::all();

        return view('ordenes_compra.edit', compact('orden', 'proveedores'));
    }

    /**
     * Actualizar orden
     */
    public function update(Request $request, $id)
    {
        $orden = OrdenCompra::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'date_oc'       => 'required|date',
            'proveedor_id'  => 'required|exists:proveedores,id',
            'methods_oc'    => 'nullable|string|max:255',
            'plazo_oc'      => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'precio'        => 'required|numeric|min:0',
        ]);


        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $orden->update([
            'proveedor_id'  => $request->proveedor_id,
            'methods_oc'    => $request->methods_oc,
            'plazo_oc'      => $request->plazo_oc,
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('ordenes_compra.index')
            ->with('success', 'Orden de compra actualizada exitosamente.');
    }

    /**
     * Eliminar (borrado permanente)
     */
    public function destroy($id)
    {
        $orden = OrdenCompra::findOrFail($id);
        $orden->forceDelete(); // 🔥 Eliminación directa (no soft delete)

        return redirect()->route('ordenes_compra.index')
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
