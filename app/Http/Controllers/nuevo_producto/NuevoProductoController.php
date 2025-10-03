<?php

namespace App\Http\Controllers\nuevo_producto;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Nuevo_producto;
use Illuminate\Support\Facades\DB;
use App\Jobs\NuevoProductoSolicitadoJob;

class NuevoProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $productos = Nuevo_producto::withTrashed()->orderBy('nombre')->get();
        return view('requisiciones.menu', compact('productos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('productos.nuevoproducto');
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

        DB::beginTransaction();

        try {
            // Extraer usuario de sesión
            $nameUser  = session('user.name') ?? 'Desconocido';
            $emailUser = session('user.email') ?? 'no-email@dominio.com';

            // Crear registro incluyendo nombre y email del usuario
            $nuevoProducto = Nuevo_producto::create([
                'nombre'      => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
                'name_user'   => $nameUser,
                'email_user'  => $emailUser,
            ]);

            // Despachar el Job para enviar el correo
            NuevoProductoSolicitadoJob::dispatch($nuevoProducto);

            DB::commit();

            return redirect()->route('productos.nuevoproducto')
                ->with('success', 'Solicitud de producto creada exitosamente. Se ha enviado una notificación.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors([
                'error' => 'Error al crear la solicitud: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Nuevo_producto $nuevoProducto)
    {
        return view('nuevo_producto.show', compact('nuevoProducto'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Nuevo_producto $nuevoProducto)
    {
        return view('nuevo_producto.edit', compact('nuevoProducto'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Nuevo_producto $nuevoProducto)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
        ]);

        $nuevoProducto->update($validated);

        return redirect()->route('productos.gestor')
            ->with('success', 'Solicitud de producto actualizada exitosamente.');
    }

    public function destroy(Nuevo_producto $nuevoProducto)
    {
        $nuevoProducto->delete();
        return redirect()->route('productos.gestor')
            ->with('success', 'Solicitud de producto eliminada exitosamente.');
    }

    public function restore($id)
    {
        $producto = Nuevo_producto::withTrashed()->findOrFail($id);
        $producto->restore();

        return redirect()->route('productos.gestor')
            ->with('success', 'Solicitud de producto restaurada exitosamente.');
    }

    /**
     * Eliminar permanentemente un producto.
     */
    public function forceDelete($id)
    {
        $producto = Nuevo_producto::withTrashed()->findOrFail($id);
        $producto->forceDelete();

        return redirect()->route('productos.gestor')
            ->with('success', 'Solicitud de producto eliminada permanentemente.');
    }
}
