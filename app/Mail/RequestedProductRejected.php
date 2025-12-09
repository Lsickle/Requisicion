<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestedProductRejected extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function build()
    {
        return $this->subject('Tu solicitud de producto fue rechazada')
                    ->view('emails.requested_product_rejected')
                    ->with(['data' => $this->payload]);
    }
}
