<?php

namespace App\Http\Controllers\centros;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Centro;
use App\Models\Subcentro;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para la administración de Centros de costo y Subcentros.
 *
 * Solo se agregan comentarios para documentar el propósito de cada acción,
 * sin modificar el comportamiento existente.
 */
class CentroController extends Controller
{
    /**
     * Muestra el gestor de Centros con sus subcentros asociados.
     * Obtiene todos los centros ordenados por nombre y carga la relación `subcentros`.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $centros = Centro::with('subcentros')->orderBy('name_centro')->get();
        return view('centros.gestor', compact('centros'));
    }

    /**
     * Crea un nuevo Centro.
     * Valida el nombre y registra la entidad; en caso de error, retorna con mensaje.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name_centro' => 'required|string|max:255',
        ]);

        try {
            // Inserta el centro con el nombre suministrado
            $centro = Centro::create([ 'name_centro' => $data['name_centro'] ]);
            return redirect()->route('centros.index')->with('success', 'Centro creado correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error creando centro: '.$e->getMessage());
            return redirect()->back()->with('error', 'Error creando centro: '.$e->getMessage());
        }
    }

    /**
     * Actualiza el nombre de un Centro existente.
     * Busca por ID y persiste el nuevo nombre.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name_centro' => 'required|string|max:255',
        ]);
        try {
            $centro = Centro::findOrFail($id);
            $centro->name_centro = $data['name_centro'];
            $centro->save();
            return redirect()->route('centros.index')->with('success', 'Centro actualizado.');
        } catch (\Throwable $e) {
            Log::error('Error actualizando centro: '.$e->getMessage());
            return redirect()->back()->with('error', 'Error actualizando centro: '.$e->getMessage());
        }
    }

    /**
     * Elimina (soft delete si el modelo lo soporta) un Centro por ID.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            $centro = Centro::findOrFail($id);
            $centro->delete();
            return redirect()->route('centros.index')->with('success', 'Centro eliminado.');
        } catch (\Throwable $e) {
            Log::error('Error eliminando centro: '.$e->getMessage());
            return redirect()->back()->with('error', 'Error eliminando centro: '.$e->getMessage());
        }
    }

    // Subcentros

    /**
     * Crea un Subcentro dentro de un Centro dado.
     * Permite elegir un nombre nuevo o clonar el nombre desde un `existing_id` (incluye soft-deleted).
     * Previene duplicados exactos dentro del mismo centro.
     *
     * @param Request $request
     * @param int $centroId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeSubcentro(Request $request, $centroId)
    {
        $data = $request->validate([
            'name_subcentro' => 'nullable|string|max:255',
            'existing_id' => 'nullable|integer',
        ]);

        try {
            $centro = Centro::findOrFail($centroId);

            // Determina el nombre: si llega existing_id, se toma el nombre de ese registro (aunque esté borrado lógicamente)
            $name = null;
            if (!empty($data['existing_id'])) {
                $existing = Subcentro::withTrashed()->find($data['existing_id']);
                if (!$existing) return redirect()->back()->with('error', 'Subcentro seleccionado no encontrado.');
                $name = $existing->name_subcentro;
            } elseif (!empty($data['name_subcentro'])) {
                $name = $data['name_subcentro'];
            }

            if (empty($name)) return redirect()->back()->with('error', 'Nombre de subcentro requerido.');

            // Previene duplicados exactos dentro del mismo centro
            $exists = Subcentro::where('name_subcentro', $name)->where('centro_id', $centro->id)->exists();
            if ($exists) {
                return redirect()->route('centros.index')->with('info', 'El subcentro ya existe en este centro.');
            }

            $sub = Subcentro::create([ 'name_subcentro' => $name, 'centro_id' => $centro->id ]);
            return redirect()->route('centros.index')->with('success', 'Subcentro creado.');
        } catch (\Throwable $e) {
            Log::error('Error creando subcentro: '.$e->getMessage());
            return redirect()->back()->with('error', 'Error creando subcentro: '.$e->getMessage());
        }
    }

    /**
     * Actualiza el nombre de un Subcentro por ID.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSubcentro(Request $request, $id)
    {
        $data = $request->validate([
            'name_subcentro' => 'required|string|max:255',
        ]);
        try {
            $sub = Subcentro::findOrFail($id);
            $sub->name_subcentro = $data['name_subcentro'];
            $sub->save();
            return redirect()->route('centros.index')->with('success', 'Subcentro actualizado.');
        } catch (\Throwable $e) {
            Log::error('Error actualizando subcentro: '.$e->getMessage());
            return redirect()->back()->with('error', 'Error actualizando subcentro: '.$e->getMessage());
        }
    }

    /**
     * Elimina (soft delete si aplica) un Subcentro por ID.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySubcentro($id)
    {
        try {
            $sub = Subcentro::findOrFail($id);
            $sub->delete();
            return redirect()->route('centros.index')->with('success', 'Subcentro eliminado.');
        } catch (\Throwable $e) {
            Log::error('Error eliminando subcentro: '.$e->getMessage());
            return redirect()->back()->with('error', 'Error eliminando subcentro: '.$e->getMessage());
        }
    }
}
