<?php

namespace App\Http\Controllers\Mailto;

use App\Http\Controllers\Controller;
use App\Jobs\EstatusRequisicionActualizadoJob;
use App\Jobs\OrdenCompraCreadaJob;
use App\Jobs\RequisicionCreadaJob;
use App\Models\Estatus_Requisicion;
use App\Models\OrdenCompra;
use App\Models\Requisicion;
use Illuminate\Http\Request;

class MailtoController extends Controller
{
    /**
     * Send notification when a requisition is created.
     */
    public function sendRequisicionCreada(Requisicion $requisicion)
    {
        RequisicionCreadaJob::dispatch($requisicion);
        return response()->json(['message' => 'Requisicion created notification queued']);
    }

    /**
     * Send notification when a requisition status is updated.
     */
    public function sendEstatusRequisicionActualizado(Requisicion $requisicion, Estatus_Requisicion $estatus)
    {
        EstatusRequisicionActualizadoJob::dispatch($requisicion, $estatus);
        return response()->json(['message' => 'Requisition status update notification queued']);
    }

    /**
     * Send notification when a purchase order is created.
     */
    public function sendOrdenCompraCreada(OrdenCompra $orden)
    {
        OrdenCompraCreadaJob::dispatch($orden);
        return response()->json(['message' => 'Purchase order created notification queued']);
    }
}