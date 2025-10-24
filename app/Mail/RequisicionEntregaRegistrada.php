<?php

namespace App\Mail;

use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RequisicionEntregaRegistrada extends Mailable
{
    use Queueable, SerializesModels;

    public $requisicion;
    /** @var array<int,array{id:int,nombre:string,cantidad:int}> */
    public $items;

    public function __construct(Requisicion $requisicion, array $items)
    {
        $this->requisicion = $requisicion;
        $this->items = $items;
    }

    public function build()
    {
        Log::info('Construyendo correo de entrega para requisición #'.$this->requisicion->id);
        try {
            return $this->subject('Entrega registrada - #'.$this->requisicion->id)
                        ->view('emails.requisicion_entrega_registrada')
                        ->with([
                            'requisicion' => $this->requisicion,
                            'items' => $this->items,
                        ]);
        } catch (\Throwable $e) {
            Log::error('Error construyendo correo de entrega: '.$e->getMessage());
            throw $e;
        }
    }
}
