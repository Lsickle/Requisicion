<?php

namespace App\Mail;

use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Helpers\PermissionHelper;

class EstatusRequisicionCambiado extends Mailable
{
    use Queueable, SerializesModels;

    public $requisicion;
    public $estatusRequisicion;
    public $nombreUsuario;
    public $nombreEstatus;

    public function __construct(Requisicion $requisicion, Estatus_Requisicion $estatusRequisicion,  $nombreUsuario = null)
    {
        $this->requisicion = $requisicion;
        $this->estatusRequisicion = $estatusRequisicion;
        $this->nombreUsuario = $nombreUsuario ?? PermissionHelper::getUserNameById($requisicion->user_id);
        $this->nombreEstatus = $estatusRequisicion->estatus->status_name ?? 'Desconocido';
    }

    public function build()
    {
        Log::info('Construyendo correo para cambio de estatus de requisición #' . $this->requisicion->id);

        try {
            return $this->subject('Estatus Actualizado - Requisición #' . $this->requisicion->id)
                        ->view('emails.estatus_requisicion_cambiado')
                        ->with([
                            'requisicion' => $this->requisicion,
                            'estatusRequisicion' => $this->estatusRequisicion,
                            'nombreUsuario' => $this->nombreUsuario,
                            'nombreEstatus' => $this->nombreEstatus,
                        ]);
        } catch (\Exception $e) {
            Log::error('Error construyendo correo de cambio de estatus: ' . $e->getMessage());
            throw $e;
        }
    }
}