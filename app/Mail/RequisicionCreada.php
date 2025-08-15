<?php

namespace App\Mail;

use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisicionCreada extends Mailable
{
    use Queueable, SerializesModels;

    public $requisicion;

    public function __construct(Requisicion $requisicion)
    {
        $this->requisicion = $requisicion;
    }

    public function build()
    {
        return $this->subject('Nueva Requisición Creada - #' . $this->requisicion->id)
                    ->view('emails.requisicion_creada')
                    ->with([
                        'requisicion' => $this->requisicion,
                    ]);
    }
}