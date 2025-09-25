<?php

namespace App\Observers;

use App\Models\OrdenCompraEstatus;
use App\Models\EstatusOrdenCompra;
use Illuminate\Support\Facades\Auth;

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
                    'user_id' => Auth::id() ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            // no interrumpir el flujo si falla la inserción del estatus
            logger()->error('OrdenCompraObserver error creating status: '.$e->getMessage());
        }
    }
}
