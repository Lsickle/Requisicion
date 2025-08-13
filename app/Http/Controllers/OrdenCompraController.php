<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Centro;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use ZipArchive;

class OrdenCompraController extends Controller
{
    public function index()
    {
        $ordenes = OrdenCompra::with(['proveedor', 'productos.proveedor'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ordenes_compra.index', compact('ordenes'));
    }

    public function create()
    {
        $proveedores = Proveedor::all();
        $productos   = Producto::all();
        $centros     = Centro::all();

        return view('ordenes_compra.create', compact('proveedores', 'productos', 'centros'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id'                => 'required|exists:proveedores,id',
            'date_oc'                     => 'required|date',
            'methods_oc'                  => 'required|string|max:255',
            'plazo_oc'                     => 'required|string|max:255',
            'order_oc'                    => 'required|integer|unique:orden_compras,order_oc',
            'observaciones'               => 'nullable|string',
            'productos'                   => 'required|array|min:1',
            'productos.*.id'              => 'required|exists:productos,id',
            'productos.*.cantidad'        => 'required|integer|min:1',
            'productos.*.precio'          => 'required|numeric|min:0',
            'productos.*.centros'         => 'required|array|min:1',
            'productos.*.centros.*.id'    => 'required|exists:centros,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            $orden = OrdenCompra::create([
                'proveedor_id'  => $request->proveedor_id,
                'date_oc'       => $request->date_oc,
                'methods_oc'    => $request->methods_oc,
                'plazo_oc'      => $request->plazo_oc,
                'order_oc'      => $request->order_oc,
                'observaciones' => $request->observaciones,
                'estado'        => 'pendiente',
            ]);

            foreach ($request->productos as $productoData) {
                $orden->productos()->attach($productoData['id'], [
                    'po_amount'       => $productoData['cantidad'],
                    'precio_unitario' => $productoData['precio'],
                    'observaciones'   => $request->observaciones ?? null
                ]);

                foreach ($productoData['centros'] as $centroData) {
                    DB::table('centro_ordencompra')->insert([
                        'producto_id'      => $productoData['id'],
                        'centro_id'        => $centroData['id'],
                        'orden_compra_id'  => $orden->id,
                        'rc_amount'        => $centroData['cantidad'],
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            }
        });

        return redirect()->route('ordenes-compra.index')->with('success', 'Orden de compra creada exitosamente');
    }

    public function show(string $id)
    {
        $orden = OrdenCompra::with([
            'proveedor',
            'productos.proveedor',
            'productos.centrosOrdenCompra' => function ($q) use ($id) {
                $q->where('centro_ordencompra.orden_compra_id', $id);
            }
        ])->findOrFail($id);

        return view('ordenes_compra.show', compact('orden'));
    }

    public function edit(string $id)
    {
        $orden       = OrdenCompra::with([
            'productos.proveedor',
            'productos.centrosOrdenCompra' => function ($q) use ($id) {
                $q->where('centro_ordencompra.orden_compra_id', $id);
            }
        ])->findOrFail($id);

        $proveedores = Proveedor::all();
        $productos   = Producto::all();
        $centros     = Centro::all();

        $centrosProductos = DB::table('centro_ordencompra')
            ->where('orden_compra_id', $id)
            ->get()
            ->groupBy(['producto_id', 'centro_id']);

        return view('ordenes_compra.edit', compact('orden', 'proveedores', 'productos', 'centros', 'centrosProductos'));
    }

    public function update(Request $request, string $id)
    {
        // Validación con mensajes personalizados
        $validated = $request->validate([
            'proveedor_id'                    => ['required', 'exists:proveedores,id'],
            'date_oc'                         => ['required', 'date'],
            'methods_oc'                      => ['required', 'string', 'max:255'],
            'plazo_oc'                        => ['required', 'string', 'max:255'],
            'order_oc'                        => ['required', 'integer', 'unique:orden_compras,order_oc,' . $id],
            'observaciones'                   => ['nullable', 'string'],
            'productos'                       => ['required', 'array', 'min:1'],
            'productos.*.id'                  => ['exists:productos,id'],
            'productos.*.cantidad'            => ['integer', 'min:1'],
            'productos.*.precio'              => ['numeric', 'min:0'],
            'productos.*.centros'             => ['array', 'min:1'],
            'productos.*.centros.*.id'        => ['exists:centros,id'],
            'productos.*.centros.*.cantidad'  => ['integer', 'min:1'],
        ], [
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists'   => 'El proveedor seleccionado no existe.',
            'productos.required'    => 'Debe agregar al menos un producto.',
            'productos.min'         => 'Debe agregar al menos un producto.',
            'productos.*.id.exists' => 'Alguno de los productos seleccionados no existe.',
            'productos.*.centros.min' => 'Cada producto debe estar asignado a al menos un centro.',
        ]);

        DB::transaction(function () use ($validated, $id) {
            $orden = OrdenCompra::findOrFail($id);

            // Actualizar datos básicos
            $orden->update([
                'proveedor_id'  => $validated['proveedor_id'],
                'date_oc'       => $validated['date_oc'],
                'methods_oc'    => $validated['methods_oc'],
                'plazo_oc'      => $validated['plazo_oc'],
                'order_oc'      => $validated['order_oc'],
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // Preparar productos para sincronizar
            $productosSync = [];
            foreach ($validated['productos'] as $productoData) {
                $productosSync[$productoData['id']] = [
                    'po_amount'       => $productoData['cantidad'],
                    'precio_unitario' => $productoData['precio'],
                    'observaciones'   => $validated['observaciones'] ?? null
                ];
            }
            $orden->productos()->sync($productosSync);

            // Actualizar centros de costo
            DB::table('centro_ordencompra')->where('orden_compra_id', $orden->id)->delete();
            foreach ($validated['productos'] as $productoData) {
                foreach ($productoData['centros'] as $centroData) {
                    DB::table('centro_ordencompra')->insert([
                        'producto_id'     => $productoData['id'],
                        'centro_id'       => $centroData['id'],
                        'orden_compra_id' => $orden->id,
                        'rc_amount'       => $centroData['cantidad'],
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('ordenes-compra.show', $id)
            ->with('success', 'Orden de compra actualizada exitosamente.');
    }


    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $orden = OrdenCompra::findOrFail($id);

            DB::table('centro_ordencompra')->where('orden_compra_id', $orden->id)->delete();
            $orden->productos()->detach();
            $orden->delete();
        });

        return redirect()->route('ordenes-compra.index')->with('success', 'Orden de compra eliminada exitosamente');
    }

    /**
     * Generar PDF de la orden de compra (corregido)
     */
    public function pdf(OrdenCompra $orden)
    {
        $orden->load([
            'productos.proveedor',
            'productos.centrosOrdenCompra' => function ($q) {
                $q->withPivot('rc_amount');
            }
        ]);

        $proveedores = [];

        foreach ($orden->productos as $producto) {
            $proveedorId = $producto->proveedor->id ?? 'sin_proveedor';

            $dateOC    = $producto->pivot->date_oc
                ? Carbon::parse($producto->pivot->date_oc)
                : Carbon::now();
            $methodsOC = $producto->pivot->methods_oc ?? '';
            $plazoOC   = $producto->pivot->plazo_oc ?? '';

            // ID de la tabla pivot como número de orden
            $orderNumber = $producto->pivot->id;

            if (!isset($proveedores[$proveedorId])) {
                $proveedores[$proveedorId] = [
                    'proveedor'      => $producto->proveedor,
                    'items'          => [],
                    'subtotal'       => 0,
                    'observaciones'  => [],
                    'date_oc'        => $dateOC,
                    'methods_oc'     => $methodsOC,
                    'plazo_oc'       => $plazoOC,
                    'order_oc'       => $orderNumber,
                ];
            }

            $cantidad  = (int) $producto->pivot->po_amount;
            $precio    = (float) $producto->pivot->precio_unitario;
            $totalItem = $cantidad * $precio;

            $centros = $producto->centrosOrdenCompra->map(function ($centro) {
                return [
                    'name_centro' => $centro->name_centro,
                    'rc_amount'   => $centro->pivot->rc_amount,
                ];
            })->toArray();

            $proveedores[$proveedorId]['items'][] = [
                'name_produc'        => $producto->name_produc,
                'description_produc' => $producto->description_produc,
                'unit_produc'        => $producto->unit_produc,
                'po_amount'          => $cantidad,
                'precio_unitario'    => $precio,
                'total'              => $totalItem,
                'centros'            => $centros,
            ];

            if (!empty($producto->pivot->observaciones)) {
                $proveedores[$proveedorId]['observaciones'][] = $producto->pivot->observaciones;
            }

            $proveedores[$proveedorId]['subtotal'] += $totalItem;
        }

        if (empty($proveedores)) {
            abort(404, 'No hay productos/proveedores para esta orden.');
        }

        $tempFolder = storage_path('app/temp_pdfs');
        if (!is_dir($tempFolder)) {
            mkdir($tempFolder, 0777, true);
        }

        $zipFile = storage_path("app/OrdenCompra-{$orden->id}.zip");
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        foreach ($proveedores as $prov) {
            $nombreProveedor = $prov['proveedor']->prov_name ?? 'Sin Proveedor';

            $data = [
                'orden'         => $orden,
                'proveedor'     => $prov['proveedor'],
                'items'         => $prov['items'],
                'subtotal'      => $prov['subtotal'],
                'observaciones' => implode("\n", $prov['observaciones']),
                'fecha_actual'  => Carbon::now()->format('d/m/Y H:i'),
                'logo'          => 'images/logo.png',
                'date_oc'       => $prov['date_oc']->format('d/m/Y'),
                'methods_oc'    => $prov['methods_oc'],
                'plazo_oc'      => $prov['plazo_oc'],
                'order_oc'      => $prov['order_oc'], // ID pivot
            ];

            $pdf = Pdf::loadView('ordenes_compra.pdf', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'defaultFont'          => 'sans-serif',
                ]);

            $fileName = "Orden-Compra-{$prov['order_oc']}-Proveedor-{$nombreProveedor}.pdf";
            $pdfPath = $tempFolder . '/' . $fileName;
            $pdf->save($pdfPath);

            $zip->addFile($pdfPath, $fileName);
        }

        $zip->close();

        return response()->download($zipFile)->deleteFileAfterSend(true);
    }
}
