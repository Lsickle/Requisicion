<?php

namespace App\Http\Controllers\centros;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Centro;
use App\Models\Subcentro;
use Illuminate\Support\Facades\DB;

class BodegaController extends Controller
{
    public function index()
    {
        $centros = Centro::with(['subcentros' => function($q) {
            $q->whereNull('deleted_at')->orderBy('name_subcentro');
        }])->whereNull('deleted_at')->orderBy('name_centro')->get();

        return view('centros.bodegas', compact('centros'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_centro' => 'required|string|max:255',
        ]);

        if ($request->filled('id')) {
            $bodega = Centro::findOrFail($request->id);
            $bodega->update(['name_centro' => $data['name_centro']]);
            return redirect()->back()->with('success', 'Bodega actualizada');
        }

        Centro::create([
            'name_centro' => $data['name_centro'],
        ]);

        return redirect()->back()->with('success', 'Bodega creada correctamente');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name_centro' => 'required|string|max:255',
        ]);

        $bodega = Centro::findOrFail($id);
        $bodega->update(['name_centro' => $data['name_centro']]);

        return redirect()->back()->with('success', 'Bodega actualizada');
    }

    public function destroy($id)
    {
        $bodega = Centro::findOrFail($id);
        $bodega->delete();

        return redirect()->back()->with('success', 'Bodega eliminada');
    }

    public function getSubcentros(Request $request)
    {
        $bodegaId = $request->get('bodega_id');
        $subcentros = Subcentro::where('centro_id', $bodegaId)
            ->whereNull('deleted_at')
            ->orderBy('name_subcentro')
            ->get(['id', 'name_subcentro']);

        return response()->json($subcentros);
    }

    public function storeSubcentro(Request $request)
    {
        $data = $request->validate([
            'bodega_id' => 'required|integer|exists:centro,id',
            'name_subcentro' => 'required|string|max:255',
        ]);

        Subcentro::create([
            'centro_id' => $data['bodega_id'],
            'name_subcentro' => $data['name_subcentro'],
        ]);

        return redirect()->back()->with('success', 'Operación creada');
    }

    public function updateSubcentro(Request $request, $id)
    {
        $data = $request->validate([
            'name_subcentro' => 'required|string|max:255',
        ]);

        $subcentro = Subcentro::findOrFail($id);
        $subcentro->update(['name_subcentro' => $data['name_subcentro']]);

        return redirect()->back()->with('success', 'Operación actualizada');
    }

    public function destroySubcentro($id)
    {
        $subcentro = Subcentro::findOrFail($id);
        $subcentro->delete();

        return redirect()->back()->with('success', 'Operación eliminada');
    }

    public function asignarUsuario(Request $request)
    {
        $data = $request->validate([
            'email_user' => 'required|email',
            'subcentro_ids' => 'array',
            'subcentro_ids.*' => 'integer|exists:subcentros,id',
            'force' => 'nullable|boolean',
        ]);

        $email = $data['email_user'];
        $ids = $data['subcentro_ids'] ?? [];
        $force = !empty($data['force']);

        // Validar bodega única
        if (!empty($ids)) {
            $subcentrosData = Subcentro::with('centro')->whereIn('id', $ids)->get();
            $bodegasIds = $subcentrosData->pluck('centro_id')->unique();

            if ($bodegasIds->count() > 1) {
                return redirect()->back()
                    ->with('error', 'No se pueden asignar operaciones de diferentes bodegas')
                    ->withInput();
            }

            $bodegaNueva = $bodegasIds->first();
            $bodegaActual = $this->obtenerBodegaActual($email);
            $isAdmin = in_array('admin', array_map(fn($r) => mb_strtolower($r), session('user_roles', [])));

            if ($bodegaActual && $bodegaActual != $bodegaNueva && !$force) {
                $bodega = Centro::find($bodegaActual);
                return redirect()->back()
                    ->with('error', "El usuario ya tiene operaciones en: {$bodega->name_centro}. Solo un administrador puede cambiar de bodega.")
                    ->withInput();
            }
        }

        // Eliminar asignaciones previas
        DB::table('userxsubcentro')->where('email_user', $email)->delete();

        // Crear nuevas
        foreach ($ids as $sid) {
            DB::table('userxsubcentro')->insert([
                'email_user' => $email,
                'subcentro_id' => $sid,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Usuario asignado a las operaciones');
    }

    public function getUsuariosBodega(Request $request)
    {
        $bodegaId = $request->get('bodega_id');
        $subcentrosIds = Subcentro::where('centro_id', $bodegaId)
            ->whereNull('deleted_at')
            ->pluck('id');

        $usuarios = DB::table('userxsubcentro')
            ->whereNull('deleted_at')
            ->whereIn('subcentro_id', $subcentrosIds)
            ->join('subcentros', 'userxsubcentro.subcentro_id', '=', 'subcentros.id')
            ->select('userxsubcentro.email_user')
            ->distinct()
            ->get();

        return response()->json($usuarios->pluck('email_user'));
    }

    private function obtenerBodegaActual(string $email): ?int
    {
        $userSubcentro = DB::table('userxsubcentro')
            ->where('email_user', $email)
            ->whereNull('deleted_at')
            ->join('subcentros', 'userxsubcentro.subcentro_id', '=', 'subcentros.id')
            ->first();

        return $userSubcentro->centro_id ?? null;
    }
}