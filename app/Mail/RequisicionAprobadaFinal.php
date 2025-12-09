<?php

namespace App\Mail;

use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RequisicionAprobadaFinal extends Mailable
{
    use Queueable, SerializesModels;

    public Requisicion $requisicion;

    public function __construct(Requisicion $requisicion)
    {
        $this->requisicion = $requisicion;
    }

    public function build()
    {
        Log::info('Construyendo correo AprobadaFinal para requisición #'.$this->requisicion->id);
        return $this->subject('Requisición aprobada - #'.$this->requisicion->id)
            ->view('emails.requisicion_aprobada_final')
            ->with(['requisicion' => $this->requisicion]);
    }
}
