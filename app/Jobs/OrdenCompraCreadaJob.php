<?php

namespace App\Jobs;

use App\Mail\OrdenCompraCreada;
use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class OrdenCompraCreadaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orden;

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
    }

    public function handle()
    {
        Mail::send(new OrdenCompraCreada($this->orden));
    }
}