<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\RequestedProductAdded;
use Illuminate\Support\Facades\Log;

class SendRequestedProductAddedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if (empty($this->payload['email_user'])) return;
        try {
            Mail::to($this->payload['email_user'])->send(new RequestedProductAdded($this->payload));
        } catch (\Throwable $e) {
            // opcional: log
            Log::error('Error sending RequestedProductAdded email: ' . $e->getMessage());
        }
    }
}
