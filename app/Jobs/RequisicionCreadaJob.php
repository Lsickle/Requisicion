<?php

namespace App\Jobs;

use App\Mail\RequisicionCreada;
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

    protected $requisicion;

    public function __construct(Requisicion $requisicion)
    {
        $this->requisicion = $requisicion;
    }

    public function handle()
    {
        Mail::send(new RequisicionCreada($this->requisicion));
    }
}