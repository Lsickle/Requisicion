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
        return response()->json(['message' => 'Creación de centros deshabilitada en esta interfaz'], 403);
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
        return response()->json(['message' => 'Edición de centros deshabilitada en esta interfaz'], 403);
    }

    /**
     * Eliminar un centro (soft delete)
     */
    public function destroy(Centro $centro)
    {
        return response()->json(['message' => 'Eliminación de centros deshabilitada en esta interfaz'], 403);
    }

    /**
     * Restaurar un centro eliminado
     */
    public function restore($id)
    {
        return response()->json(['message' => 'Restauración de centros deshabilitada en esta interfaz'], 403);
    }
}
