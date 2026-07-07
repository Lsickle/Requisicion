<?php

namespace App\Jobs;

use App\Models\Requisicion;
use App\Mail\RequisicionAprobadaFinal as RequisicionAprobadaFinalMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RequisicionAprobadaFinalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Requisicion $requisicion;

    public $tries = 3;
    public function backoff(): array { return [10, 30, 60]; }

    public function __construct(Requisicion $requisicion)
    {
        $this->requisicion = $requisicion;
    }

    public function handle(): void
    {
        try {
            // destino principal compras
            $to = env('COMPRAS_MAIL_TO') ?: 'areacompras@gmail.com';
            if (!$to) { Log::warning('AprobadaFinalJob: sin destinatario compras', ['req'=>$this->requisicion->id]); return; }

            $gap = (float) env('MAIL_MIN_GAP_SECONDS', 0);
            if ($gap > 0) { usleep((int) round($gap * 1_000_000)); }

            // CC/BCC configurables si se requiere
            $bcc = [];
            $envAudit = env('REQUISICIONES_MAIL_AUDIT'); if (!empty($envAudit)) { $bcc = array_merge($bcc, preg_split('/[;,]+/', (string)$envAudit) ?: []); }
            $bcc[] = 'areacompras@gmail.com';
            $bcc = array_values(array_unique(array_filter(array_map(function($s){ $s = trim((string)$s); return filter_var($s, FILTER_VALIDATE_EMAIL) ? $s : null; }, $bcc))));

            Log::info('RequisicionAprobadaFinalJob enviando', ['req'=>$this->requisicion->id, 'to'=>$to]);
            $mailable = new RequisicionAprobadaFinalMailable($this->requisicion);
            $mailer = Mail::to($to);
            if (!empty($bcc)) { $mailer->bcc($bcc); }
            $mailer->send($mailable);
        } catch (\Throwable $e) {
            Log::error('RequisicionAprobadaFinalJob error al enviar', ['req'=>$this->requisicion->id, 'msg'=>$e->getMessage()]);
        }
    }
}
