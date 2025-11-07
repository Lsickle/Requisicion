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
    public ?string $sessionEmail;

    public $tries = 3;
    public function backoff(): array { return [10, 30, 60]; }

    public function __construct(OrdenCompra $orden, Producto $producto, int $cantidad, string $usuario, ?string $sessionEmail = null)
    {
        $this->orden = $orden;
        $this->producto = $producto;
        $this->cantidad = $cantidad;
        $this->usuario = $usuario;
        $this->sessionEmail = $sessionEmail;
    }

    public function handle(): void
    {
        try {
            $toList = [];
            if (!empty($this->sessionEmail)) { $toList[] = $this->sessionEmail; }
            $compras = env('COMPRAS_MAIL_TO') ?: 'areacompras@gmail.com';
            if (!empty($compras)) { $toList[] = $compras; }
            $toList = array_values(array_unique(array_filter($toList)));

            if (empty($toList)) { Log::warning('RecepcionProductoOCJob: sin destinatarios'); return; }

            $gap = (float) env('MAIL_MIN_GAP_SECONDS', 0);
            if ($gap > 0) { usleep((int) round($gap * 1_000_000)); }

            Mail::to($toList)->send(new RecepcionProductoOCMailable($this->orden, $this->producto, $this->cantidad, $this->usuario));
            Log::info('RecepcionProductoOCJob enviado', ['oc'=>$this->orden->id, 'prod'=>$this->producto->id, 'to'=>$toList]);
        } catch (\Throwable $e) {
            Log::error('RecepcionProductoOCJob error: '.$e->getMessage());
        }
    }
}
