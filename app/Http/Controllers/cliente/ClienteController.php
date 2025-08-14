<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    /**
     * Listar todos los clientes (incluyendo eliminados)
     */
    public function index()
    {
        $clientes = Cliente::withTrashed()->orderBy('cli_name')->get();
        return response()->json($clientes);
    }

    /**
     * Mostrar formulario de creación (opcional)
     */
    public function create()
    {
        return response()->json(['message' => 'Formulario de creación de cliente']);
    }

    /**
     * Guardar un nuevo cliente
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cli_name'     => 'required|string|max:255',
            'cli_nit'      => 'required|string|max:20|unique:clientes,cli_nit',
            'cli_descrip'  => 'nullable|string',
            'cli_contacto' => 'required|string|max:255',
            'cli_telefono' => 'required|string|max:20',
            'cli_mail'     => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cliente = Cliente::create($request->all());

        return response()->json(['message' => 'Cliente creado exitosamente', 'cliente' => $cliente], 201);
    }

    /**
     * Mostrar un cliente específico
     */
    public function show(Cliente $cliente)
    {
        return response()->json($cliente);
    }

    /**
     * Mostrar formulario de edición (opcional)
     */
    public function edit(Cliente $cliente)
    {
        return response()->json(['cliente' => $cliente, 'message' => 'Formulario de edición']);
    }

    /**
     * Actualizar un cliente existente
     */
    public function update(Request $request, Cliente $cliente)
    {
        $validator = Validator::make($request->all(), [
            'cli_name'     => 'required|string|max:255',
            'cli_nit'      => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'cli_nit')->ignore($cliente->id),
            ],
            'cli_descrip'  => 'nullable|string',
            'cli_contacto' => 'required|string|max:255',
            'cli_telefono' => 'required|string|max:20',
            'cli_mail'     => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cliente->update($request->all());

        return response()->json(['message' => 'Cliente actualizado exitosamente', 'cliente' => $cliente]);
    }

    /**
     * Eliminar un cliente (soft delete)
     */
    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        return response()->json(['message' => 'Cliente eliminado correctamente']);
    }

    /**
     * Restaurar un cliente eliminado
     */
    public function restore($id)
    {
        $cliente = Cliente::withTrashed()->findOrFail($id);
        $cliente->restore();

        return response()->json(['message' => 'Cliente restaurado correctamente', 'cliente' => $cliente]);
    }

    /**
     * Eliminar permanentemente un cliente
     */
    public function forceDelete($id)
    {
        $cliente = Cliente::withTrashed()->findOrFail($id);
        $cliente->forceDelete();

        return response()->json(['message' => 'Cliente eliminado permanentemente']);
    }
}
