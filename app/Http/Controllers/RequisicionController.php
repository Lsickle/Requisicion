<?php

namespace App\Http\Controllers;

use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\Centro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class RequisicionController extends Controller
{
    public function index()
    {
        $requisiciones = Requisicion::with(['productos'])->latest()->get();
        return view('requisiciones.index', compact('requisiciones'));
    }

    public function show(Requisicion $requisicion)
    {
        $requisicion->load(['productos.centros']);
        return view('requisiciones.show', compact('requisicion'));
    }

    public function pdf(Requisicion $requisicion)
    {
        $requisicion->load([
            'productos' => function ($query) {
                $query->with(['centrosInventario' => function ($q) {
                    $q->select('centros.id', 'centros.name_centro')
                        ->withPivot('amount');
                }]);
            }
        ]);

        $requisicion->date_requisicion = \Carbon\Carbon::parse($requisicion->date_requisicion);

        Log::debug('Datos de requisicion:', [
            'productos' => $requisicion->productos->map(function ($producto) {
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->name_produc,
                    'centros' => $producto->centrosInventario->map(function ($centro) {
                        return [
                            'id' => $centro->id,
                            'nombre' => $centro->name_centro,
                            'amount' => $centro->pivot->amount ?? null
                        ];
                    })->toArray()
                ];
            })->toArray()
        ]);

        $pdf = PDF::loadView('requisiciones.pdf', [
            'requisicion' => $requisicion
        ]);

        return $pdf->download("requisicion-{$requisicion->id}.pdf");
    }

    public function edit(Requisicion $requisicion)
    {
        $requisicion->load(['productos.centros']);
        $categorias = Producto::distinct()->pluck('categoria_produc');
        $centros = Centro::all();

        return view('requisiciones.edit', compact(
            'requisicion',
            'categorias',
            'centros'
        ));
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prioridad_requisicion' => 'required|in:alta,media,baja',
            'Recobreble' => 'required|in:Recobrable,No recobrable',
            'detail_requisicion' => 'required|string|max:500',
            'justify_requisicion' => 'required|string|max:500',

            'productos' => 'required|array|min:1',
            'productos.*.id' => 'exists:productos,id',
            'productos.*.cantidad' => 'integer|min:1',

            'productos.*.centros' => 'array|min:1',
            'productos.*.centros.*.id' => 'exists:centros,id',
            'productos.*.centros.*.cantidad' => 'integer|min:1',
        ], [
            'prioridad_requisicion.required' => 'La prioridad es obligatoria.',
            'Recobreble.required' => 'Debe indicar si es recobrable o no.',
            'productos.required' => 'Debe agregar al menos un producto.',
            'productos.*.id.exists' => 'Uno de los productos seleccionados no existe.',
            'productos.*.cantidad.min' => 'La cantidad del producto debe ser al menos 1.',
            'productos.*.centros' => 'Debe asignar al menos un centro de costo.',
            'productos.*.centros.*.id.exists' => 'Uno de los centros seleccionados no existe.',
        ]);

        return DB::transaction(function () use ($validated) {
            // Crear requisición
            $requisicion = Requisicion::create([
                'prioridad_requisicion' => $validated['prioridad_requisicion'],
                'Recobreble' => $validated['Recobreble'],
                'detail_requisicion' => $validated['detail_requisicion'],
                'justify_requisicion' => $validated['justify_requisicion'],
                'date_requisicion' => now(),
            ]);

            // Guardar productos y centros
            foreach ($validated['productos'] as $productoData) {
                $producto = Producto::find($productoData['id']);

                if ($productoData['cantidad'] > $producto->stock_produc) {
                    throw ValidationException::withMessages([
                        "productos.{$productoData['id']}.cantidad" =>
                            "La cantidad supera el stock disponible para {$producto->name_produc}"
                    ]);
                }

                $productoRequisicionId = DB::table('producto_requisicion')->insertGetId([
                    'id_producto' => $producto->id,
                    'id_requisicion' => $requisicion->id,
                    'pr_amount' => $productoData['cantidad'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                foreach ($productoData['centros'] as $centroData) {
                    DB::table('centro_producto')->insert([
                        'producto_requisicion_id' => $productoRequisicionId,
                        'centro_id' => $centroData['id'],
                        'amount' => $centroData['cantidad'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            return redirect()->route('requisiciones.show', $requisicion)
                ->with('success', 'Requisición creada exitosamente');
        });
    }

    public function update(Request $request, Requisicion $requisicion)
    {
        $validated = $request->validate([
            'prioridad_requisicion' => 'required|in:alta,media,baja',
            'Recobreble' => 'required|in:Recobrable,No recobrable',
            'detail_requisicion' => 'required|string|max:500',
            'justify_requisicion' => 'required|string|max:500',

            'productos' => 'required|array|min:1',
            'productos.*.id' => 'exists:productos,id',
            'productos.*.cantidad' => 'integer|min:1',

            'productos.*.centros' => 'array|min:1',
            'productos.*.centros.*.id' => 'exists:centros,id',
            'productos.*.centros.*.cantidad' => 'integer|min:1',
        ], [
            'prioridad_requisicion.required' => 'La prioridad es obligatoria.',
            'Recobreble.required' => 'Debe indicar si es recobrable o no.',
            'productos.required' => 'Debe agregar al menos un producto.',
            'productos.*.id.exists' => 'Uno de los productos seleccionados no existe.',
            'productos.*.cantidad.min' => 'La cantidad del producto debe ser al menos 1.',
            'productos.*.centros' => 'Debe asignar al menos un centro de costo.',
            'productos.*.centros.*.id.exists' => 'Uno de los centros seleccionados no existe.',
        ]);

        return DB::transaction(function () use ($validated, $requisicion) {
            $requisicion->update([
                'prioridad_requisicion' => $validated['prioridad_requisicion'],
                'Recobreble' => $validated['Recobreble'],
                'detail_requisicion' => $validated['detail_requisicion'],
                'justify_requisicion' => $validated['justify_requisicion'],
            ]);

            DB::table('producto_requisicion')
                ->where('id_requisicion', $requisicion->id)
                ->delete();

            foreach ($validated['productos'] as $productoData) {
                $producto = Producto::find($productoData['id']);

                if ($productoData['cantidad'] > $producto->stock_produc) {
                    throw ValidationException::withMessages([
                        "productos.{$productoData['id']}.cantidad" =>
                            "La cantidad supera el stock disponible para {$producto->name_produc}"
                    ]);
                }

                $productoRequisicionId = DB::table('producto_requisicion')->insertGetId([
                    'id_producto' => $producto->id,
                    'id_requisicion' => $requisicion->id,
                    'pr_amount' => $productoData['cantidad'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                foreach ($productoData['centros'] as $centroData) {
                    DB::table('centro_producto')->insert([
                        'producto_requisicion_id' => $productoRequisicionId,
                        'centro_id' => $centroData['id'],
                        'amount' => $centroData['cantidad'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            return redirect()->route('requisiciones.show', $requisicion)
                ->with('success', 'Requisición actualizada exitosamente');
        });
    }

    public function destroy(Requisicion $requisicion)
    {
        $requisicion->delete();
        return redirect()->route('requisiciones.index')
            ->with('success', 'Requisición eliminada');
    }
}