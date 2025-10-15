<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestedProductAdded extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function build()
    {
        return $this->subject('Tu solicitud de producto fue atendida')
                    ->view('emails.requested_product_added')
                    ->with(['data' => $this->payload]);
    }
}
