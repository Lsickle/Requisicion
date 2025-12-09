<?php

namespace App\Jobs;

use App\Models\Requisicion;
use App\Mail\RequisicionCorregida as RequisicionCorregidaMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RequisicionCorregidaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Requisicion $requisicion;
    public ?string $nombreSolicitante;
    public $tries = 3;
    public function backoff(): array { return [10,30,60]; }

    public function __construct(Requisicion $requisicion, ?string $nombreSolicitante = null)
    {
        $this->requisicion = $requisicion;
        $this->nombreSolicitante = $nombreSolicitante;
    }

    public function handle(): void
    {
        try {
            // Evitar enviar si es especial (podría tener flujo distinto)
            $tipo = strtolower((string)($this->requisicion->type ?? ''));
            if ($tipo === 'especial') {
                Log::info('RequisicionCorregidaJob: omitiendo (tipo especial)', ['req'=>$this->requisicion->id]);
                return;
            }
            $primary = trim((string)($this->requisicion->email_user ?: ''));
            $compras = trim((string) env('COMPRAS_MAIL_TO', 'areacompras@gmail.com'));
            $to = [];
            if (filter_var($compras, FILTER_VALIDATE_EMAIL)) { $to[] = $compras; }
            // Incluir solicitante si es email válido y distinto
            if (filter_var($primary, FILTER_VALIDATE_EMAIL) && strcasecmp($primary, $compras) !== 0) { $to[] = $primary; }
            $to = array_values(array_unique($to));
            if (!$to) {
                Log::warning('RequisicionCorregidaJob: sin destinatarios válidos', ['req'=>$this->requisicion->id]);
                return;
            }
            Log::info('Enviando correo requisición corregida', ['req'=>$this->requisicion->id,'to'=>$to]);
            Mail::to($to)->send(new RequisicionCorregidaMailable($this->requisicion, $this->nombreSolicitante));
        } catch (\Throwable $e) {
            Log::error('RequisicionCorregidaJob error', ['req'=>$this->requisicion->id,'msg'=>$e->getMessage()]);
        }
    }
}
