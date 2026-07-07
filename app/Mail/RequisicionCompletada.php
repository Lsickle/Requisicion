<?php

namespace App\Mail;

use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisicionCompletada extends Mailable
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
        return $this->subject('Requisición completada - #' . ($this->requisicion->id ?? ''))
                    ->view('emails.requisicion_completada')
                    ->with([
                        'requisicion' => $this->requisicion,
                        'estatus'     => $this->estatus,
                    ]);
    }
}
