<?php

namespace App\Http\Controllers\productos;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Nuevo_producto;

class ProductosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Producto::withTrashed()->with('proveedor')->orderBy('name_produc');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_produc', 'like', "%{$search}%")
                    ->orWhere('categoria_produc', 'like', "%{$search}%");
            });
        }

        $productos = $query->get();
        $proveedores = Proveedor::orderBy('prov_name')->get();

        // Agregamos las solicitudes de nuevos productos
        $solicitudes = Nuevo_producto::withTrashed()->orderBy('created_at', 'desc')->get();

        return view('productos.gestor', compact('productos', 'proveedores', 'solicitudes'));
    }

    /**
     * Método para el gestor de productos
     */
    public function gestor()
    {
        $productos = Producto::withTrashed()->with('proveedor')->orderBy('name_produc')->get();

        $productosSolicitados = DB::table('producto_requisicion')
            ->join('requisicion', 'producto_requisicion.id_requisicion', '=', 'requisicion.id')
            ->join('productos', 'producto_requisicion.id_producto', '=', 'productos.id')
            ->leftJoin('estatus_requisicion', function ($join) {
                $join->on('requisicion.id', '=', 'estatus_requisicion.requisicion_id')
                    ->where('estatus_requisicion.estatus', 1);
            })
            ->leftJoin('estatus', 'estatus_requisicion.estatus_id', '=', 'estatus.id')
            ->select(
                'producto_requisicion.*',
                'requisicion.prioridad_requisicion',
                'requisicion.created_at',
                'estatus.status_name'
            )
            ->orderBy('requisicion.created_at', 'desc')
            ->get();

        $proveedores = Proveedor::orderBy('prov_name')->get();

        // Agregamos las solicitudes de nuevos productos
        $solicitudes = Nuevo_producto::withTrashed()->orderBy('created_at', 'desc')->get();

        return view('productos.gestor', compact('productos', 'productosSolicitados', 'proveedores', 'solicitudes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name_produc' => 'required|string|max:255',
                'categoria_produc' => 'required|string|max:255',
                'proveedor_id' => 'required|exists:proveedores,id',
                'stock_produc' => 'required|integer|min:0',
                'price_produc' => 'required|numeric|min:0',
                'iva' => 'nullable|numeric|min:0',
                'unit_produc' => 'required|string|max:50',
                'description_produc' => 'required|string|max:1000', // Cambiado de 'text' a 'string'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Por favor, corrige los errores en el formulario.');
            }

            // Guardar solo campos permitidos
            Producto::create($request->only(['proveedor_id','categoria_produc','name_produc','stock_produc','description_produc','price_produc','unit_produc','iva']));

            // Si viene de una solicitud, eliminar la solicitud
            if ($request->has('solicitud_id') && $request->solicitud_id) {
                $solicitud = Nuevo_producto::find($request->solicitud_id);
                if ($solicitud) {
                    $solicitud->delete();
                }
            }

            return redirect()->route('productos.gestor')->with('success', 'Producto creado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al crear el producto: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Store a new provider
     */
public function storeProveedor(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'prov_name'   => 'required|string|max:255',
                'prov_nit'    => 'required|string|max:50|unique:proveedores,prov_nit',
                'prov_name_c' => 'required|string|max:255',
                'prov_phone'  => 'required|string|max:20',
                'prov_adress' => 'required|string|max:255',
                'prov_city'   => 'required|string|max:100',
                'prov_descrip' => 'required|string|max:1000', // Ahora es obligatorio
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            Proveedor::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado exitosamente.',
                'proveedores' => Proveedor::orderBy('prov_name')->get()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el proveedor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Producto $producto)
    {
        try {
            $validator = Validator::make($request->all(), [
                'proveedor_id' => 'required|exists:proveedores,id',
                'categoria_produc' => 'required|string|max:255',
                'name_produc' => 'required|string|max:255',
                'stock_produc' => 'required|integer|min:0',
                'description_produc' => 'required|string|max:1000', // Cambiado de 'text' a 'string'
                'price_produc' => 'required|numeric|min:0',
                'iva' => 'nullable|numeric|min:0',
                'unit_produc' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Por favor, corrige los errores en el formulario.');
            }

            $producto->update([
                 'proveedor_id' => $request->proveedor_id,
                 'categoria_produc' => $request->categoria_produc,
                 'name_produc' => $request->name_produc,
                 'stock_produc' => $request->stock_produc,
                 'description_produc' => $request->description_produc,
                 'price_produc' => $request->price_produc,
                'iva' => $request->iva ?? 0,
                 'unit_produc' => $request->unit_produc,
             ]);

            return redirect()->route('productos.gestor')->with('success', 'Producto actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al actualizar el producto: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Producto $producto)
    {
        try {
            $producto->delete();
            return redirect()->route('productos.gestor')->with('success', 'Producto eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al eliminar el producto: ' . $e->getMessage());
        }
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore($id)
    {
        try {
            $producto = Producto::withTrashed()->findOrFail($id);
            $producto->restore();

            return redirect()->route('productos.gestor')->with('success', 'Producto restaurado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al restaurar el producto: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete($id)
    {
        try {
            $producto = Producto::withTrashed()->findOrFail($id);
            $producto->forceDelete();

            return redirect()->route('productos.gestor')->with('success', 'Producto eliminado permanentemente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al eliminar permanentemente el producto: ' . $e->getMessage());
        }
    }

    /**
     * Obtener datos de una solicitud para añadir producto
     */
    public function getSolicitudData($id)
    {
        try {
            $solicitud = Nuevo_producto::findOrFail($id);
            return response()->json($solicitud);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Solicitud no encontrada'], 404);
        }
    }
}