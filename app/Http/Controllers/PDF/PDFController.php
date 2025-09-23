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
        $candidates = [
            public_path('images/logo.png'),
            public_path('images/logo.jpg'),
            public_path('images/logo.jpeg'),
            public_path('images/logo_empresa.png'),
            public_path('images/logo_empresa.jpg'),
        ];

        foreach ($candidates as $logoPath) {
            if (file_exists($logoPath)) {
                $contents = file_get_contents($logoPath);
                $mime = function_exists('mime_content_type') ? mime_content_type($logoPath) : null;
                if (empty($mime)) {
                    $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                    $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                }
                return 'data:' . $mime . ';base64,' . base64_encode($contents);
            }
        }

        return null;
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

    private function pdfOrdenCompra($requisicionId)
    {
        // Obtener la requisición con todas sus órdenes de compra
        $requisicion = Requisicion::with([
            'ordenesCompra' => function($query) {
                $query->with([
                    'proveedor',
                    'productos' => function($q) {
                        $q->with(['centrosOrdenCompra' => function($q2) {
                            $q2->withPivot('rc_amount');
                        }]);
                    }
                ]);
            }
        ])->findOrFail($requisicionId);

        if ($requisicion->ordenesCompra->isEmpty()) {
            abort(404, 'No hay órdenes de compra para esta requisición.');
        }

        $tempFolder = storage_path('app/temp_pdfs');
        if (!is_dir($tempFolder)) mkdir($tempFolder, 0777, true);

        $zipFile = storage_path("app/OrdenCompra-Requisicion-{$requisicion->id}.zip");
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        foreach ($requisicion->ordenesCompra as $orden) {
            $proveedor = $orden->proveedor;
            $nombreProveedor = $proveedor->name_proveedor ?? 'Sin Proveedor';

            // Preparar los datos según el formato del PDF
            $items = [];
            foreach ($orden->productos as $producto) {
                $centros = $producto->centrosOrdenCompra->map(function($centro) {
                    return [
                        'name_centro' => $centro->name_centro,
                        'rc_amount' => $centro->pivot->rc_amount,
                    ];
                })->toArray();

                $items[] = [
                    'name_produc' => $producto->name_produc,
                    'description_produc' => $producto->description_produc,
                    'unit_produc' => $producto->unit_produc,
                    'po_amount' => $producto->pivot->po_amount,
                    'precio_unitario' => $producto->pivot->precio_unitario,
                    'centros' => $centros,
                ];
            }

            $subtotal = $orden->productos->sum(function($producto) {
                return $producto->pivot->po_amount * $producto->pivot->precio_unitario;
            });

            $data = [
                'orden' => $orden,
                'proveedor' => $proveedor,
                'items' => $items,
                'subtotal' => $subtotal,
                'observaciones' => $orden->observaciones,
                'fecha_actual' => Carbon::now()->format('d/m/Y H:i'),
                'logo' => $this->getLogoData(),
                'date_oc' => Carbon::parse($orden->date_oc)->format('d/m/Y'),
                'methods_oc' => $orden->methods_oc,
                'plazo_oc' => $orden->plazo_oc,
            ];

            // Usar la vista específica para el formato de PDF
            $pdf = PDF::loadView('ordenes_compra.pdf', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'defaultFont' => 'sans-serif']);

            $fileName = "Orden-Compra-{$orden->order_oc}-Proveedor-{$nombreProveedor}.pdf";
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
            'requisicion'        => $requisicion,
            'logo'               => $this->getLogoData(),
            'fecha_actual'       => Carbon::now()->format('d/m/Y H:i'),
            'nombreSolicitante'  => $requisicion->nombre_user,
            'operacionUsuario'   => $requisicion->operacion_user,
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