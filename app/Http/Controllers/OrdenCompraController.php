<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class OrdenCompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }


    // OrdenCompraController.php (actualizado)
    // En OrdenCompraController.php

    public function pdf(OrdenCompra $orden)
    {
        $orden->load([
            'proveedor',
            'productos' => function ($query) {
                $query->withPivot(['po_amount', 'precio_unitario'])
                    ->with(['centrosOrdenCompra' => function ($q) {
                        $q->withPivot('rc_amount');
                    }]);
            }
        ]);

        if (is_string($orden->date_oc)) {
            $orden->date_oc = Carbon::parse($orden->date_oc);
        }

        $subtotal = 0;
        $items = [];

        foreach ($orden->productos as $producto) {
            $cantidad = $producto->pivot->po_amount;
            $precio = $producto->pivot->precio_unitario;
            $totalItem = $cantidad * $precio;

            $centros = [];
            foreach ($producto->centrosOrdenCompra as $centro) {
                $centros[] = $centro->name_centro . ' (' . $centro->pivot->rc_amount . ')';
            }

            $items[] = [
                'nombre' => $producto->name_produc,
                'descripcion' => $producto->description_produc,
                'unidad' => $producto->unit_produc,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'total' => $totalItem,
                'centros' => implode(', ', $centros)
            ];

            $subtotal += $totalItem;
        }

        $data = [
            'orden' => $orden,
            'items' => $items,
            'subtotal' => $subtotal,
            'fecha_actual' => Carbon::now()->format('d/m/Y H:i'),
            'logo' => public_path('images/logo.png')
        ];

        $pdf = PDF::loadView('ordenes_compra.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        return $pdf->download("Orden-Compra-{$orden->order_oc}.pdf");
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
