<?php

namespace App\Http\Controllers\CentrosCostos;

use App\Http\Controllers\Controller;
use App\Models\Centro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CentroController extends Controller
{
    /**
     * Listar todos los centros (incluye eliminados)
     */
    public function index()
    {
        $centros = Centro::withTrashed()->orderBy('name_centro')->get();
        return response()->json($centros);
    }

    /**
     * Mostrar formulario de creación (solo para vistas, opcional en API)
     */
    public function create()
    {
        return response()->json(['message' => 'Formulario de creación de centro']);
    }

    /**
     * Guardar un nuevo centro
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_centro' => 'required|string|max:255|unique:centros,name_centro',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $centro = Centro::create($request->all());

        return response()->json(['message' => 'Centro creado exitosamente', 'centro' => $centro], 201);
    }

    /**
     * Mostrar un centro específico
     */
    public function show(Centro $centro)
    {
        return response()->json($centro);
    }

    /**
     * Mostrar formulario de edición (solo para vistas, opcional en API)
     */
    public function edit(Centro $centro)
    {
        return response()->json(['centro' => $centro, 'message' => 'Formulario de edición']);
    }

    /**
     * Actualizar un centro existente
     */
    public function update(Request $request, Centro $centro)
    {
        $validator = Validator::make($request->all(), [
            'name_centro' => [
                'required',
                'string',
                'max:255',
                Rule::unique('centros', 'name_centro')->ignore($centro->id),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $centro->update($request->all());

        return response()->json(['message' => 'Centro actualizado exitosamente', 'centro' => $centro]);
    }

    /**
     * Eliminar un centro (soft delete)
     */
    public function destroy(Centro $centro)
    {
        $centro->delete();
        return response()->json(['message' => 'Centro eliminado correctamente']);
    }

    /**
     * Restaurar un centro eliminado
     */
    public function restore($id)
    {
        $centro = Centro::withTrashed()->findOrFail($id);
        $centro->restore();

        return response()->json(['message' => 'Centro restaurado correctamente', 'centro' => $centro]);
    }
}
