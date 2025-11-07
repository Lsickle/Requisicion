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
        try { $this->orden->loadMissing(['ordencompraProductos.proveedor', 'requisicion', 'proveedor']); } catch (\Throwable $e) { /* noop */ }
        $orden = $this->orden->fresh();

        // Resolver proveedor correctamente
        $provModel = null;
        try {
            // 1) Relación directa en la orden (si existe)
            if (method_exists($orden, 'getAttribute') && $orden->getAttribute('proveedor')) {
                $provModel = $orden->proveedor;
            }
            if (!$provModel && isset($orden->proveedor_id) && !empty($orden->proveedor_id)) {
                $provModel = \App\Models\Proveedor::withTrashed()->find($orden->proveedor_id);
            }

            // 2) Dominante en líneas (si hubiera múltiples, escoger el más frecuente)
            if (!$provModel) {
                $lineas = $orden->ordencompraProductos ?? collect();
                if ($lineas->count()) {
                    $provId = $lineas->pluck('proveedor_id')->filter()->countBy()->sortDesc()->keys()->first();
                    if ($provId) { $provModel = \App\Models\Proveedor::withTrashed()->find($provId); }
                }
            }

            // 3) Join directo como último recurso
            if (!$provModel) {
                $provRow = DB::table('ordencompra_producto as ocp')
                    ->join('proveedores as prov', 'prov.id', '=', 'ocp.proveedor_id')
                    ->where('ocp.orden_compras_id', $orden->id)
                    ->whereNull('ocp.deleted_at')
                    ->select('prov.*')
                    ->orderBy('ocp.id')
                    ->first();
                if ($provRow) { $provModel = (object) $provRow; }
            }

            // 4) Fallback por requisición: producto_requisicion -> productoxproveedor
            if (!$provModel && !empty($orden->requisicion_id)) {
                $provId = DB::table('producto_requisicion as pr')
                    ->join('productoxproveedor as pxp', 'pxp.id', '=', 'pr.id_productoxproveedor')
                    ->where('pr.id_requisicion', $orden->requisicion_id)
                    ->whereNull('pxp.deleted_at')
                    ->pluck('pxp.proveedor_id')
                    ->filter()
                    ->countBy()
                    ->sortDesc()
                    ->keys()
                    ->first();
                if ($provId) { $provModel = \App\Models\Proveedor::withTrashed()->find($provId); }
            }
        } catch (\Throwable $e) { /* noop */ }

        // Nullsafe al acceder a campos del proveedor
        $methodsOc = $provModel?->methods_oc ?? null;
        $plazoOc = Schema::hasColumn('proveedores', 'plazo_oc') ? ($provModel?->plazo_oc ?? null) : null;

        // Fecha con hora (preferir date_oc si existe)
        $dateBase = $orden->date_oc ? \Carbon\Carbon::parse($orden->date_oc) : ($orden->created_at ?? now());
        $createdAtStr = $dateBase->format('d/m/Y') . ' ' . (($orden->created_at ?? now())->format('H:i'));

        // Created by: usar datos de requisición si faltan
        $createdBy = $orden->oc_user
            ?? $orden->user_name
            ?? ($orden->requisicion->name_user ?? null)
            ?? ($orden->requisicion->email_user ?? null)
            ?? $orden->email_user
            ?? 'Sistema';

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
        
        $mailable = $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Nueva Orden de Compra Creada - #' . ($orden->order_oc ?? $orden->id))
            ->view('emails.orden_compra_creada')
            ->with([
                'orden' => $orden,
                'proveedor' => $proveedor,
                'createdAtStr' => $createdAtStr,
                'createdByOverride' => $createdBy,
            ]);

        // Adjuntar PDF si existe en la orden
        try {
            $orderCode = $orden->order_oc ?? ('OC-' . $orden->id);
            $fileName = 'orden_' . $orderCode . '.pdf';
            if (!empty($orden->pdf_file)) {
                $attachment = null;
                $decoded = @base64_decode($orden->pdf_file, true);
                if ($decoded !== false && strncmp($decoded, '%PDF', 4) === 0) {
                    $attachment = $decoded;
                } elseif (strncmp($orden->pdf_file, '%PDF', 4) === 0) {
                    $attachment = $orden->pdf_file; // binario
                }
                if ($attachment) {
                    $mailable->attachData($attachment, $fileName, ['mime' => 'application/pdf']);
                }
            }
        } catch (\Throwable $e) { /* noop */ }

        return $mailable;
    }
}