<?php

namespace App\Http\Controllers;

use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\Centro;
use App\Models\Estatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class RequisicionController extends Controller
{
    public function index()
    {
        $requisiciones = Requisicion::with([
            'productos',
            'centros',
            'estatusRequisicion.estatus'
        ])->latest()->get();

        return view('requisiciones.index', compact('requisiciones'));
    }

    public function create()
    {
        $categorias = Producto::distinct()->pluck('categoria_produc');
        $centros = Centro::all();
        return view('requisiciones.create', compact('categorias', 'centros'));
    }

    public function getProductosByCategoria(Request $request)
    {
        $productos = Producto::where('categoria_produc', $request->categoria)
                        ->select('id', 'name_produc', 'stock_produc')
                        ->get();
        return response()->json($productos);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prioridad_requisicion' => 'required|in:alta,media,baja',
            'Recobreble' => 'required|in:Recobrable,No recobrable',
            'detail_requisicion' => 'required|string|max:500',
            'justify_requisicion' => 'required|string|max:500',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.centros' => 'required|array|min:1',
            'productos.*.centros.*.id' => 'required|exists:centro,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1'
        ]);

        // Agregar la fecha actual automáticamente
        $validated['date_requisicion'] = Carbon::now();

        return DB::transaction(function () use ($validated) {
            $requisicion = Requisicion::create($validated);

            foreach ($validated['productos'] as $productoData) {
                $producto = Producto::find($productoData['id']);

                // Validar stock
                if ($productoData['cantidad'] > $producto->stock_produc) {
                    throw ValidationException::withMessages([
                        'productos.'.$productoData['id'].'.cantidad' => 
                        "La cantidad supera el stock disponible para {$producto->name_produc}"
                    ]);
                }

                // Adjuntar producto con cantidad
                $requisicion->productos()->attach($producto->id, [
                    'pr_amount' => $productoData['cantidad']
                ]);

                // Adjuntar centros de costos
                foreach ($productoData['centros'] as $centroData) {
                    DB::table('centro_producto')->insert([
                        'producto_requisicion_id' => DB::getPdo()->lastInsertId(),
                        'centro_id' => $centroData['id'],
                        'rc_amount' => $centroData['cantidad'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // Asignar primer estatus
            $requisicion->estatusRequisicion()->create([
                'estatus_id' => Estatus::where('status_name', 'Iniciada')->first()->id,
                'estatus' => 1,
                'date_update' => now()
            ]);

            return redirect()->route('requisiciones.show', $requisicion)
                ->with('success', 'Requisición creada exitosamente');
        });
    }

    public function show(Requisicion $requisicion)
    {
        $requisicion->load([
            'productos',
            'estatusRequisicion.estatus',
            'productoRequisicion.centroProducto.centro'
        ]);

        return view('requisiciones.show', compact('requisicion'));
    }

    public function edit(Requisicion $requisicion)
    {
        $requisicion->load(['productos', 'productoRequisicion.centroProducto']);
        $categorias = Producto::distinct()->pluck('categoria_produc');
        $centros = Centro::all();

        return view('requisiciones.edit', compact(
            'requisicion', 
            'categorias', 
            'centros'
        ));
    }

    public function update(Request $request, Requisicion $requisicion)
    {
        $validated = $request->validate([
            'prioridad_requisicion' => 'required|in:alta,media,baja',
            'Recobreble' => 'required|in:Recobrable,No recobrable',
            'detail_requisicion' => 'required|string|max:500',
            'justify_requisicion' => 'required|string|max:500',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.centros' => 'required|array|min:1',
            'productos.*.centros.*.id' => 'required|exists:centro,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1'
        ]);

        return DB::transaction(function () use ($validated, $requisicion) {
            $requisicion->update($validated);
            
            // Eliminar relaciones existentes
            DB::table('producto_requisicion')
                ->where('id_requisicion', $requisicion->id)
                ->delete();

            // Volver a crear relaciones
            foreach ($validated['productos'] as $productoData) {
                $producto = Producto::find($productoData['id']);

                // Validar stock
                if ($productoData['cantidad'] > $producto->stock_produc) {
                    throw ValidationException::withMessages([
                        'productos.'.$productoData['id'].'.cantidad' => 
                        "La cantidad supera el stock disponible para {$producto->name_produc}"
                    ]);
                }

                // Adjuntar producto con cantidad
                $requisicion->productos()->attach($producto->id, [
                    'pr_amount' => $productoData['cantidad']
                ]);

                // Adjuntar centros de costos
                foreach ($productoData['centros'] as $centroData) {
                    DB::table('centro_producto')->insert([
                        'producto_requisicion_id' => DB::getPdo()->lastInsertId(),
                        'centro_id' => $centroData['id'],
                        'rc_amount' => $centroData['cantidad'],
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