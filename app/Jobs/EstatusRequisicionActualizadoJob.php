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
            if ($this->userEmail) {
                Mail::to($this->userEmail)->send(new EstatusRequisicionActualizado($this->requisicion, $this->estatus));
                Log::info("✅ Correo de estatus enviado a: {$this->userEmail} para requisición #{$this->requisicion->id}");
            } else {
                Log::warning("⚠️ No se proporcionó email para usuario {$this->requisicion->user_id}, requisición #{$this->requisicion->id}");
                
                // Fallback: enviar a correo administrativo
                Mail::to('admin@empresa.com')->send(new EstatusRequisicionActualizado($this->requisicion, $this->estatus));
            }
        } catch (\Exception $e) {
            Log::error("❌ Error enviando correo para requisición #{$this->requisicion->id}: " . $e->getMessage());
        }
    }
}