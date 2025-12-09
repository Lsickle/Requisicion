<?php

namespace App\Http\Controllers\requisicion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Centro;
use Illuminate\Support\Facades\Schema;

class AprobadoresController extends Controller
{
    public function index()
    {
        $centros = Centro::orderBy('name_centro')->get();
        return view('requisiciones.gestor_aprobadores', compact('centros'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'centro_id' => 'required|integer|exists:centro,id',
            'approver_1' => 'nullable|integer',
            'approver_2' => 'nullable|integer'
        ]);

        try {
            $update = [];
            if ($request->has('approver_1')) $update['approver_1_id'] = $data['approver_1'] ?: null;
            if ($request->has('approver_2')) $update['approver_2_id'] = $data['approver_2'] ?: null;

            if (empty($update)) {
                return response()->json(['success' => true, 'message' => 'Nada que actualizar.']);
            }

            // Intentar actualizar modelo Centro
            $centro = Centro::findOrFail($data['centro_id']);
            // Asignar solo si las columnas existen
            $cols = Schema::getColumnListing($centro->getTable());
            if (!in_array('approver_1_id', $cols) || !in_array('approver_2_id', $cols)) {
                // intentar actualizar directamente en DB (es posible que la migración no se haya ejecutado)
                DB::table($centro->getTable())->where('id', $centro->id)->update($update);
            } else {
                $centro->fill($update);
                $centro->save();
            }

            return response()->json(['success' => true, 'message' => 'Aprobadores guardados correctamente.']);
        } catch (\Throwable $e) {
            Log::error('AprobadoresController@store error: ' . $e->getMessage(), ['input' => $data]);
            return response()->json(['success' => false, 'message' => 'No se pudo guardar la asignación (ver logs).'], 500);
        }
    }
}
