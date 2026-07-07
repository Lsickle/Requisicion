<?php

namespace App\Jobs;

use App\Models\Requisicion;
use App\Mail\RequisicionRechazada as RequisicionRechazadaMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RequisicionRechazadaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Requisicion $requisicion;
    public int $estatusId;
    public ?string $comentario;

    public $tries = 3;
    public function backoff(): array { return [10, 30, 60]; }

    public function __construct(Requisicion $requisicion, int $estatusId, ?string $comentario = null)
    {
        $this->requisicion = $requisicion;
        $this->estatusId = $estatusId;
        $this->comentario = $comentario;
    }

    public function handle(): void
    {
        try {
            $primary = $this->requisicion->email_user ?: env('REQUISICIONES_MAIL_TO', 'admin@example.com');
            if (!$primary) { Log::warning('RechazoJob: sin destinatario', ['req'=>$this->requisicion->id]); return; }

            $gap = (float) env('MAIL_MIN_GAP_SECONDS', 0);
            if ($gap > 0) { usleep((int) round($gap * 1_000_000)); }

            $bcc = [];
            try { $confAudit = config('requisiciones.mail_audit'); if (!empty($confAudit)) { $bcc = array_merge($bcc, preg_split('/[;,]+/', (string)$confAudit) ?: []); } } catch (\Throwable $e) {}
            $envAudit = env('REQUISICIONES_MAIL_AUDIT'); if (!empty($envAudit)) { $bcc = array_merge($bcc, preg_split('/[;,]+/', (string)$envAudit) ?: []); }
            $bcc[] = 'areacompras@gmail.com';
            $bcc = array_values(array_unique(array_filter(array_map(function($s){ $s = trim((string)$s); return filter_var($s, FILTER_VALIDATE_EMAIL) ? $s : null; }, $bcc))));
            $bcc = array_values(array_filter($bcc, fn($addr) => $addr && strcasecmp($addr, $primary) !== 0));

            Log::info('RequisicionRechazadaJob enviando', ['req'=>$this->requisicion->id, 'to'=>$primary, 'estatus'=>$this->estatusId]);
            $mailable = new RequisicionRechazadaMailable($this->requisicion, $this->estatusId, $this->comentario);
            $mailer = Mail::to($primary);
            if (!empty($bcc)) { $mailer->bcc($bcc); }
            $mailer->send($mailable);
        } catch (\Throwable $e) {
            Log::error('RequisicionRechazadaJob error al enviar', ['req'=>$this->requisicion->id, 'msg'=>$e->getMessage()]);
        }
    }
}
