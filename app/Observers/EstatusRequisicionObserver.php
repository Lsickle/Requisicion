<?php

namespace App\Observers;

use App\Jobs\EstatusRequisicionActualizadoJob;
use App\Models\Estatus_Requisicion;

class EstatusRequisicionObserver
{
    /** Handle the Estatus_Requisicion "created" event. */
    public function created(Estatus_Requisicion $estatus)
    {
        $requisicion = $estatus->requisicion ?? $estatus->requisicion()->first();
        if (!$requisicion) return;
        // Dispatch job to send email asynchronously
        EstatusRequisicionActualizadoJob::dispatch($requisicion, $estatus);
    }
}
