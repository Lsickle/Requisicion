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

    // Reintentos y backoff si se usa queue asincrónica
    public $tries = 3;
    public function backoff(): array { return [10, 30, 60]; }

    /**
     * Crear una nueva instancia del Job
     *
     * @param Requisicion $requisicion
     * @param string $nombreSolicitante
     */
    public function __construct(Requisicion $requisicion, string $nombreSolicitante)
    {
        $this->requisicion = $requisicion;
        $this->nombreSolicitante = $nombreSolicitante;
    }

    /**
     * Ejecutar el Job
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $to = $this->requisicion->email_user ?: env('REQUISICIONES_MAIL_TO', 'admin@example.com');
            if (!$to) {
                Log::warning('RequisicionCreadaJob: sin destinatario', ['req' => $this->requisicion->id]);
                return;
            }

            // Throttle simple para evitar límites del proveedor
            $gap = (float) env('MAIL_MIN_GAP_SECONDS', 0);
            if ($gap > 0) {
                $us = (int) round($gap * 1_000_000);
                usleep($us);
            }

            Log::info('RequisicionCreadaJob enviando correo', ['req' => $this->requisicion->id, 'to' => $to]);
            Mail::to($to)->send(new RequisicionCreadaMailable($this->requisicion));
        } catch (\Throwable $e) {
            Log::error('RequisicionCreadaJob error al enviar', ['req' => $this->requisicion->id, 'msg' => $e->getMessage()]);
            //throw $e; // permitir reintentos si hay queue asincrónica
        }
    }
}
