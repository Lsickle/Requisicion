<?php

namespace App\Jobs;

use App\Mail\NuevoProductoSolicitado;
use App\Models\Nuevo_Producto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NuevoProductoSolicitadoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $producto;

    public function __construct(Nuevo_Producto $producto)
    {
        $this->producto = $producto;
    }

    public function handle()
    {
        Log::info('NuevoProductoSolicitadoJob iniciado para producto #' . $this->producto->id);

        try {
            Log::debug('Intentando enviar correo...');

            // Envía el correo
            Mail::to('pardomoyasegio@gmail.com')->send(new NuevoProductoSolicitado($this->producto));

            Log::info('Correo enviado exitosamente para producto #' . $this->producto->id);
        } catch (\Exception $e) {
            Log::error('Error en NuevoProductoSolicitadoJob: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            // Re-lanza la excepción para que Laravel la maneje
            throw $e;
        }
    }
}