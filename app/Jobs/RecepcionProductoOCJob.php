<?php

namespace App\Jobs;

use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Mail\RecepcionProductoOC as RecepcionProductoOCMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RecepcionProductoOCJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public OrdenCompra $orden;
    public Producto $producto;
    public int $cantidad;
    public string $usuario;

    public $tries = 3;
    public function backoff(): array { return [10, 30, 60]; }

    public function __construct(OrdenCompra $orden, Producto $producto, int $cantidad, string $usuario)
    {
        $this->orden = $orden;
        $this->producto = $producto;
        $this->cantidad = $cantidad;
        $this->usuario = $usuario;
    }

    public function handle(): void
    {
        try {
            $to = env('COMPRAS_MAIL_TO') ?: 'areacompras@gmail.com';
            if (!$to) { Log::warning('RecepcionProductoOCJob: sin destinatario'); return; }
            $gap = (float) env('MAIL_MIN_GAP_SECONDS', 0);
            if ($gap > 0) { usleep((int) round($gap * 1_000_000)); }

            Mail::to($to)->send(new RecepcionProductoOCMailable($this->orden, $this->producto, $this->cantidad, $this->usuario));
            Log::info('RecepcionProductoOCJob enviado', ['oc'=>$this->orden->id, 'prod'=>$this->producto->id, 'to'=>$to]);
        } catch (\Throwable $e) {
            Log::error('RecepcionProductoOCJob error: '.$e->getMessage());
        }
    }
}
