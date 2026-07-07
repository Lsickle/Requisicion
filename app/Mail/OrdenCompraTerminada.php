<?php

namespace App\Mail;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrdenCompraTerminada extends Mailable
{
    use Queueable, SerializesModels;

    public OrdenCompra $orden;

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
    }

    public function build()
    {
        $num = $this->orden->order_oc ?? ('OC-'.$this->orden->id);
        return $this->subject('Orden de compra cerrada - '.$num)
            ->view('emails.orden_compra_terminada')
            ->with(['orden' => $this->orden, 'numero' => $num]);
    }
}
