<?php

namespace App\Jobs;

use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AprobacionEtapaMail;

class NotificarAprobacionEtapaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $requisicion;
    public $estatus;
    public $stageKey;
    public $destinatarios;
    public $subject;
    public $mensajePrincipal;
    public $panelUrl;
    public $detalleUrl;

    public function __construct(Requisicion $requisicion, Estatus_Requisicion $estatus, string $stageKey, array $destinatarios, string $subject, string $mensajePrincipal, string $panelUrl, string $detalleUrl)
    {
        $this->requisicion = $requisicion;
        $this->estatus = $estatus;
        $this->stageKey = $stageKey;
        $this->destinatarios = $destinatarios;
        $this->subject = $subject;
        $this->mensajePrincipal = $mensajePrincipal;
        $this->panelUrl = $panelUrl;
        $this->detalleUrl = $detalleUrl;
    }

    public function handle(): void
    {
        // Resolver destinatarios: aceptar array o string, validar emails
        $toCandidates = $this->destinatarios;
        $emails = [];

        if (is_string($toCandidates) && strlen(trim($toCandidates)) > 0) {
            $parts = preg_split('/[;,
\s]+/', $toCandidates) ?: [];
            $toCandidates = $parts;
        }

        if (is_array($toCandidates)) {
            foreach ($toCandidates as $item) {
                $s = trim((string)$item);
                if (!$s) continue;
                // Si trae un nombre con correo entre <>, extraer
                if (preg_match('/<([^>]+)>/', $s, $m)) { $s = $m[1]; }
                if (filter_var($s, FILTER_VALIDATE_EMAIL)) { $emails[] = $s; }
            }
        }

        // Si no hay emails válidos, intentar fallback por stage/env
        if (empty($emails)) {
            $raw = '';
            if ($this->stageKey === 'stage2') {
                $raw = env('APROB_STAGE2_TO') ?: env('REQUISICIONES_MAIL_TO') ?: 'Operaciones@gmail.com';
            } elseif ($this->stageKey === 'stage3') {
                $raw = env('APROB_STAGE3_TO') ?: env('REQUISICIONES_MAIL_TO') ?: 'Financiera@gmail.com';
            } else { // final
                $raw = env('COMPRAS_MAIL_TO') ?: env('REQUISICIONES_MAIL_TO') ?: 'areacompras@gmail.com';
            }
            
            $parts = preg_split('/[;,\s]+/', (string)$raw) ?: [];
            foreach ($parts as $p) {
                $p = trim((string)$p);
                if ($p && filter_var($p, FILTER_VALIDATE_EMAIL)) $emails[] = $p;
            }
        }

        // No se añade el email del solicitante como destinatario por diseño; solo se usan destinatarios explícitos o variables de entorno.

        $to = array_values(array_unique($emails));

        if (empty($to)) {
            Log::warning('NotificarAprobacionEtapaJob: sin destinatarios válidos', ['req'=> $this->requisicion->id ?? null, 'stage'=>$this->stageKey, 'raw_destinatarios'=>$this->destinatarios]);
            return;
        }

        $mailable = new AprobacionEtapaMail(
            $this->requisicion,
            $this->estatus,
            $this->stageKey,
            $this->subject,
            $this->mensajePrincipal,
            $this->panelUrl,
            $this->detalleUrl
        );

        try {
            Mail::to($to)->send($mailable);
            Log::info('NotificarAprobacionEtapaJob enviado', ['req'=>$this->requisicion->id ?? null, 'stage'=>$this->stageKey, 'to'=>$to]);
        } catch (\Throwable $e) {
            Log::error('NotificarAprobacionEtapaJob: error enviando correo', ['err'=>$e->getMessage(), 'req'=>$this->requisicion->id ?? null, 'to'=>$to]);
        }
    }
}
