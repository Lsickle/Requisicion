<?php

namespace App\Mail;

use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RequisicionCorregida extends Mailable implements ShouldQueue
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
        return $this->subject('Requisición Corregida - Revisión requerida')
            ->view('emails.requisicion_corregida')
            ->with([
                'requisicion' => $this->requisicion,
                'nombreSolicitante' => $this->nombreSolicitante,
            ]);
    }
}
