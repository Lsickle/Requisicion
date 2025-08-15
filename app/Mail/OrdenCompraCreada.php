<?php

namespace App\Mail;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrdenCompraCreada extends Mailable
{
    use Queueable, SerializesModels;

    public $orden;

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
    }

    public function build()
    {
        return $this->subject('Nueva Orden de Compra Creada - #' . $this->orden->id)
                    ->view('emails.orden_compra_creada')
                    ->with([
                        'orden' => $this->orden,
                    ]);
    }
}