<?php

namespace App\Jobs;

use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class RequisicionCreadaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $requisicion;
    public $nombreSolicitante;

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
    public function handle()
    {
        $requisicion = $this->requisicion;

        // Enviar correo usando plantilla
        Mail::send('emails.requisicion_creada', [
            'requisicion' => $requisicion,
            'nombreSolicitante' => $this->nombreSolicitante
        ], function ($message) use ($requisicion) {
            $message->to('pardomoyasegio@gmail.com') // Cambiar al correo real
                    ->subject("Nueva Requisición Creada #{$requisicion->id}");
        });
    }
}
