<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Agregamos búsqueda por nombre de producto opcional
        $query = Producto::withTrashed()->with('proveedor')->orderBy('name_produc');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name_produc', 'like', "%{$search}%")
                  ->orWhere('categoria_produc', 'like', "%{$search}%");
        }

        $productos = $query->get();

        return view('productos.index', compact('productos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $proveedores = Proveedor::orderBy('prov_name')->get();
        return view('productos.create', compact('proveedores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_proveedores' => 'required|exists:proveedores,id',
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
            'id_proveedores' => $request->id_proveedores,
            'categoria_produc' => $request->categoria_produc,
            'name_produc' => $request->name_produc,
            'stock_produc' => $request->stock_produc,
            'description_produc' => $request->description_produc,
            'price_produc' => $request->price_produc,
            'unit_produc' => $request->unit_produc,
        ]);

        return redirect()->route('productos.index')->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Producto $producto)
    {
        return view('productos.show', compact('producto'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $producto)
    {
        $proveedores = Proveedor::orderBy('prov_name')->get();
        return view('productos.edit', compact('producto', 'proveedores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Producto $producto)
    {
        $validator = Validator::make($request->all(), [
            'id_proveedores' => 'required|exists:proveedores,id',
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
            'id_proveedores' => $request->id_proveedores,
            'categoria_produc' => $request->categoria_produc,
            'name_produc' => $request->name_produc,
            'stock_produc' => $request->stock_produc,
            'description_produc' => $request->description_produc,
            'price_produc' => $request->price_produc,
            'unit_produc' => $request->unit_produc,
        ]);

        return redirect()->route('productos.index')->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Producto $producto)
    {
        $producto->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado exitosamente.');
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore($id)
    {
        $producto = Producto::withTrashed()->findOrFail($id);
        $producto->restore();
        
        return redirect()->route('productos.index')->with('success', 'Producto restaurado exitosamente.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete($id)
    {
        $producto = Producto::withTrashed()->findOrFail($id);
        $producto->forceDelete();
        
        return redirect()->route('productos.index')->with('success', 'Producto eliminado permanentemente.');
    }
}
