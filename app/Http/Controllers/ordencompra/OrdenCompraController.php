<?php

namespace App\Http\Controllers\ordencompra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\OrdencompraProducto;
use Illuminate\Support\Facades\Validator;

class OrdenCompraController extends Controller
{
    public function index()
    {
        $ordenes = OrdenCompra::with(['requisicion'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ordenes_compra.create', compact('ordenes'));
    }

    public function create(Request $request)
    {
        $requisicion = null;
        $proveedoresDisponibles = collect();
        $proveedoresConOrden = collect();
        $proveedorSeleccionado = null;

        $orderNumber = 'OC-' . str_pad((OrdenCompra::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);

        if ($request->has('requisicion_id') && $request->requisicion_id != 0) {
            $requisicion = Requisicion::with(['productos.proveedor', 'ordenesCompra.proveedor'])->find($request->requisicion_id);

            if ($requisicion) {
                // Obtener todos los proveedores de los productos de la requisición
                $todosProveedores = $requisicion->productos->pluck('proveedor')->unique('id');

                // Obtener proveedores que ya tienen órdenes de compra
                $proveedoresConOrden = $requisicion->ordenesCompra->pluck('proveedor_id');
                $proveedoresDisponibles = $todosProveedores->whereNotIn('id', $proveedoresConOrden);

                if ($request->has('proveedor_id') && $request->proveedor_id != 0) {
                    $proveedorSeleccionado = Proveedor::find($request->proveedor_id);
                }
            }
        }

        $productosProveedor = collect();
        if ($requisicion && $proveedorSeleccionado) {
            $productosProveedor = $requisicion->productos->where('proveedor_id', $proveedorSeleccionado->id);
        }

        return view('ordenes_compra.create', compact(
            'requisicion',
            'orderNumber',
            'proveedoresDisponibles',
            'proveedoresConOrden',
            'proveedorSeleccionado',
            'productosProveedor',
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proveedor_id'   => 'required|exists:proveedores,id',
            'methods_oc'     => 'nullable|string|max:255',
            'plazo_oc'       => 'nullable|string|max:255',
            'observaciones'  => 'nullable|string',
            'requisicion_id' => 'required|exists:requisicion,id',
            'productos'      => 'required|array',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|numeric|min:1',
            'productos.*.precio'   => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $ordenExistente = OrdenCompra::where('proveedor_id', $request->proveedor_id)
            ->where('requisicion_id', $request->requisicion_id)
            ->first();

        if ($ordenExistente) {
            return back()->withInput()->withErrors(['error' => 'Ya existe una orden de compra para este proveedor en esta requisición.']);
        }

        try {
            DB::beginTransaction();

            $ultimo = OrdenCompra::orderByDesc('id')->first();
            $numero = $ultimo ? $ultimo->id + 1 : 1;
            $nuevoOrderOc = 'OC-' . str_pad($numero, 6, '0', STR_PAD_LEFT);

            // 📌 fecha automática con now()
            $fechaActual = now()->format('Y-m-d');

            $ordenCompra = OrdenCompra::create([
                'requisicion_id' => $request->requisicion_id,
                'proveedor_id'   => $request->proveedor_id,
                'observaciones'  => $request->observaciones,
                'date_oc'        => $fechaActual,
                'methods_oc'     => $request->methods_oc,
                'plazo_oc'       => $request->plazo_oc,
                'order_oc'       => $nuevoOrderOc,
            ]);

            foreach ($request->productos as $productoData) {
                OrdencompraProducto::create([
                    'producto_id'         => $productoData['id'],
                    'orden_compras_id'    => $ordenCompra->id,
                    'proveedor_seleccionado' => $request->proveedor_id,
                    'observaciones'       => $request->observaciones,
                    'date_oc'             => $fechaActual,
                    'methods_oc'          => $request->methods_oc,
                    'plazo_oc'            => $request->plazo_oc,
                    'order_oc'            => $nuevoOrderOc,
                    'cantidad'            => $productoData['cantidad'],
                    'precio_unitario'     => $productoData['precio'],
                ]);
            }

            DB::commit();

            $requisicion = Requisicion::with(['productos.proveedor', 'ordenesCompra'])->find($request->requisicion_id);
            $todosProveedores = $requisicion->productos->pluck('proveedor')->unique('id');
            $proveedoresConOrden = $requisicion->ordenesCompra->pluck('proveedor_id');
            $proveedoresDisponibles = $todosProveedores->whereNotIn('id', $proveedoresConOrden);

            if ($proveedoresDisponibles->count() > 0) {
                $siguienteProveedor = $proveedoresDisponibles->first();
                return redirect()->route('ordenes_compra.create', [
                    'requisicion_id' => $request->requisicion_id,
                    'proveedor_id' => $siguienteProveedor->id
                ])->with('success', 'Orden de compra creada correctamente. Continúa con el siguiente proveedor.');
            } else {
                return redirect()->route('ordenes_compra.create', [
                    'requisicion_id' => $request->requisicion_id
                ])->with('success', '¡Todas las órdenes de compra han sido creadas!');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al crear la orden de compra: ' . $e->getMessage()]);
        }
    }


    public function show(string $id)
    {
        $orden = OrdenCompra::with(['requisicion', 'proveedor', 'ordencompraProductos.producto'])->findOrFail($id);
        return view('ordenes_compra.show', compact('orden'));
    }

    public function edit(string $id)
    {
        $orden = OrdenCompra::with(['requisicion', 'ordencompraProductos.producto'])->findOrFail($id);
        $proveedores = Proveedor::all();

        return view('ordenes_compra.edit', compact('orden', 'proveedores'));
    }

    public function update(Request $request, string $id)
    {
        $orden = OrdenCompra::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'date_oc'       => 'required|date',
            'proveedor_id'  => 'required|exists:proveedores,id',
            'methods_oc'    => 'nullable|string|max:255',
            'plazo_oc'      => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'productos'     => 'required|array',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|numeric|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            // Actualizar la orden principal
            $orden->update([
                'proveedor_id' => $request->proveedor_id,
                'observaciones' => $request->observaciones,
                'date_oc' => $request->date_oc,
                'methods_oc' => $request->methods_oc,
                'plazo_oc' => $request->plazo_oc,
            ]);

            // Actualizar los registros en la tabla pivot
            foreach ($request->productos as $productoData) {
                $ordenProducto = OrdencompraProducto::where('orden_compras_id', $id)
                    ->where('producto_id', $productoData['id'])
                    ->first();

                if ($ordenProducto) {
                    $ordenProducto->update([
                        'proveedor_seleccionado' => $request->proveedor_id,
                        'observaciones' => $request->observaciones,
                        'date_oc' => $request->date_oc,
                        'methods_oc' => $request->methods_oc,
                        'plazo_oc' => $request->plazo_oc,
                        'cantidad' => $productoData['cantidad'],
                        'precio_unitario' => $productoData['precio'],
                    ]);
                } else {
                    // Crear nuevo registro si no existe
                    OrdencompraProducto::create([
                        'producto_id' => $productoData['id'],
                        'orden_compras_id' => $id,
                        'proveedor_seleccionado' => $request->proveedor_id,
                        'observaciones' => $request->observaciones,
                        'date_oc' => $request->date_oc,
                        'methods_oc' => $request->methods_oc,
                        'plazo_oc' => $request->plazo_oc,
                        'order_oc' => $orden->order_oc,
                        'cantidad' => $productoData['cantidad'],
                        'precio_unitario' => $productoData['precio'],
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('ordenes_compra.create', [
                'requisicion_id' => $orden->requisicion_id
            ])->with('success', 'Orden de compra actualizada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al actualizar la orden de compra: ' . $e->getMessage()]);
        }
    }

    public function destroy(string $id)
    {
        $orden = OrdenCompra::findOrFail($id);
        $requisicion_id = $orden->requisicion_id;

        try {
            DB::beginTransaction();

            // Eliminar los registros de la tabla pivot primero
            OrdencompraProducto::where('orden_compras_id', $id)->delete();

            // Luego eliminar la orden principal
            $orden->delete();

            DB::commit();

            return redirect()->route('ordenes_compra.create', [
                'requisicion_id' => $requisicion_id
            ])->with('success', 'Orden de compra eliminada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al eliminar la orden de compra: ' . $e->getMessage()]);
        }
    }
}
