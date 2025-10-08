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
            // borrar luego de mailtrap
            $nombreEstatus = optional($this->estatus->estatusRelation)->status_name;
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
            Log::info("Correo de estatus enviado a: {$to} para requisición #{$this->requisicion->id}");
        } catch (\Throwable $e) {
            Log::error("Error enviando correo para requisición #{$this->requisicion->id}: " . $e->getMessage());
            // No re-lanzar para no fallar la petición
        }
    }
}