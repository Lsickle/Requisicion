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

class EstatusRequisicionActualizadoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $requisicion;
    protected $estatus;

    public function __construct(Requisicion $requisicion, Estatus_Requisicion $estatus)
    {
        $this->requisicion = $requisicion;
        $this->estatus = $estatus;
    }

    public function handle()
    {
        Mail::send(new EstatusRequisicionActualizado($this->requisicion, $this->estatus));
    }
}