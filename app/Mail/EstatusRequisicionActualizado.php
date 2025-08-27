<?php

namespace App\Mail;

use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EstatusRequisicionActualizado extends Mailable
{
    use Queueable, SerializesModels;

    public $requisicion;
    public $estatus;

    /**
     * Create a new message instance.
     */
    public function __construct(Requisicion $requisicion, Estatus_Requisicion $estatus)
    {
        $this->requisicion = $requisicion;
        $this->estatus = $estatus;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Actualización de Estatus - Requisición #' . $this->requisicion->id)
                    ->view('emails.estatus_requisicion_actualizado')
                    ->with([
                        'requisicion' => $this->requisicion,
                        'estatus'     => $this->estatus,
                    ]);
    }
}
