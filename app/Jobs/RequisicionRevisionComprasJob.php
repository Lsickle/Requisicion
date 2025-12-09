<?php

namespace App\Jobs;

use App\Models\Requisicion;
use App\Mail\RequisicionRevisionCompras as RequisicionRevisionComprasMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RequisicionRevisionComprasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Requisicion $requisicion;
    public $tries = 3;
    public function backoff(): array { return [10,30,60]; }

    public function __construct(Requisicion $requisicion)
    {
        $this->requisicion = $requisicion;
    }

    public function handle(): void
    {
        try {
            $compras = trim((string) env('COMPRAS_MAIL_TO', 'areacompras@gmail.com'));
            if (!filter_var($compras, FILTER_VALIDATE_EMAIL)) {
                Log::warning('RequisicionRevisionComprasJob: correo compras inválido', ['value' => $compras]);
                return;
            }
            Log::info('Enviando correo de revisión de requisición a compras', ['req' => $this->requisicion->id, 'to' => $compras]);
            Mail::to([$compras])->send(new RequisicionRevisionComprasMailable($this->requisicion));
        } catch (\Throwable $e) {
            Log::error('RequisicionRevisionComprasJob error', ['req' => $this->requisicion->id, 'msg' => $e->getMessage()]);
        }
    }
}
