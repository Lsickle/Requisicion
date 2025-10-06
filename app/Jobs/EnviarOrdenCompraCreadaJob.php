<?php

namespace App\Jobs;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrdenCompraCreadaMail;

class EnviarOrdenCompraCreadaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $ordenId;
    public array $to;
    public array $cc;

    public function __construct(int $ordenId, array $to = [], array $cc = [])
    {
        $this->ordenId = $ordenId;
        $this->to = $to;
        $this->cc = $cc;
    }

    public function handle(): void
    {
        $orden = OrdenCompra::with(['requisicion','ordencompraProductos.producto','ordencompraProductos.proveedor'])->find($this->ordenId);
        if (!$orden) { return; }

        $mailable = new OrdenCompraCreadaMail($orden);
        $mailer = Mail::to($this->to);
        if (!empty($this->cc)) { $mailer->cc($this->cc); }
        $mailer->send($mailable);
    }
}
