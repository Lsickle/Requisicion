<?php

namespace App\Jobs;

use App\Models\Requisicion;
use App\Mail\RequisicionCreada as RequisicionCreadaMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RequisicionCreadaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $requisicion;
    public $nombreSolicitante;
    /** @var array<string> */
    public $extraEmails = [];

    // Reintentos y backoff si se usa queue asincrónica
    public $tries = 3;
    public function backoff(): array { return [10, 30, 60]; }

    /**
     * Crear una nueva instancia del Job
     *
     * @param Requisicion $requisicion
     * @param string $nombreSolicitante
     * @param array<string> $extraEmails Correos adicionales (CC) opcionales
     */
    public function __construct(Requisicion $requisicion, string $nombreSolicitante, array $extraEmails = [])
    {
        $this->requisicion = $requisicion;
        $this->nombreSolicitante = $nombreSolicitante;
        // normalizar correos extra
        $this->extraEmails = array_values(array_filter(array_map(function($v){
            if ($v === null) return null;
            $s = trim((string)$v);
            return $s !== '' ? $s : null;
        }, $extraEmails)));
    }

    /**
     * Ejecutar el Job
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $primary = $this->requisicion->email_user ?: env('REQUISICIONES_MAIL_TO', 'admin@example.com');
            if (!$primary) {
                Log::warning('RequisicionCreadaJob: sin destinatario', ['req' => $this->requisicion->id]);
                return;
            }

            // Throttle simple para evitar límites del proveedor
            $gap = (float) env('MAIL_MIN_GAP_SECONDS', 0);
            if ($gap > 0) { usleep((int) round($gap * 1_000_000)); }

            // Construir lista de CC desde distintas fuentes
            $cc = [];
            // 1) parámetro explícito
            $cc = array_merge($cc, $this->extraEmails);
            // 2) campos de la requisición si existen
            try {
                foreach (['email_cc','email_extra','emails_extra','otros_correos'] as $field) {
                    if (isset($this->requisicion->{$field}) && $this->requisicion->{$field}) {
                        $parts = preg_split('/[;,]+/', (string)$this->requisicion->{$field});
                        if ($parts) $cc = array_merge($cc, $parts);
                    }
                }
            } catch (\Throwable $e) { /* noop */ }
            // 3) variables de entorno
            $envCc = env('REQUISICIONES_MAIL_CC');
            if (!empty($envCc)) { $cc = array_merge($cc, preg_split('/[;,]+/', (string)$envCc) ?: []); }
            // 4) configuración
            try {
                $conf = (array) config('requisiciones.destinatarios_requisicion', []);
                $cc = array_merge($cc, (array) ($conf['cc'] ?? []));
                $cc = array_merge($cc, (array) ($conf['to'] ?? []));
            } catch (\Throwable $e) { /* noop */ }
            // Normalizar y deduplicar CC evitando repetir primary
            $cc = array_values(array_unique(array_filter(array_map(function($s){ return trim((string)$s); }, $cc))));
            $cc = array_values(array_filter($cc, function($addr) use ($primary){ return $addr && strcasecmp($addr, $primary) !== 0; }));

            // Construir lista de auditoría (BCC) incluyendo por defecto areacompras@gmail.com
            $bcc = [];
            try {
                $confAudit = config('requisiciones.mail_audit');
                if (!empty($confAudit)) { $bcc = array_merge($bcc, preg_split('/[;,]+/', (string)$confAudit) ?: []); }
            } catch (\Throwable $e) { /* noop */ }
            $envAudit = env('REQUISICIONES_MAIL_AUDIT');
            if (!empty($envAudit)) { $bcc = array_merge($bcc, preg_split('/[;,]+/', (string)$envAudit) ?: []); }
            $bcc[] = 'areacompras@gmail.com';
            // Normalizar, validar, deduplicar y evitar incluir primary
            $bcc = array_values(array_unique(array_filter(array_map(function($s){
                $s = trim((string)$s);
                return (filter_var($s, FILTER_VALIDATE_EMAIL)) ? $s : null;
            }, $bcc))));
            $bcc = array_values(array_filter($bcc, function($addr) use ($primary){ return $addr && strcasecmp($addr, $primary) !== 0; }));

            Log::info('RequisicionCreadaJob enviando correo', ['req' => $this->requisicion->id, 'to' => $primary, 'cc' => $cc, 'bcc' => $bcc]);
            $mailable = new RequisicionCreadaMailable($this->requisicion);
            $mailer = Mail::to($primary);
            if (!empty($cc)) { $mailer->cc($cc); }
            if (!empty($bcc)) { $mailer->bcc($bcc); }
            $mailer->send($mailable);
        } catch (\Throwable $e) {
            Log::error('RequisicionCreadaJob error al enviar', ['req' => $this->requisicion->id, 'msg' => $e->getMessage()]);
            //throw $e; // permitir reintentos si hay queue asincrónica
        }
    }
}
