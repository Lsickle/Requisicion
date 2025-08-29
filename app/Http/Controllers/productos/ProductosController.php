<?php

namespace App\Http\Controllers\productos;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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

        return view('productos.gestor', compact('productos', 'proveedores'));
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

        return view('productos.gestor', compact('productos', 'productosSolicitados', 'proveedores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proveedor_id' => 'required|exists:proveedores,id',
            'categoria_produc' => 'required|string|max:255',
            'name_produc' => 'required|string|max:255',
            'stock_produc' => 'required|integer|min:0',
            'description_produc' => 'nullable|string',
            'price_produc' => 'required|numeric|min:0',
            'unit_produc' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Producto::create([
            'proveedor_id' => $request->proveedor_id,
            'categoria_produc' => $request->categoria_produc,
            'name_produc' => $request->name_produc,
            'stock_produc' => $request->stock_produc,
            'description_produc' => $request->description_produc,
            'price_produc' => $request->price_produc,
            'unit_produc' => $request->unit_produc,
        ]);

        return redirect()->route('productos.gestor')->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Producto $producto)
    {
        $validator = Validator::make($request->all(), [
            'proveedor_id' => 'required|exists:proveedores,id',
            'categoria_produc' => 'required|string|max:255',
            'name_produc' => 'required|string|max:255',
            'stock_produc' => 'required|integer|min:0',
            'description_produc' => 'nullable|string',
            'price_produc' => 'required|numeric|min:0',
            'unit_produc' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $producto->update([
            'proveedor_id' => $request->proveedor_id,
            'categoria_produc' => $request->categoria_produc,
            'name_produc' => $request->name_produc,
            'stock_produc' => $request->stock_produc,
            'description_produc' => $request->description_produc,
            'price_produc' => $request->price_produc,
            'unit_produc' => $request->unit_produc,
        ]);

        return redirect()->route('productos.gestor')->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Producto $producto)
    {
        $producto->delete();
        return redirect()->route('productos.gestor')->with('success', 'Producto eliminado exitosamente.');
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore($id)
    {
        $producto = Producto::withTrashed()->findOrFail($id);
        $producto->restore();

        return redirect()->route('productos.gestor')->with('success', 'Producto restaurado exitosamente.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete($id)
    {
        $producto = Producto::withTrashed()->findOrFail($id);
        $producto->forceDelete();

        return redirect()->route('productos.gestor')->with('success', 'Producto eliminado permanentemente.');
    }
}
