<?php

namespace App\Http\Controllers\centros;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Centro;
use App\Models\Subcentro;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CentroController extends Controller
{
    public function index()
    {
        $centros = Centro::with('subcentros')->orderBy('name_centro')->get();
        return view('centros.gestor', compact('centros'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_centro' => 'required|string|max:255',
        ]);

        try {
            $centro = Centro::create([ 'name_centro' => $data['name_centro'] ]);
            return redirect()->route('centros.index')->with('success', 'Centro creado correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error creando centro: '.$e->getMessage());
            return redirect()->back()->with('error', 'Error creando centro: '.$e->getMessage());
        }
    }

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
    public function storeSubcentro(Request $request, $centroId)
    {
        $data = $request->validate([
            'name_subcentro' => 'required|string|max:255',
        ]);
        try {
            $centro = Centro::findOrFail($centroId);
            $sub = Subcentro::create([ 'name_subcentro' => $data['name_subcentro'], 'centro_id' => $centro->id ]);
            return redirect()->route('centros.index')->with('success', 'Subcentro creado.');
        } catch (\Throwable $e) {
            Log::error('Error creando subcentro: '.$e->getMessage());
            return redirect()->back()->with('error', 'Error creando subcentro: '.$e->getMessage());
        }
    }

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
