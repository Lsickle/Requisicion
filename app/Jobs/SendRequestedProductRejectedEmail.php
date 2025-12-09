<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\RequestedProductRejected;
use Illuminate\Support\Facades\Log;


class SendRequestedProductRejectedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        if (empty($this->payload['email_user'])) return;
        try {
            Mail::to($this->payload['email_user'])->send(new RequestedProductRejected($this->payload));
        } catch (\Throwable $e) {
            Log::error('Error sending RequestedProductRejected email: ' . $e->getMessage());
        }
    }
}
