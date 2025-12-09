<?php

namespace App\Mail;

use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RequisicionRechazada extends Mailable
{
    use Queueable, SerializesModels;

    public Requisicion $requisicion;
    public int $estatusId;
    public ?string $comentario;

    public function __construct(Requisicion $requisicion, int $estatusId, ?string $comentario = null)
    {
        $this->requisicion = $requisicion;
        $this->estatusId = $estatusId;
        $this->comentario = $comentario;
    }

    public function build()
    {
        Log::info('Construyendo correo de rechazo #'.$this->requisicion->id.' estatus='.$this->estatusId);
        $subject = 'Requisición rechazada - #'.$this->requisicion->id;
        if ($this->estatusId === 11) $subject = 'Se necesita que haga correcciones en la requisición - #'.$this->requisicion->id;
        elseif ($this->estatusId === 13) $subject = 'Requisición rechazada por Gerencia - #'.$this->requisicion->id;
        elseif ($this->estatusId === 9) $subject = 'Requisición rechazada por Gerencia Financiera - #'.$this->requisicion->id;

        return $this->subject($subject)
            ->view('emails.requisicion_rechazada')
            ->with([
                'requisicion' => $this->requisicion,
                'estatusId' => $this->estatusId,
                'comentario' => $this->comentario,
            ]);
    }
}
