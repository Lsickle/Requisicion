<?php

namespace App\Mail;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrdenCompraCreadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public OrdenCompra $orden;

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
    }

    public function build()
    {
        $subject = 'Orden de compra creada #'.($this->orden->order_oc ?? ('OC-'.$this->orden->id));
        return $this->subject($subject)
            ->view('emails.orden_compra_creada')
            ->with([
                'orden' => $this->orden,
            ]);
    }
}
