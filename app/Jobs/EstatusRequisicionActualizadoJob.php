<?php

namespace App\Jobs;

use App\Mail\EstatusRequisicionActualizado;
use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Jobs\OrdenCompraCreadaJob;
use App\Models\OrdenCompra;

class EstatusRequisicionActualizadoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $requisicion;
    protected $estatus;
    protected $userEmail;

    public $tries = 3;
    public function backoff(): array { return [10, 30, 60]; }

    /**
     * Create a new job instance.
     */
    public function __construct(Requisicion $requisicion, Estatus_Requisicion $estatus, $userEmail = null)
    {
        $this->requisicion = $requisicion;
        $this->estatus = $estatus;
        $this->userEmail = $userEmail;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Idempotencia: evitar envío duplicado para el mismo estatus de la misma requisición
            $estatusRowId = (int)($this->estatus->id ?? 0);
            $reqId = (int)($this->requisicion->id ?? 0);
            $cacheKey = 'estatus_mail_sent:'.$reqId.':'.$estatusRowId;
            // Cache::add devuelve false si ya existe la clave
            if (!Cache::add($cacheKey, 1, now()->addMinutes(15))) {
                Log::info('estatusActualizado: correo ya enviado, se omite duplicado', ['key'=>$cacheKey]);
                return;
            }

            $estatusId = (int) ($this->estatus->estatus_id ?? 0);
            $nombreEstatus = optional($this->estatus->estatusRelation)->status_name;

            // Resolver estatus activo real desde BD
            try {
                $activeId = DB::table('estatus_requisicion')
                    ->where('requisicion_id', $this->requisicion->id)
                    ->where('estatus', 1)
                    ->orderByDesc('id')
                    ->value('estatus_id');
                if (!is_null($activeId)) { $estatusId = (int)$activeId; }
            } catch (\Throwable $e) { /* noop */ }

            // Normalizar nombre para comparaciones
            $norm = function($s){
                $s = mb_strtolower((string)$s, 'UTF-8');
                $from = ['á','é','í','ó','ú','ñ']; $to = ['a','e','i','o','u','n'];
                return str_replace($from, $to, $s);
            };
            $nameNorm = $norm($nombreEstatus);

            // Ajuste: permitir también correos genéricos para aprobaciones (2,3,4) y movimiento parcial (12) si se desea doble notificación.
            // Solo saltar corrección (11) y rechazos (9,13) para evitar ruido al usuario solicitante.
            $skipIds = [9,11,13];
            $skipByName = (
                strpos($nameNorm, 'corregir') !== false ||
                strpos($nameNorm, 'rechaz') !== false
            );
            if (in_array($estatusId, $skipIds, true) || $skipByName) {
                Log::info('estatusActualizado: omitido genérico (corrección/rechazo)', [
                    'req' => $this->requisicion->id,
                    'estatus_id' => $estatusId,
                    'estatus_name' => $nombreEstatus,
                ]);
                return; // NO enviar correo genérico ni despachar otros jobs aquí (se hacen en el controlador)
            }

            // Omitir únicamente estatus de creación inicial
            if ($nombreEstatus && trim(mb_strtolower($nombreEstatus)) === trim(mb_strtolower('Requisición creada'))) {
                Log::info('estatusActualizado: skip por estatus creación', ['req' => $this->requisicion->id]);
                return;
            }

            // Si el estatus es 5 (OC generada), enviar correo(s) de OC creada para todas las OCs de la requisición al creador (user_email/email_user)
            if ((int)$estatusId === 5) {
                try {
                    $creatorEmail = $this->requisicion->user_email
                        ?? $this->requisicion->email_user
                        ?? optional($this->requisicion->user)->email
                        ?? config('mail.from.address');
                    $ordenes = OrdenCompra::where('requisicion_id', $this->requisicion->id)->get();
                    foreach ($ordenes as $oc) {
                        if ($oc) { OrdenCompraCreadaJob::dispatch($oc, $creatorEmail); }
                    }
                } catch (\Throwable $e) {
                    Log::warning('EstatusRequisicionActualizadoJob: fallo al despachar OC creada', ['req'=>$this->requisicion->id, 'err'=>$e->getMessage()]);
                }
            }

            $to = $this->userEmail
                ?? ($this->requisicion->email_user ?? null)
                ?? optional($this->requisicion->user)->email
                ?? env('REQUISICIONES_MAIL_TO', 'admin@example.com');

            if (!$to) {
                Log::warning('estatusActualizado: sin destinatario', ['req' => $this->requisicion->id]);
                return;
            }

            $gap = (float) env('MAIL_MIN_GAP_SECONDS', 0);
            if ($gap > 0) { usleep((int) round($gap * 1_000_000)); }

            Mail::to($to)->send(new EstatusRequisicionActualizado($this->requisicion, $this->estatus));
            Log::info("Correo de estatus enviado a: {$to} para requisición #{$this->requisicion->id}", [
                'estatus_id' => $estatusId,
                'estatus_name' => $nombreEstatus,
            ]);
        } catch (\Throwable $e) {
            Log::error("Error enviando correo para requisición #{$this->requisicion->id}: " . $e->getMessage());
        }
    }
}