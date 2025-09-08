<?php

namespace App\Http\Controllers\ordencompra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Requisicion;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use Carbon\Carbon;

class OrdenCompraController extends Controller
{
    public function index()
    {
        $ordenes = OrdenCompra::with(['proveedor', 'requisicion'])
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
            $requisicion = Requisicion::with(['productos.proveedor', 'ordenesCompra'])->find($request->requisicion_id);

            // Solo proveedores de los productos de la requisición
            if ($requisicion) {
                // Obtener todos los proveedores de los productos
                $todosProveedores = $requisicion->productos->pluck('proveedor')->unique('id');
                
                // Obtener proveedores que ya tienen orden
                $proveedoresConOrden = $requisicion->ordenesCompra->pluck('proveedor_id');
                
                // Filtrar proveedores disponibles (sin orden)
                $proveedoresDisponibles = $todosProveedores->whereNotIn('id', $proveedoresConOrden);
                
                // Si hay un proveedor seleccionado en la solicitud
                if ($request->has('proveedor_id') && $request->proveedor_id != 0) {
                    $proveedorSeleccionado = Proveedor::find($request->proveedor_id);
                }
            }
        }

        return view('ordenes_compra.create', compact(
            'requisicion',
            'orderNumber',
            'proveedoresDisponibles',
            'proveedoresConOrden',
            'proveedorSeleccionado'
        ));
    }

    /**
     * Guardar una orden de compra para un proveedor.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_oc'       => 'required|date',
            'proveedor_id'  => 'required|exists:proveedores,id',
            'methods_oc'    => 'nullable|string|max:255',
            'plazo_oc'      => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'requisicion_id' => 'nullable|exists:requisicion,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verificar si ya existe una orden para este proveedor y requisición
        $ordenExistente = OrdenCompra::where('proveedor_id', $request->proveedor_id)
            ->where('requisicion_id', $request->requisicion_id)
            ->first();

        if ($ordenExistente) {
            return back()->withInput()->withErrors(['error' => 'Ya existe una orden de compra para este proveedor en esta requisición.']);
        }

        // Generar número de orden único
        $ultimo = OrdenCompra::orderByDesc('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        $nuevoOrderOc = 'OC-' . str_pad($numero, 6, '0', STR_PAD_LEFT);

        try {
            OrdenCompra::create([
                'date_oc'        => $request->date_oc,
                'proveedor_id'   => $request->proveedor_id,
                'methods_oc'     => $request->methods_oc,
                'plazo_oc'       => $request->plazo_oc,
                'observaciones'  => $request->observaciones,
                'requisicion_id' => $request->requisicion_id != 0 ? $request->requisicion_id : null,
                'order_oc'       => $nuevoOrderOc,
            ]);

            // Obtener el siguiente proveedor disponible
            $requisicion = Requisicion::with(['productos.proveedor', 'ordenesCompra'])->find($request->requisicion_id);
            $todosProveedores = $requisicion->productos->pluck('proveedor')->unique('id');
            $proveedoresConOrden = $requisicion->ordenesCompra->pluck('proveedor_id');
            $proveedoresDisponibles = $todosProveedores->whereNotIn('id', $proveedoresConOrden);
            
            // Si hay más proveedores disponibles, redirigir al primero
            if ($proveedoresDisponibles->count() > 0) {
                $siguienteProveedor = $proveedoresDisponibles->first();
                return redirect()->route('ordenes_compra.create', [
                    'requisicion_id' => $request->requisicion_id,
                    'proveedor_id' => $siguienteProveedor->id
                ])->with('success', 'Orden de compra creada correctamente. Continúa con el siguiente proveedor.');
            } else {
                // Todos los proveedores tienen orden
                return redirect()->route('ordenes_compra.create', [
                    'requisicion_id' => $request->requisicion_id
                ])->with('success', '¡Todas las órdenes de compra han sido creadas!');
            }
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Error al crear la orden de compra: ' . $e->getMessage()]);
        }
    }

    public function show(string $id)
    {
        $orden = OrdenCompra::with(['proveedor', 'requisicion'])->findOrFail($id);
        return view('ordenes_compra.show', compact('orden'));
    }

    public function edit(string $id)
    {
        $orden = OrdenCompra::with(['proveedor', 'requisicion'])->findOrFail($id);
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
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $orden->update($request->only([
            'date_oc',
            'proveedor_id',
            'methods_oc',
            'plazo_oc',
            'observaciones'
        ]));

        // Redirigir de vuelta a la creación con la requisición correspondiente
        return redirect()->route('ordenes_compra.create', [
            'requisicion_id' => $orden->requisicion_id
        ])->with('success', 'Orden de compra actualizada correctamente.');
    }

    public function destroy(string $id)
    {
        $orden = OrdenCompra::findOrFail($id);
        $requisicion_id = $orden->requisicion_id;
        $orden->delete();

        // Redirigir de vuelta a la creación con la requisición correspondiente
        return redirect()->route('ordenes_compra.create', [
            'requisicion_id' => $requisicion_id
        ])->with('success', 'Orden de compra eliminada correctamente.');
    }
}