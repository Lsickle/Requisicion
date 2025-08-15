<?php

namespace App\Http\Controllers\requisicion;

use App\Http\Controllers\Controller;
use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\Centro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Mail\RequisicionCreada;
use Illuminate\Support\Facades\Mail;

class RequisicionController extends Controller
{
    /**
     * Mostrar todas las requisiciones
     */
    public function index()
    {
        $requisiciones = Requisicion::with(['productos'])->latest()->get();
        return view('requisiciones.index', compact('requisiciones'));
    }

    /**
     * Mostrar el formulario para crear una nueva requisición
     */
    public function create()
    {
        $categorias = Producto::distinct()->pluck('categoria_produc');
        $centros = Centro::all();

        return view('requisiciones.create', compact('categorias', 'centros'));
    }

    /**
     * Guardar una nueva requisición en la base de datos
     */
    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        return DB::transaction(function () use ($validated) {
            $requisicion = Requisicion::create([
                'prioridad_requisicion' => $validated['prioridad_requisicion'],
                'Recobreble' => $validated['Recobreble'],
                'detail_requisicion' => $validated['detail_requisicion'],
                'justify_requisicion' => $validated['justify_requisicion'],
                'date_requisicion' => now(),
            ]);

            $this->attachProductosCentros($requisicion, $validated['productos']);

            // Enviar correo electrónico
            $destinatarios = ['compras@empresa.com', 'finanzas@empresa.com']; // Reemplaza con tus destinatarios reales
            Mail::to($destinatarios)->send(new RequisicionCreada($requisicion));

            return redirect()->route('requisiciones.show', $requisicion)
                ->with('success', 'Requisición creada exitosamente');
        });
    }

    /**
     * Mostrar una requisición específica
     */
    public function show(Requisicion $requisicion)
    {
        $requisicion->load(['productos.centros']);
        return view('requisiciones.show', compact('requisicion'));
    }

    /**
     * Mostrar el formulario de edición de una requisición
     */
    public function edit(Requisicion $requisicion)
    {
        $requisicion->load(['productos.centros']);
        $categorias = Producto::distinct()->pluck('categoria_produc');
        $centros = Centro::all();

        return view('requisiciones.edit', compact('requisicion', 'categorias', 'centros'));
    }

    /**
     * Actualizar una requisición existente
     */
    public function update(Request $request, Requisicion $requisicion)
    {
        $validated = $this->validateRequest($request);

        return DB::transaction(function () use ($validated, $requisicion) {
            $requisicion->update([
                'prioridad_requisicion' => $validated['prioridad_requisicion'],
                'Recobreble' => $validated['Recobreble'],
                'detail_requisicion' => $validated['detail_requisicion'],
                'justify_requisicion' => $validated['justify_requisicion'],
            ]);

            // Eliminar productos y centros antiguos
            DB::table('producto_requisicion')->where('id_requisicion', $requisicion->id)->delete();

            $this->attachProductosCentros($requisicion, $validated['productos']);

            return redirect()->route('requisiciones.show', $requisicion)
                ->with('success', 'Requisición actualizada exitosamente');
        });
    }

    /**
     * Eliminar una requisición
     */
    public function destroy(Requisicion $requisicion)
    {
        $requisicion->delete();
        return redirect()->route('requisiciones.index')
            ->with('success', 'Requisición eliminada');
    }

    /**
     * Validar los datos de la requisición
     */
    private function validateRequest(Request $request)
    {
        return $request->validate([
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
    }

    /**
     * Guardar productos y sus centros de costo asociados a la requisición
     */
    private function attachProductosCentros(Requisicion $requisicion, array $productos)
    {
        foreach ($productos as $productoData) {
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
    }
}