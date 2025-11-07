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
use App\Mail\EstatusRequisicionActualizado as EstatusRequisicionActualizadoMail;

class EstatusRequisicionActualizadoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $requisicion;
    public $estatus;
    public $userEmail;

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
        $this->afterCommit = true; // usar propiedad del trait
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $estatusId = (int)($this->estatus->estatus_id ?? 0);
        if (in_array($estatusId, [2,3,4], true)) { return; } // evitar duplicados con correos de aprobación
        if (empty($this->userEmail) || !filter_var($this->userEmail, FILTER_VALIDATE_EMAIL)) { return; }

        try {
            Mail::to($this->userEmail)->send(new EstatusRequisicionActualizadoMail($this->requisicion, $this->estatus));
        } catch (\Throwable $e) { /* silencio para evitar 500 */ }
    }
}