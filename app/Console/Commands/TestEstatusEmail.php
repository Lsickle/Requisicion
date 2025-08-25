<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use App\Models\Estatus;
use App\Jobs\EstatusRequisicionCambiadoJob;
use Illuminate\Support\Facades\Log;

class TestEstatusEmail extends Command
{
    protected $signature = 'test:estatus-email {requisicion_id}';
    protected $description = 'Probar el envío de correo para cambio de estatus';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $requisicionId = $this->argument('requisicion_id');
        
        try {
            // Buscar la requisición
            $requisicion = Requisicion::findOrFail($requisicionId);
            
            // Crear un estatus de prueba
            $estatus = Estatus::first();
            
            if (!$estatus) {
                $estatus = Estatus::create([
                    'status_name' => 'Prueba',
                    'description' => 'Estatus de prueba'
                ]);
            }
            
            // Crear un registro de estatus_requisicion de prueba
            $estatusRequisicion = Estatus_Requisicion::create([
                'requisicion_id' => $requisicion->id,
                'estatus_id' => $estatus->id,
                'estatus' => 1,
                'date_update' => now(),
                'comentario' => 'Este es un correo de prueba para verificar el envío de notificaciones de cambio de estatus.'
            ]);
            
            // Despachar el job
            EstatusRequisicionCambiadoJob::dispatch($requisicion, $estatusRequisicion, 'Usuario de Prueba');
            
            $this->info('Correo de prueba despachado correctamente para la requisición #' . $requisicion->id);
            $this->info('Verifica la cola con: php artisan queue:work');
            $this->info('O revisa los logs en storage/logs/laravel.log');
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Error en test:estatus-email: ' . $e->getMessage());
        }
    }
}