<?php

namespace App\Jobs;

use App\Models\Requisicion;
use App\Mail\RequisicionEntregaRegistrada as RequisicionEntregaRegistradaMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RequisicionEntregaRegistradaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Requisicion $requisicion;
    /** @var array<int,array{id:int,nombre:string,cantidad:int}> */
    public array $items;

    public $tries = 3;
    public function backoff(): array { return [10, 30, 60]; }

    public function __construct(Requisicion $requisicion, array $items)
    {
        $this->requisicion = $requisicion;
        $this->items = $items;
    }

    public function handle(): void
    {
        try {
            $primary = $this->requisicion->email_user ?: env('REQUISICIONES_MAIL_TO', 'admin@example.com');
            if (!$primary) { Log::warning('EntregaJob: sin destinatario', ['req'=>$this->requisicion->id]); return; }

            $gap = (float) env('MAIL_MIN_GAP_SECONDS', 0);
            if ($gap > 0) { usleep((int) round($gap * 1_000_000)); }

            // BCC auditorías como en RequisicionCreadaJob
            $bcc = [];
            try { $confAudit = config('requisiciones.mail_audit'); if (!empty($confAudit)) { $bcc = array_merge($bcc, preg_split('/[;,]+/', (string)$confAudit) ?: []); } } catch (\Throwable $e) {}
            $envAudit = env('REQUISICIONES_MAIL_AUDIT'); if (!empty($envAudit)) { $bcc = array_merge($bcc, preg_split('/[;,]+/', (string)$envAudit) ?: []); }
            $bcc[] = 'areacompras@gmail.com';
            $bcc = array_values(array_unique(array_filter(array_map(function($s){ $s = trim((string)$s); return filter_var($s, FILTER_VALIDATE_EMAIL) ? $s : null; }, $bcc))));
            $bcc = array_values(array_filter($bcc, fn($addr) => $addr && strcasecmp($addr, $primary) !== 0));

            Log::info('RequisicionEntregaRegistradaJob enviando', ['req'=>$this->requisicion->id, 'to'=>$primary, 'bcc'=>$bcc, 'items'=>$this->items]);
            $mailable = new RequisicionEntregaRegistradaMailable($this->requisicion, $this->items);
            $mailer = Mail::to($primary);
            if (!empty($bcc)) { $mailer->bcc($bcc); }
            $mailer->send($mailable);
        } catch (\Throwable $e) {
            Log::error('EntregaJob error al enviar', ['req'=>$this->requisicion->id, 'msg'=>$e->getMessage()]);
        }
    }
}
