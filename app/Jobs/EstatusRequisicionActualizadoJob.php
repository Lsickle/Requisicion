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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EstatusRequisicionActualizadoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $requisicion;
    protected $estatus;

    /**
     * Create a new job instance.
     */
    public function __construct(Requisicion $requisicion, Estatus_Requisicion $estatus)
    {
        $this->requisicion = $requisicion;
        $this->estatus = $estatus;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Obtener el email del usuario que creó la requisición desde la API externa
            $userEmail = $this->getUserEmailFromApi($this->requisicion->user_id);
            
            if ($userEmail) {
                Mail::to($userEmail)->send(new EstatusRequisicionActualizado($this->requisicion, $this->estatus));
                Log::info("✅ Correo de estatus enviado a: {$userEmail} para requisición #{$this->requisicion->id}");
            } else {
                Log::warning("⚠️ No se pudo obtener el email del usuario {$this->requisicion->user_id} para requisición #{$this->requisicion->id}");
                
                // Opcional: Enviar a correo de administración para notificar el error
                // Mail::to('admin@empresa.com')->send(new ErrorEmail(...));
            }
        } catch (\Exception $e) {
            Log::error("❌ Error enviando correo para requisición #{$this->requisicion->id}: " . $e->getMessage());
        }
    }

    /**
     * Obtener el email del usuario desde la API externa usando el user_id
     */
    private function getUserEmailFromApi($userId)
    {
        try {
            $apiUrl = env('VPL_CORE') . "/api/users/{$userId}";
            
            Log::info("🌐 Consultando API para usuario ID: {$userId} - URL: {$apiUrl}");
            
            $response = Http::withoutVerifying()
                ->timeout(15) // Timeout de 15 segundos
                ->retry(2, 100) // Reintentar 2 veces con 100ms de espera
                ->get($apiUrl);

            if ($response->successful()) {
                $userData = $response->json();
                $email = $userData['email'] ?? null;
                
                Log::info("📧 Email obtenido para usuario {$userId}: {$email}");
                return $email;
            } else {
                Log::error("🔴 Error API al obtener usuario {$userId}: Status " . $response->status());
                return null;
            }
        } catch (\Throwable $e) {
            Log::error("🔴 Exception obteniendo email del usuario {$userId}: " . $e->getMessage());
            return null;
        }
    }
}