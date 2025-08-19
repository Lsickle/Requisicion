<?php

namespace App\Http\Controllers\requisicion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\Centro;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Mailto\MailtoController;
use App\Jobs\RequisicionCreadaJob;

class RequisicionController extends Controller
{
    public function index()
    {
        $requisiciones = Requisicion::with('productos', 'estatusHistorial.estatus')->get();
        return view('index', compact('requisiciones'));
    }

    public function create()
    {
        $centros = Centro::all();
        $productos = Producto::all();

        return view('requisiciones.create', compact('centros', 'productos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Recobrable' => 'required|in:Recobrable,No recobrable',
            'prioridad_requisicion' => 'required|in:baja,media,alta',
            'justify_requisicion' => 'required|string|min:3|max:500',
            'detail_requisicion' => 'required|string|min:3|max:1000',

            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.proveedor_id' => 'nullable|exists:proveedores,id',
            'productos.*.requisicion_amount' => 'required|integer|min:1',

            'productos.*.centros' => 'required|array|min:1',
            'productos.*.centros.*.id' => 'required|exists:centro,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1',
        ], [
            'productos.required' => 'Agrega al menos un producto.',
            'productos.*.requisicion_amount.required' => 'La cantidad total por producto es obligatoria.',
        ]);

        DB::beginTransaction();

        try {
            // Calcular total de la requisición
            $totalRequisicion = 0;
            foreach ($validated['productos'] as $prod) {
                $totalRequisicion += (int)$prod['requisicion_amount'];

                // Validación: suma de centros debe coincidir
                $sumaCentros = array_sum(array_column($prod['centros'], 'cantidad'));
                if ($sumaCentros !== (int)$prod['requisicion_amount']) {
                    throw ValidationException::withMessages([
                        'productos' => "Para el producto {$prod['id']}, la suma de cantidades por centros ({$sumaCentros}) no coincide con la cantidad total indicada ({$prod['requisicion_amount']})."
                    ]);
                }
            }

            // Crear requisición
            $requisicion = new Requisicion();
            $requisicion->Recobrable = $validated['Recobrable'];
            $requisicion->prioridad_requisicion = $validated['prioridad_requisicion'];
            $requisicion->justify_requisicion = $validated['justify_requisicion'];
            $requisicion->detail_requisicion = $validated['detail_requisicion'];
            $requisicion->amount_requisicion = $totalRequisicion;
            $requisicion->save();

            // Adjuntar productos y centros
            foreach ($validated['productos'] as $prod) {
                $cantidadTotalCentros = array_sum(array_column($prod['centros'], 'cantidad'));

                // Pivot producto_requisicion
                $requisicion->productos()->attach($prod['id'], [
                    'pr_amount' => $cantidadTotalCentros
                ]);

                // Tabla centro_producto (sin requisicion_id)
                foreach ($prod['centros'] as $centro) {
                    DB::table('centro_producto')->insert([
                        'producto_id' => $prod['id'],
                        'centro_id'   => $centro['id'],
                        'amount'      => $centro['cantidad'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            DB::commit();

            // Enviar notificación por correo - FORMA CORRECTA
            // Despachar el job para enviar el correo
            RequisicionCreadaJob::dispatch($requisicion);

            return redirect()->route('index')->with('success', 'Requisición creada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al crear la requisición: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $requisicion = Requisicion::with([
            'productos',
            'productos.centrosOrdenCompra',
            'estatusHistorial.estatus'
        ])->findOrFail($id);

        return view('requisiciones.show', compact('requisicion'));
    }

    public function pdf($id)
    {
        $requisicion = Requisicion::with([
            'productos',
            'productos.centrosOrdenCompra',
            'estatusHistorial.estatus'
        ])->findOrFail($id);

        $pdf = Pdf::loadView('requisiciones.pdf', compact('requisicion'))
            ->setPaper('A4', 'portrait');

        return $pdf->download("requisicion_{$requisicion->id}.pdf");
    }
}
