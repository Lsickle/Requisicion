<?php

namespace App\Mail;

use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Helpers\PermissionHelper;

class RequisicionCreada extends Mailable
{
    use Queueable, SerializesModels;

    public $requisicion;
    public $nombreSolicitante;

    public function __construct(Requisicion $requisicion)
    {
        $this->requisicion = $requisicion;
        $this->nombreSolicitante = PermissionHelper::getUserNameById($requisicion->user_id);
    }

    public function build()
    {
        Log::info('Construyendo correo para requisición #' . $this->requisicion->id);

        try {
            return $this->subject('Nueva Requisición Creada - #' . $this->requisicion->id)
                        ->view('emails.requisicion_creada')
                        ->with([
                            'requisicion' => $this->requisicion,
                            'nombreSolicitante' => $this->nombreSolicitante,
                        ])
                        ->to('pardomoyasegio@gmail.com');
        } catch (\Exception $e) {
            Log::error('Error construyendo correo: ' . $e->getMessage());
            throw $e;
        }
    }
}
