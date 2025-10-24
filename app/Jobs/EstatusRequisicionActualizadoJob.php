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
use App\Jobs\RequisicionRechazadaJob;
use App\Jobs\RequisicionAprobadaFinalJob;

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
            $estatusId = (int) ($this->estatus->estatus_id ?? 0);
            $nombreEstatus = optional($this->estatus->estatusRelation)->status_name;

            // Resolver estatus activo real desde BD (en caso de que el modelo recibido no sea el actual)
            try {
                $activeId = DB::table('estatus_requisicion')
                    ->where('requisicion_id', $this->requisicion->id)
                    ->where('estatus', 1)
                    ->orderByDesc('id')
                    ->value('estatus_id');
                if (!is_null($activeId)) { $estatusId = (int)$activeId; }
            } catch (\Throwable $e) { /* noop */ }

            // Normalizar nombre para comparaciones robustas
            $norm = function($s){
                $s = mb_strtolower((string)$s, 'UTF-8');
                $from = ['á','é','í','ó','ú','ñ']; $to = ['a','e','i','o','u','n'];
                return str_replace($from, $to, $s);
            };
            $nameNorm = $norm($nombreEstatus);

            // Omitir envío para estatus no notificables (por ID o por nombre) y despachar jobs específicos
            $skipIds = [11, 12, 9, 13, 4];
            $skipByName = (
                strpos($nameNorm, 'corregir') !== false ||
                strpos($nameNorm, 'rechaz') !== false ||
                strpos($nameNorm, 'aprobado por financiera') !== false ||
                strpos($nameNorm, 'movimiento parcial') !== false ||
                strpos($nameNorm, 'solo se ha entregado') !== false
            );
            if (in_array($estatusId, $skipIds, true) || $skipByName) {
                Log::info('estatusActualizado: skip por estatus no notificable', [
                    'req' => $this->requisicion->id,
                    'estatus_id' => $estatusId,
                    'estatus_name' => $nombreEstatus,
                ]);
                // Rechazos: 11 (corregir), 9 (rechazo financiera), 13 (rechazo gerencia)
                if (in_array($estatusId, [11, 9, 13], true)) {
                    try { RequisicionRechazadaJob::dispatch($this->requisicion, $estatusId, $this->estatus->comentario ?? null); }
                    catch (\Throwable $e) { Log::warning('No se pudo despachar RequisicionRechazadaJob desde EstatusActualizado', ['req'=>$this->requisicion->id, 'err'=>$e->getMessage()]); }
                }
                // Aprobado final por financiera: 4
                if ($estatusId === 4) {
                    try { RequisicionAprobadaFinalJob::dispatch($this->requisicion); }
                    catch (\Throwable $e) { Log::warning('No se pudo despachar RequisicionAprobadaFinalJob desde EstatusActualizado', ['req'=>$this->requisicion->id, 'err'=>$e->getMessage()]); }
                }
                return;
            }

            // Omitir 'Requisición creada'
            if ($nombreEstatus && trim(mb_strtolower($nombreEstatus)) === trim(mb_strtolower('Requisición creada'))) {
                Log::info('estatusActualizado: skip por estatus Requisición creada', ['req' => $this->requisicion->id]);
                return;
            }

            // Preferir el email proporcionado, si no, usar el email guardado en la requisición o el usuario relacionado
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