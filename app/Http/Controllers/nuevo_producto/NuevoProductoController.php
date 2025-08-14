<?php

namespace App\Http\Controllers\nuevo_producto;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Nuevo_Producto;
use Illuminate\Validation\ValidationException;

class NuevoProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $productos = Nuevo_Producto::withTrashed()->orderBy('nombre')->get();
        return view('nuevo_producto.index', compact('productos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('nuevo_producto.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
        ]);

        Nuevo_Producto::create($validated);

        return redirect()->route('nuevo-producto.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Nuevo_Producto $nuevoProducto)
    {
        return view('nuevo_producto.show', compact('nuevoProducto'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Nuevo_Producto $nuevoProducto)
    {
        return view('nuevo_producto.edit', compact('nuevoProducto'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Nuevo_Producto $nuevoProducto)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
        ]);

        $nuevoProducto->update($validated);

        return redirect()->route('nuevo-producto.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Nuevo_Producto $nuevoProducto)
    {
        $nuevoProducto->delete();
        return redirect()->route('nuevo-producto.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }

    public function restore($id)
    {
        $producto = Nuevo_Producto::withTrashed()->findOrFail($id);
        $producto->restore();

        return redirect()->route('nuevo-producto.index')
            ->with('success', 'Producto restaurado exitosamente.');
    }

    /**
     * borrar permanente del producto.
     */
    public function forceDelete($id)
    {
        $producto = Nuevo_Producto::withTrashed()->findOrFail($id);
        $producto->forceDelete();

        return redirect()->route('nuevo-producto.index')
            ->with('success', 'Producto eliminado permanentemente.');
    }
}
