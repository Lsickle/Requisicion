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
            // Preferir el email proporcionado, si no, usar el email guardado en la requisición o el usuario relacionado
            $to = $this->userEmail
                ?? ($this->requisicion->email_user ?? null)
                ?? optional($this->requisicion->user)->email
                ?? null;

            if ($to) {
                Mail::to($to)->send(new EstatusRequisicionActualizado($this->requisicion, $this->estatus));
                Log::info("Correo de estatus enviado a: {$to} para requisición #{$this->requisicion->id}");
            } else {
                Log::warning("No se encontró email destinatario para requisición #{$this->requisicion->id}. Enviando fallback a admin.");
                Mail::to('admin@empresa.com')->send(new EstatusRequisicionActualizado($this->requisicion, $this->estatus));
            }
        } catch (\Exception $e) {
            Log::error("Error enviando correo para requisición #{$this->requisicion->id}: " . $e->getMessage());
        }
    }
}