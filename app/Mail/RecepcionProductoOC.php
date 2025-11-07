<?php

namespace App\Mail;

use App\Models\OrdenCompra;
use App\Models\Producto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecepcionProductoOC extends Mailable
{
    use Queueable, SerializesModels;

    public OrdenCompra $orden;
    public Producto $producto;
    public int $cantidad;
    public string $usuario;

    public function __construct(OrdenCompra $orden, Producto $producto, int $cantidad, string $usuario)
    {
        $this->orden = $orden;
        $this->producto = $producto;
        $this->cantidad = $cantidad;
        $this->usuario = $usuario;
    }

    public function build()
    {
        $num = $this->orden->order_oc ?? ('OC-'.$this->orden->id);
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Recepción registrada - '.$num)
            ->view('emails.recepcion_producto_oc')
            ->with([
                'orden' => $this->orden,
                'producto' => $this->producto,
                'cantidad' => $this->cantidad,
                'usuario' => $this->usuario,
                'numero' => $num,
            ]);
    }
}
