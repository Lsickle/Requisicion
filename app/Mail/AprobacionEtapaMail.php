<?php

namespace App\Mail;

use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AprobacionEtapaMail extends Mailable
{
    use Queueable, SerializesModels;

    public Requisicion $requisicion;
    public Estatus_Requisicion $estatus;
    public string $stageKey;
    public string $subjectLine;
    public string $mensajePrincipal;
    public string $panelUrl;
    public string $detalleUrl;

    public function __construct(Requisicion $requisicion, Estatus_Requisicion $estatus, string $stageKey, string $subjectLine, string $mensajePrincipal, string $panelUrl, string $detalleUrl)
    {
        $this->requisicion = $requisicion;
        $this->estatus = $estatus;
        $this->stageKey = $stageKey;
        $this->subjectLine = $subjectLine;
        $this->mensajePrincipal = $mensajePrincipal;
        $this->panelUrl = $panelUrl;
        $this->detalleUrl = $detalleUrl;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.aprobacion_etapa')
            ->with([
                'requisicion' => $this->requisicion,
                'estatus' => $this->estatus,
                'stageKey' => $this->stageKey,
                'mensajePrincipal' => $this->mensajePrincipal,
                'panelUrl' => $this->panelUrl,
                'detalleUrl' => $this->detalleUrl,
            ]);
    }
}
