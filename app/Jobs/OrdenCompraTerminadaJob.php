<?php

namespace App\Jobs;

use App\Models\OrdenCompra;
use App\Mail\OrdenCompraTerminada as OrdenCompraTerminadaMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OrdenCompraTerminadaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public OrdenCompra $orden;

    public $tries = 3;
    public function backoff(): array { return [10, 30, 60]; }

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
    }

    public function handle(): void
    {
        try {
            $to = env('COMPRAS_MAIL_TO') ?: 'areacompras@gmail.com';
            if (!$to) { Log::warning('OrdenCompraTerminadaJob: sin destinatario'); return; }
            $gap = (float) env('MAIL_MIN_GAP_SECONDS', 0);
            if ($gap > 0) { usleep((int) round($gap * 1_000_000)); }

            Mail::to($to)->send(new OrdenCompraTerminadaMailable($this->orden));
            Log::info('OrdenCompraTerminadaJob enviado', ['oc'=>$this->orden->id, 'to'=>$to]);
        } catch (\Throwable $e) {
            Log::error('OrdenCompraTerminadaJob error: '.$e->getMessage());
        }
    }
}
