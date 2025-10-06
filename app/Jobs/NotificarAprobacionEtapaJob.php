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
use App\Mail\AprobacionEtapaMail;

class NotificarAprobacionEtapaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $requisicion;
    public $estatus;
    public $stageKey;
    public $destinatarios;
    public $subject;
    public $mensajePrincipal;
    public $panelUrl;
    public $detalleUrl;

    public function __construct(Requisicion $requisicion, Estatus_Requisicion $estatus, string $stageKey, array $destinatarios, string $subject, string $mensajePrincipal, string $panelUrl, string $detalleUrl)
    {
        $this->requisicion = $requisicion;
        $this->estatus = $estatus;
        $this->stageKey = $stageKey;
        $this->destinatarios = $destinatarios;
        $this->subject = $subject;
        $this->mensajePrincipal = $mensajePrincipal;
        $this->panelUrl = $panelUrl;
        $this->detalleUrl = $detalleUrl;
    }

    public function handle(): void
    {
        $mailable = new AprobacionEtapaMail(
            $this->requisicion,
            $this->estatus,
            $this->stageKey,
            $this->subject,
            $this->mensajePrincipal,
            $this->panelUrl,
            $this->detalleUrl
        );
        Mail::to($this->destinatarios)->send($mailable);
    }
}
