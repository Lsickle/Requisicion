<?php

namespace App\Jobs;

use App\Mail\EstatusRequisicionActualizado;
use App\Mail\RequisicionCompletada; // nuevo
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
        // Enviar correo siempre que el email sea válido
        if (empty($this->userEmail) || !filter_var($this->userEmail, FILTER_VALIDATE_EMAIL)) { return; }
        $estatusId = (int)($this->estatus->estatus_id ?? 0);
        try {
            if ($estatusId === 10) {
                Mail::to($this->userEmail)->send(new RequisicionCompletada($this->requisicion, $this->estatus));
                return;
            }
            Mail::to($this->userEmail)->send(new EstatusRequisicionActualizadoMail($this->requisicion, $this->estatus));
        } catch (\Throwable $e) { /* silencio */ }
    }
}