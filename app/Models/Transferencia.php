<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transferencia extends Model
{
    protected $table = 'transferencias';

    protected $fillable = [
        'bodega_origen_id',
        'bodega_destino_id',
        'producto_id',
        'cantidad',
        'observaciones',
        'nombre_origen',
        'cedula_origen',
        'firma_origen',
        'nombre_destino',
        'cedula_destino',
        'firma_destino',
        'estado',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    public function bodegaOrigen(): BelongsTo
    {
        return $this->belongsTo(Centro::class, 'bodega_origen_id');
    }

    public function bodegaDestino(): BelongsTo
    {
        return $this->belongsTo(Centro::class, 'bodega_destino_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}