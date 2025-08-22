<?php

namespace App\Http\Controllers\PDF;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Centro;
use App\Models\Requisicion;
use Illuminate\Support\Facades\DB;

class PdfController extends Controller
{
    /**
     * Obtiene los datos del logo para incluir en los PDFs
     */
    private function getLogoData()
    {
        $logoPath = public_path('images/logo.jpg');
        return file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : null;
    }

    /**
     * Genera PDF según el tipo de entidad
     */
    public function generar($tipo, $id)
    {
        switch ($tipo) {
            case 'orden':
                return $this->pdfOrdenCompra($id);
            case 'requisicion':
                return $this->pdfRequisicion($id);
            case 'estatus':
                return $this->pdfEstatus($id);
            default:
                abort(404, 'Tipo de PDF no válido');
        }
    }

    private function pdfOrdenCompra($id)
    {
        $orden = OrdenCompra::with([
            'productos.proveedor',
            'productos.centrosOrdenCompra' => function ($q) {
                $q->withPivot('rc_amount');
            }
        ])->findOrFail($id);

        $proveedores = [];
        foreach ($orden->productos as $producto) {
            $proveedorId = $producto->proveedor->id ?? 'sin_proveedor';
            $dateOC = $producto->pivot->date_oc ? Carbon::parse($producto->pivot->date_oc) : Carbon::now();
            $methodsOC = $producto->pivot->methods_oc ?? '';
            $plazoOC = $producto->pivot->plazo_oc ?? '';
            $orderNumber = $producto->pivot->id;

            if (!isset($proveedores[$proveedorId])) {
                $proveedores[$proveedorId] = [
                    'proveedor'     => $producto->proveedor,
                    'items'         => [],
                    'subtotal'      => 0,
                    'observaciones' => [],
                    'date_oc'       => $dateOC,
                    'methods_oc'    => $methodsOC,
                    'plazo_oc'      => $plazoOC,
                    'order_oc'      => $orderNumber,
                ];
            }

            $cantidad = (int)$producto->pivot->po_amount;
            $precio = (float)$producto->pivot->precio_unitario;
            $totalItem = $cantidad * $precio;

            $centros = $producto->centrosOrdenCompra->map(function ($centro) {
                return [
                    'name_centro' => $centro->name_centro,
                    'rc_amount'   => $centro->pivot->rc_amount,
                ];
            })->toArray();

            $proveedores[$proveedorId]['items'][] = [
                'name_produc' => $producto->name_produc,
                'description_produc' => $producto->description_produc,
                'unit_produc' => $producto->unit_produc,
                'po_amount' => $cantidad,
                'precio_unitario' => $precio,
                'total' => $totalItem,
                'centros' => $centros,
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
        if (!is_dir($tempFolder)) mkdir($tempFolder, 0777, true);

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
                'logo'          => $this->getLogoData(),
                'date_oc'       => $prov['date_oc']->format('d/m/Y'),
                'methods_oc'    => $prov['methods_oc'],
                'plazo_oc'      => $prov['plazo_oc'],
                'order_oc'      => $prov['order_oc'],
            ];

            $pdf = PDF::loadView('ordenes_compra.pdf', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'defaultFont' => 'sans-serif']);

            $fileName = "Orden-Compra-{$prov['order_oc']}-Proveedor-{$nombreProveedor}.pdf";
            $pdfPath = $tempFolder . '/' . $fileName;
            $pdf->save($pdfPath);
            $zip->addFile($pdfPath, $fileName);
        }

        $zip->close();

        return response()->download($zipFile)->deleteFileAfterSend(true);
    }

    private function pdfRequisicion($id)
    {
        $requisicion = Requisicion::with(['productos'])->findOrFail($id);
        $requisicion->date_requisicion = Carbon::parse($requisicion->date_requisicion);

        foreach ($requisicion->productos as $producto) {
            $producto->distribucion_centros = DB::table('centro_producto')
                ->where('producto_id', $producto->id)
                ->where('requisicion_id', $id)
                ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                ->select('centro.name_centro', 'centro_producto.amount')
                ->get();
        }

        // Obtener nombre del solicitante desde API usando file_get_contents
        $nombreSolicitante = $this->obtenerNombreUsuario($requisicion->user_id);

        $pdf = PDF::loadView('requisiciones.pdf', [
            'requisicion' => $requisicion,
            'logo' => $this->getLogoData(),
            'fecha_actual' => Carbon::now()->format('d/m/Y H:i'),
            'nombreSolicitante' => $nombreSolicitante,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("requisicion-{$requisicion->id}.pdf");
    }

    private function pdfEstatus($id)
    {
        $requisicion = Requisicion::with('estatus')->findOrFail($id);
        $estadoActual = $requisicion->estatus->sortByDesc('pivot.created_at')->first();
        $historial = $requisicion->estatus->sortByDesc('pivot.created_at');

        $pdf = PDF::loadView('estatus.pdf', [
            'requisicion' => $requisicion,
            'estadoActual' => $estadoActual,
            'historial' => $historial,
            'logo' => $this->getLogoData(),
            'fecha_actual' => Carbon::now()->format('d/m/Y H:i'),
            'titulo' => 'Reporte de Estatus'
        ])->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        return $pdf->download("Estatus_requisicion_{$id}.pdf");
    }

    // Método auxiliar para obtener nombre de usuario usando file_get_contents
    private function obtenerNombreUsuario($userId)
    {
        try {
            $apiUrl = env('VPL_CORE') . "/api/users/{$userId}";
            
            // Usar file_get_contents con contexto SSL deshabilitado para desarrollo
            $response = @file_get_contents($apiUrl, false, stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ],
                'http' => [
                    'timeout' => 5 // Timeout de 5 segundos
                ]
            ]));
            
            if ($response !== false) {
                $userData = json_decode($response, true);
                return $userData['name'] ?? $userData['email'] ?? 'Usuario Desconocido';
            }
        } catch (\Throwable $e) {
            Log::error("Error obteniendo usuario {$userId}: {$e->getMessage()}");
        }
        
        return 'Usuario Desconocido';
    }
}