<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Requisicion;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function build()
    {
        Log::info('Construyendo correo para requisición #' . $this->requisicion->id);

        return $this->subject('Nueva Requisición Creada - #' . $this->requisicion->id)
            ->view('emails.requisicion_creada') // ¡VERIFICA QUE ESTE PATH EXISTA!
            ->to('pardomoyasegio@gmail.com');
    }
}
