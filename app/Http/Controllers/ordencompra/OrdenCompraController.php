<?php

namespace App\Http\Controllers\ordencompra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Centro;
use App\Models\Requisicion;
use ZipArchive;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use Carbon\Carbon;

class OrdenCompraController extends Controller
{
    /**
     * Formulario de creación de órdenes para cada proveedor de la requisición.
     */
    public function create(Request $request)
    {
        $requisicion = null;
        $proveedores = collect();

        if ($request->has('requisicion_id') && $request->requisicion_id != 0) {
            $requisicion = Requisicion::with(['productos.proveedor'])->find($request->requisicion_id);

            if ($requisicion) {
                // Solo los proveedores de los productos de la requisición
                $proveedores = $requisicion->productos->pluck('proveedor')->unique('id')->values();
            }
        }

        return view('ordenes_compra.create', compact(
            'requisicion',
            'proveedores'
        ));
    }

    /**
     * Guardar una orden de compra para un proveedor.
     */
    public function store(Request $request)
    {
        // Validación más robusta
        $validator = Validator::make($request->all(), [
            'date_oc'        => 'required|date',
            'proveedor_id'   => 'required|exists:proveedores,id',
            'methods_oc'     => 'nullable|string|max:255',
            'plazo_oc'       => 'nullable|string|max:255',
            'observaciones'  => 'nullable|string',
            'requisicion_id' => 'required|exists:requisiciones,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Generar el número de orden automáticamente
        $ultimoOrderOc = DB::table('ordencompra_producto')
            ->orderByDesc('id')
            ->value('order_oc');

        if ($ultimoOrderOc) {
            $numero = intval(str_replace('OC-', '', $ultimoOrderOc)) + 1;
        } else {
            $numero = 1;
        }
        $nuevoOrderOc = 'OC-' . str_pad($numero, 6, '0', STR_PAD_LEFT);

        // Crear orden
        OrdenCompra::create([
            'date_oc'        => $request->date_oc,
            'proveedor_id'   => $request->proveedor_id,
            'methods_oc'     => $request->methods_oc,
            'plazo_oc'       => $request->plazo_oc,
            'observaciones'  => $request->observaciones,
            'requisicion_id' => $request->requisicion_id,
            'order_oc'       => $nuevoOrderOc,
        ]);

        // Revisar si ya existen órdenes para todos los proveedores de la requisición
        $requisicion = Requisicion::with(['productos.proveedor', 'ordenesCompra'])->find($request->requisicion_id);
        $proveedoresRequisicion = $requisicion->productos->pluck('proveedor.id')->unique();
        $proveedoresConOrden = $requisicion->ordenesCompra->pluck('proveedor_id')->unique();

        $faltan = $proveedoresRequisicion->diff($proveedoresConOrden);

        if ($faltan->isEmpty()) {
            // Ya se generaron todas → crear el ZIP
            return $this->generarZipOrdenes($requisicion->id);
        }

        return redirect()->route('ordenes_compra.create', ['requisicion_id' => $request->requisicion_id])
            ->with('success', 'Orden creada para un proveedor. Faltan otros.');
    }

    /**
     * Generar ZIP con todas las órdenes de compra de la requisición.
     */
    private function generarZipOrdenes($requisicionId)
    {
        $requisicion = Requisicion::with(['ordenesCompra.proveedor', 'ordenesCompra.requisicion'])->findOrFail($requisicionId);

        $tempFolder = storage_path('app/temp_pdfs');
        if (!is_dir($tempFolder)) mkdir($tempFolder, 0777, true);

        $zipFile = storage_path("app/OrdenesCompra-Requisicion-{$requisicion->id}.zip");
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        foreach ($requisicion->ordenesCompra as $orden) {
            $data = [
                'orden'        => $orden,
                'proveedor'    => $orden->proveedor,
                'date_oc'      => Carbon::parse($orden->date_oc)->format('d/m/Y'),
                'methods_oc'   => $orden->methods_oc,
                'plazo_oc'     => $orden->plazo_oc,
                'observaciones'=> $orden->observaciones,
                'fecha_actual' => Carbon::now()->format('d/m/Y H:i'),
                'logo'         => $this->getLogoData(),
                'items'        => $orden->requisicion->productos->where('proveedor_id', $orden->proveedor_id),
                'subtotal'     => $orden->requisicion->productos
                                    ->where('proveedor_id', $orden->proveedor_id)
                                    ->sum(fn($p) => $p->pivot->cantidad * $p->price_produc),
            ];

            $pdf = Pdf::loadView('ordenes_compra.pdf', $data);
            $fileName = "Orden-Compra-{$orden->order_oc}-Proveedor-{$orden->proveedor->prov_name}.pdf";
            $pdfPath = $tempFolder . '/' . $fileName;
            $pdf->save($pdfPath);
            $zip->addFile($pdfPath, $fileName);
        }

        $zip->close();

        // Limpiar archivos temporales
        array_map('unlink', glob("$tempFolder/*.pdf"));
        rmdir($tempFolder);

        return response()->download($zipFile)->deleteFileAfterSend(true);
    }

    /**
     * Obtener logo como base64
     */
    private function getLogoData()
    {
        $logoPath = public_path('images/logo.jpg');
        return file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : null;
    }
}
