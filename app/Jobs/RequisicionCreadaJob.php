<?php

namespace App\Jobs;

use App\Mail\RequisicionCreada;
use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RequisicionCreadaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $requisicion;

    public function __construct(Requisicion $requisicion)
    {
        $this->requisicion = $requisicion;
    }

    public function handle()
    {
        Log::info('RequisicionCreadaJob iniciado para requisición #' . $this->requisicion->id);

        try {
            Log::debug('Intentando enviar correo...');

            // Envía el correo
            Mail::to('pardomoyasegio@gmail.com')->send(new RequisicionCreada($this->requisicion));

            Log::info('Correo enviado exitosamente para requisición #' . $this->requisicion->id);
        } catch (\Exception $e) {
            Log::error('Error en RequisicionCreadaJob: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            // Re-lanza la excepción para que Laravel la maneje
            throw $e;
        }
    }
}