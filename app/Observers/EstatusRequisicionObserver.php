<?php

namespace App\Observers;

use App\Jobs\EstatusRequisicionActualizadoJob;
use App\Models\Estatus_Requisicion;

class EstatusRequisicionObserver
{
    /** Handle the Estatus_Requisicion "created" event. */
    public function created(Estatus_Requisicion $estatus)
    {
        // Evitar notificar para el estatus inicial (1) que se crea al crear la requisición,
        // ya que la requisición ya despacha su propio job (RequisicionCreadaJob).
        if ((int) ($estatus->estatus_id ?? 0) === 1) {
            return;
        }

        $requisicion = $estatus->requisicion ?? $estatus->requisicion()->first();
        if (!$requisicion) return;
        // Dispatch job to send email asynchronously
        EstatusRequisicionActualizadoJob::dispatch($requisicion, $estatus);
    }
}
