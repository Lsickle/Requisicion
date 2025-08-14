<?php

namespace App\Http\Controllers\proveedores;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProveedoresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $proveedores = Proveedor::withTrashed()->orderBy('prov_name')->get();
        return view('proveedores.index', compact('proveedores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('proveedores.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prov_name' => 'required|string|max:255',
            'prov_descrip' => 'required|string',
            'prov_nit' => 'required|string|max:255|unique:proveedores,prov_nit',
            'prov_name_c' => 'required|string|max:255',
            'prov_phone' => 'required|string|max:255',
            'prov_adress' => 'required|string|max:255',
            'prov_city' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Proveedor::create($request->all());

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Proveedor $proveedore)
    {
        return view('proveedores.show', compact('proveedore'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Proveedor $proveedore)
    {
        return view('proveedores.edit', compact('proveedore'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Proveedor $proveedore)
    {
        $validator = Validator::make($request->all(), [
            'prov_name' => 'required|string|max:255',
            'prov_descrip' => 'required|string',
            'prov_nit' => 'required|string|max:255|unique:proveedores,prov_nit,' . $proveedore->id,
            'prov_name_c' => 'required|string|max:255',
            'prov_phone' => 'required|string|max:255',
            'prov_adress' => 'required|string|max:255',
            'prov_city' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $proveedore->update($request->all());

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proveedor $proveedore)
    {
        $proveedore->delete();
        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor eliminado exitosamente.');
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore($id)
    {
        $proveedor = Proveedor::withTrashed()->findOrFail($id);
        $proveedor->restore();
        
        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor restaurado exitosamente.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete($id)
    {
        $proveedor = Proveedor::withTrashed()->findOrFail($id);
        $proveedor->forceDelete();
        
        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor eliminado permanentemente.');
    }
}