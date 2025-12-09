<?php

namespace App\Mail;

use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RequisicionRevisionCompras extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Requisicion $requisicion;

    public function __construct(Requisicion $requisicion)
    {
        $this->requisicion = $requisicion;
    }

    public function build()
    {
        return $this->subject('Nueva Requisición para Revisión')
            ->view('emails.requisicion_revision_compras')
            ->with([
                'requisicion' => $this->requisicion,
            ]);
    }
}
