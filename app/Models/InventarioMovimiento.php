<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioMovimiento extends Model
{
    use HasFactory;

    protected $table = 'inventario_movimiento';

    protected $fillable = [
        'inventario_bodega_id',
        'user_id',
        'tipo',
        'cantidad',
        'comentario',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    public function inventarioBodega(): BelongsTo
    {
        return $this->belongsTo(InventarioBodega::class, 'inventario_bodega_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeEntradas($query)
    {
        return $query->where('tipo', 'entrada');
    }

    public function scopeSalidas($query)
    {
        return $query->where('tipo', 'salida');
    }

    public function scopeEliminaciones($query)
    {
        return $query->where('tipo', 'eliminacion');
    }

    public function scopeAjustes($query)
    {
        return $query->where('tipo', 'ajuste');
    }

    public function scopePorBodega($query, int $bodegaId)
    {
        return $query->whereHas('inventarioBodega', function ($q) use ($bodegaId) {
            $q->where('bodega_id', $bodegaId);
        });
    }
}