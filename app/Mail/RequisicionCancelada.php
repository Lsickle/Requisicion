<?php

namespace App\Mail;

use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RequisicionCancelada extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Requisicion $requisicion;
    public ?string $nombreSolicitante;

    public function __construct(Requisicion $requisicion, ?string $nombreSolicitante = null)
    {
        $this->requisicion = $requisicion;
        $this->nombreSolicitante = $nombreSolicitante;
    }

    public function build()
    {
        return $this->subject('Requisición Cancelada')
            ->view('emails.requisicion_cancelada')
            ->with([
                'requisicion' => $this->requisicion,
                'nombreSolicitante' => $this->nombreSolicitante,
            ]);
    }
}
