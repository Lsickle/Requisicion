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
    public function index()
    {
        $productos = Producto::withTrashed()->with('proveedor')->orderBy('name_produc')->get();
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
            'categoria_produc' => 'required|string',
            'name_produc' => 'required|string|max:255',
            'stock_produc' => 'required|integer|min:0',
            'description_produc' => 'required|string',
            'price_produc' => 'required|numeric|min:0',
            'unit_produc' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Producto::create($request->all());

        return redirect()->route('productos.index')
            ->with('success', 'Producto creado exitosamente.');
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
            'categoria_produc' => 'required|string',
            'name_produc' => 'required|string|max:255',
            'stock_produc' => 'required|integer|min:0',
            'description_produc' => 'required|string',
            'price_produc' => 'required|numeric|min:0',
            'unit_produc' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $producto->update($request->all());

        return redirect()->route('productos.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Producto $producto)
    {
        $producto->delete();
        return redirect()->route('productos.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore($id)
    {
        $producto = Producto::withTrashed()->findOrFail($id);
        $producto->restore();
        
        return redirect()->route('productos.index')
            ->with('success', 'Producto restaurado exitosamente.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete($id)
    {
        $producto = Producto::withTrashed()->findOrFail($id);
        $producto->forceDelete();
        
        return redirect()->route('productos.index')
            ->with('success', 'Producto eliminado permanentemente.');
    }
}   