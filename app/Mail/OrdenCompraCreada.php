<?php

namespace App\Mail;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrdenCompraCreada extends Mailable
{
    use Queueable, SerializesModels;

    public $orden;

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
    }

    public function build()
    {
        // Asegurar relaciones mínimas
        try { $this->orden->loadMissing(['ordencompraProductos.proveedor', 'requisicion']); } catch (\Throwable $e) { /* noop */ }
        $orden = $this->orden->fresh();

        // Resolver proveedor igual que en PDF
        $provModel = null;
        try {
            $lineas = $orden->ordencompraProductos ?? collect();
            if ($lineas->count()) {
                $withProv = $lineas->first(function($l){ return !empty($l->proveedor_id); });
                if ($withProv) { $provModel = $withProv->proveedor ?: \App\Models\Proveedor::find($withProv->proveedor_id); }
                if (!$provModel) {
                    $firstProvId = $lineas->pluck('proveedor_id')->filter()->first();
                    if ($firstProvId) { $provModel = \App\Models\Proveedor::find($firstProvId); }
                }
            }
            if (!$provModel) {
                $provRow = DB::table('ordencompra_producto as ocp')
                    ->join('proveedores as prov', 'prov.id', '=', 'ocp.proveedor_id')
                    ->where('ocp.orden_compras_id', $orden->id)
                    ->whereNull('ocp.deleted_at')
                    ->select('prov.*')
                    ->first();
                if ($provRow) { $provModel = (object) $provRow; }
            }
            if (!$provModel) {
                $firstProdId = $lineas->pluck('producto_id')->filter()->first();
                if (!$firstProdId) {
                    $firstProdId = DB::table('ordencompra_producto')
                        ->where('orden_compras_id', $orden->id)
                        ->whereNull('deleted_at')
                        ->orderBy('id')
                        ->value('producto_id');
                }
                if ($firstProdId) {
                    $provIdPxP = DB::table('productoxproveedor')
                        ->where('producto_id', $firstProdId)
                        ->orderBy('id')
                        ->value('proveedor_id');
                    if ($provIdPxP) { $provModel = \App\Models\Proveedor::find($provIdPxP); }
                }
            }
        } catch (\Throwable $e) { /* noop */ }

        // Nullsafe al acceder a campos del proveedor
        $methodsOc = $provModel?->methods_oc ?? null;
        $plazoOc = Schema::hasColumn('proveedores', 'plazo_oc') ? ($provModel?->plazo_oc ?? null) : null;

        // Fecha con hora (preferir date_oc si existe)
        $dateBase = $orden->date_oc ? \Carbon\Carbon::parse($orden->date_oc) : ($orden->created_at ?? now());
        $createdAtStr = $dateBase->format('d/m/Y') . ' ' . (($orden->created_at ?? now())->format('H:i'));

        $proveedor = $provModel ? [
            'prov_name'   => $provModel?->prov_name   ?? '',
            'prov_nit'    => $provModel?->prov_nit    ?? '',
            'prov_name_c' => $provModel?->prov_name_c ?? '',
            'prov_phone'  => $provModel?->prov_phone  ?? '',
            'prov_adress' => $provModel?->prov_adress ?? '',
            'prov_city'   => $provModel?->prov_city   ?? '',
            'methods_oc'  => $methodsOc,
            'plazo_oc'    => $plazoOc,
        ] : null;

        return $this->subject('Nueva Orden de Compra Creada - #' . ($orden->order_oc ?? $orden->id))
            ->view('emails.orden_compra_creada')
            ->with([
                'orden' => $orden,
                'proveedor' => $proveedor,
                'createdAtStr' => $createdAtStr,
            ]);
    }
}