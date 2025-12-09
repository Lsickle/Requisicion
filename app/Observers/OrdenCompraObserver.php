<?php

namespace App\Observers;

use App\Models\OrdenCompraEstatus;
use App\Models\EstatusOrdenCompra;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\OrdenCompraCreada;
use App\Models\Requisicion;

class OrdenCompraObserver
{
    /**
     * Handle the OrdenCompra "created" event.
     */
    public function created($ordenCompra)
    {
        try {
            // Desactivar cualquier estatus previo de esta orden (por si acaso)
            OrdenCompraEstatus::where('orden_compra_id', $ordenCompra->id)->update(['activo' => 0]);

            // Preferir el estatus con id = 1 si existe (requerido por la especificación)
            $status = EstatusOrdenCompra::find(1);

            // Si no existe, buscar por nombre
            if (!$status) {
                $status = EstatusOrdenCompra::where('status_name', 'Orden de compra creada')->first();
            }

            // Fallback: el primer registro disponible
            if (!$status) {
                $status = EstatusOrdenCompra::first();
            }

            if ($status) {
                OrdenCompraEstatus::create([
                    'estatus_id' => $status->id,
                    'orden_compra_id' => $ordenCompra->id,
                    'activo' => 1,
                    'date_update' => now(),
                    'user_id' => session('user.id') ?? Auth::id() ?? null,
                    'user_name' => session('user.name') ?? session('user.email') ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            // no interrumpir el flujo si falla la inserción del estatus
            logger()->error('OrdenCompraObserver error creating status: '.$e->getMessage());
        }

        // Enviar correos: al solicitante de la requisición y a destinatarios configurados
        try {
            $requisicion = null;
            try {
                $requisicion = $ordenCompra->requisicion ?? (isset($ordenCompra->requisicion_id) ? Requisicion::find($ordenCompra->requisicion_id) : null);
            } catch (\Throwable $e) { /* noop */ }

            $conf = (array) config('requisiciones.destinatarios_oc', []);
            $toConfig = array_values(array_unique((array)($conf['to'] ?? [])));
            $ccConfig = array_values(array_unique((array)($conf['cc'] ?? [])));

            // Enviar al email del solicitante si existe
            if (!empty($requisicion?->email_user)) {
                try {
                    Mail::to($requisicion->email_user)->send(new OrdenCompraCreada($ordenCompra));
                } catch (\Throwable $e) {
                    Log::warning('OrdenCompraObserver: no se pudo enviar correo al solicitante ' . $requisicion->email_user . ': ' . $e->getMessage());
                }
            }

            // Enviar a destinatarios configurados (evitar reenviar al mismo email del solicitante)
            $toFiltered = array_values(array_filter($toConfig, function($addr) use ($requisicion) {
                if (empty($addr)) return false;
                if (!empty($requisicion?->email_user) && $addr === $requisicion->email_user) return false;
                return true;
            }));

            if (!empty($toFiltered)) {
                try {
                    $m = new OrdenCompraCreada($ordenCompra);
                    if (!empty($ccConfig)) Mail::to($toFiltered)->cc($ccConfig)->send($m);
                    else Mail::to($toFiltered)->send($m);
                } catch (\Throwable $e) {
                    Log::warning('OrdenCompraObserver: no se pudo enviar correo OC creada a destinatarios configurados: ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::warning('OrdenCompraObserver: error durante envío de correos OC creada: ' . $e->getMessage());
        }
    }
}
