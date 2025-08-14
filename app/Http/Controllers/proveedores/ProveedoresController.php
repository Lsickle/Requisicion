<?php

namespace App\Http\Controllers\Proveedores;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProveedoresController extends Controller
{
    /**
     * Listar todos los proveedores (incluye eliminados).
     */
    public function index()
    {
        $proveedores = Proveedor::withTrashed()->orderBy('prov_name')->get();
        return response()->json($proveedores);
    }

    /**
     * Mostrar formulario de creación (opcional).
     */
    public function create()
    {
        return response()->json(['message' => 'Formulario de creación de proveedor']);
    }

    /**
     * Guardar un nuevo proveedor.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prov_name'    => 'required|string|max:255',
            'prov_descrip' => 'required|string',
            'prov_nit'     => 'required|string|max:255|unique:proveedores,prov_nit',
            'prov_name_c'  => 'required|string|max:255',
            'prov_phone'   => 'required|string|max:255',
            'prov_adress'  => 'required|string|max:255',
            'prov_city'    => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $proveedor = Proveedor::create($request->all());

        return response()->json(['message' => 'Proveedor creado exitosamente', 'proveedor' => $proveedor], 201);
    }

    /**
     * Mostrar un proveedor específico.
     */
    public function show(Proveedor $proveedor)
    {
        return response()->json($proveedor);
    }

    /**
     * Mostrar formulario de edición (opcional).
     */
    public function edit(Proveedor $proveedor)
    {
        return response()->json(['proveedor' => $proveedor, 'message' => 'Formulario de edición']);
    }

    /**
     * Actualizar un proveedor existente.
     */
    public function update(Request $request, Proveedor $proveedor)
    {
        $validator = Validator::make($request->all(), [
            'prov_name'    => 'required|string|max:255',
            'prov_descrip' => 'required|string',
            'prov_nit'     => 'required|string|max:255|unique:proveedores,prov_nit,' . $proveedor->id,
            'prov_name_c'  => 'required|string|max:255',
            'prov_phone'   => 'required|string|max:255',
            'prov_adress'  => 'required|string|max:255',
            'prov_city'    => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $proveedor->update($request->all());

        return response()->json(['message' => 'Proveedor actualizado exitosamente', 'proveedor' => $proveedor]);
    }

    /**
     * Eliminar un proveedor (soft delete).
     */
    public function destroy(Proveedor $proveedor)
    {
        $proveedor->delete();
        return response()->json(['message' => 'Proveedor eliminado correctamente']);
    }

    /**
     * Restaurar un proveedor eliminado.
     */
    public function restore($id)
    {
        $proveedor = Proveedor::withTrashed()->findOrFail($id);
        $proveedor->restore();

        return response()->json(['message' => 'Proveedor restaurado correctamente', 'proveedor' => $proveedor]);
    }

    /**
     * Eliminar permanentemente un proveedor.
     */
    public function forceDelete($id)
    {
        $proveedor = Proveedor::withTrashed()->findOrFail($id);
        $proveedor->forceDelete();

        return response()->json(['message' => 'Proveedor eliminado permanentemente']);
    }
}
