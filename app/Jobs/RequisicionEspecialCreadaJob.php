<?php

namespace App\Jobs;

use App\Models\Requisicion;
use App\Mail\RequisicionEspecialCreada as RequisicionEspecialCreadaMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RequisicionEspecialCreadaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Requisicion $requisicion;
    public ?string $nombreSolicitante;
    /** @var array<string> */
    public array $extraEmails = [];

    public $tries = 3;
    public function backoff(): array { return [10, 30, 60]; }

    public function __construct(Requisicion $requisicion, ?string $nombreSolicitante = null, array $extraEmails = [])
    {
        $this->requisicion = $requisicion;
        $this->nombreSolicitante = $nombreSolicitante;
        $this->extraEmails = array_values(array_filter(array_map(function($v){ $s = trim((string)($v ?? '')); return $s !== '' ? $s : null; }, $extraEmails)));
    }

    public function handle(): void
    {
        try {
            $primary = trim((string)($this->requisicion->email_user ?: env('REQUISICIONES_MAIL_TO', '')));
            if (!filter_var($primary, FILTER_VALIDATE_EMAIL)) {
                Log::warning('RequisicionEspecialCreadaJob: sin destinatario primario válido', ['req' => $this->requisicion->id, 'primary' => $primary]);
                return;
            }

            $toList = [$primary];
            $compras = trim((string) env('COMPRAS_MAIL_TO', ''));
            if ($compras && filter_var($compras, FILTER_VALIDATE_EMAIL) && strcasecmp($compras, $primary) !== 0) {
                $toList[] = $compras;
            }
            $toList = array_values(array_unique(array_filter($toList)));

            $cc = $this->extraEmails;
            $envCc = (string) env('REQUISICIONES_MAIL_CC', '');
            if ($envCc) { $cc = array_merge($cc, preg_split('/[;,]+/', $envCc) ?: []); }
            $cc = array_values(array_unique(array_filter(array_map(function($s){ $s=trim((string)$s); return filter_var($s, FILTER_VALIDATE_EMAIL)?$s:null; }, $cc))));
            $cc = array_values(array_filter($cc, function($addr) use ($toList){ return !in_array($addr, $toList, true); }));

            $bcc = [];
            $envAudit = (string) env('REQUISICIONES_MAIL_AUDIT', '');
            if ($envAudit) { $bcc = array_merge($bcc, preg_split('/[;,]+/', $envAudit) ?: []); }
            $bcc = array_values(array_unique(array_filter(array_map(function($s){ $s=trim((string)$s); return filter_var($s, FILTER_VALIDATE_EMAIL)?$s:null; }, $bcc))));
            $bcc = array_values(array_filter($bcc, function($addr) use ($toList){ return !in_array($addr, $toList, true); }));

            Log::info('RequisicionEspecialCreadaJob enviando correo', ['req' => $this->requisicion->id, 'to' => $toList, 'cc' => $cc, 'bcc' => $bcc]);
            $mailable = new RequisicionEspecialCreadaMailable($this->requisicion, $this->nombreSolicitante);
            $mailer = Mail::to($toList);
            if (!empty($cc)) { $mailer->cc($cc); }
            if (!empty($bcc)) { $mailer->bcc($bcc); }
            $mailer->send($mailable);
        } catch (\Throwable $e) {
            Log::error('RequisicionEspecialCreadaJob error', ['req' => $this->requisicion->id, 'msg' => $e->getMessage()]);
        }
    }
}
