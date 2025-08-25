<?php

namespace App\Jobs;

use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\EstatusRequisicionCambiado;

class EstatusRequisicionCambiadoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $requisicion;
    public $estatusRequisicion;
    public $nombreUsuario;

    /**
     * Crear una nueva instancia del Job
     *
     * @param Requisicion $requisicion
     * @param Estatus_Requisicion $estatusRequisicion
     * @param string $nombreUsuario
     */
    public function __construct(Requisicion $requisicion, Estatus_Requisicion $estatusRequisicion, $nombreUsuario = null)
    {
        $this->requisicion = $requisicion;
        $this->estatusRequisicion = $estatusRequisicion;
        $this->nombreUsuario = $nombreUsuario;
    }

    /**
     * Ejecutar el Job
     *
     * @return void
     */
    public function handle()
    {
        // Enviar correo usando el Mailable
        Mail::to('pardomoyasegio@gmail.com')
            ->send(new EstatusRequisicionCambiado(
                $this->requisicion, 
                $this->estatusRequisicion,
                $this->nombreUsuario
            ));
    }
}