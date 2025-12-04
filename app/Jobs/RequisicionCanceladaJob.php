<?php

namespace App\Jobs;

use App\Models\Requisicion;
use App\Mail\RequisicionCancelada as RequisicionCanceladaMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RequisicionCanceladaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Requisicion $requisicion;
    public ?string $nombreSolicitante;
    public $tries = 3;
    public function backoff(): array { return [10, 30, 60]; }

    public function __construct(Requisicion $requisicion, ?string $nombreSolicitante = null)
    {
        $this->requisicion = $requisicion;
        $this->nombreSolicitante = $nombreSolicitante;
    }

    public function handle(): void
    {
        try {
            $primary = trim((string)($this->requisicion->email_user ?: ''));
            $to = [];
            if (filter_var($primary, FILTER_VALIDATE_EMAIL)) { $to[] = $primary; }
            $compras = trim((string) env('COMPRAS_MAIL_TO', 'areacompras@gmail.com'));
            $cc = [];
            if ($compras && filter_var($compras, FILTER_VALIDATE_EMAIL) && strcasecmp($compras, $primary) !== 0) {
                $cc[] = $compras;
            }
            if (!$to) {
                Log::warning('RequisicionCanceladaJob: sin destinatario primario válido', ['req'=>$this->requisicion->id]);
                return;
            }
            Log::info('Enviando correo de requisición cancelada', ['req'=>$this->requisicion->id, 'to'=>$to, 'cc'=>$cc]);
            $mailer = Mail::to($to);
            if (!empty($cc)) { $mailer->cc($cc); }
            $mailer->send(new RequisicionCanceladaMailable($this->requisicion, $this->nombreSolicitante));
        } catch (\Throwable $e) {
            Log::error('RequisicionCanceladaJob error', ['req'=>$this->requisicion->id, 'msg'=>$e->getMessage()]);
        }
    }
}
