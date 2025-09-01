<?php

namespace App\Mail;

use App\Models\Nuevo_Producto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NuevoProductoSolicitado extends Mailable
{
    use Queueable, SerializesModels;

    public $producto;

    public function __construct(Nuevo_Producto $producto)
    {
        $this->producto = $producto;
    }

    public function build()
    {
        Log::info('Construyendo correo para solicitud de nuevo producto #' . $this->producto->id);

        try {
            return $this->subject('Nueva Solicitud de Producto - ' . $this->producto->nombre)
                ->view('emails.nuevo_producto_solicitado')
                ->replyTo($this->producto->email_user, $this->producto->name_user) // 👈 para responder al usuario
                ->to('pardomoyasegio@gmail.com');
        } catch (\Exception $e) {
            Log::error('Error construyendo correo: ' . $e->getMessage());
            throw $e;
        }
    }
}
