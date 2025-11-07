<?php

namespace App\Jobs;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\OrdenCompraCreada as OrdenCompraCreadaMailable;

class OrdenCompraCreadaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orden;
    protected ?string $email;

    public function __construct(OrdenCompra $orden, ?string $email = null)
    {
        $this->orden = $orden;
        $this->email = $email; // email del usuario en sesión
    }

    public function handle(): void
    {
        try {
            $orden = $this->orden->fresh(['ordencompraProductos.proveedor', 'requisicion']);
            $orderCode = $orden->order_oc ?: ('OC-' . $orden->id);

            // Determinar destinatario: sesión o fallback de configuración
            $to = $this->email;
            if (empty($to)) {
                $to = config('mail.fallback_to')
                    ?? config('mail.admin_to')
                    ?? config('mail.from.address');
            }
            if (empty($to)) {
                Log::warning('OrdenCompraCreadaJob: sin destinatario válido, se omite envío.', ['oc' => $orden->id, 'order' => $orderCode]);
                return;
            }

            Mail::to($to)->send(new OrdenCompraCreadaMailable($orden));
            Log::info('OrdenCompraCreadaJob enviado', ['oc' => $orden->id, 'to' => $to, 'order' => $orderCode]);
        } catch (\Throwable $e) {
            Log::error('OrdenCompraCreadaJob error: ' . $e->getMessage());
        }
    }
}