<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioBodega extends Model
{
    use HasFactory;

    protected $table = 'inventario_bodega';

    protected $fillable = [
        'bodega_id',
        'producto_id',
        'subcentro_id',
        'cantidad',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Centro::class, 'bodega_id');
    }

    public function subcentro(): BelongsTo
    {
        return $this->belongsTo(Subcentro::class, 'subcentro_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(InventarioMovimiento::class, 'inventario_bodega_id');
    }

    public function incrementar(int $cantidad, int $userId, ?string $comentario = null): self
    {
        $this->cantidad += $cantidad;
        $this->save();

        InventarioMovimiento::create([
            'inventario_bodega_id' => $this->id,
            'user_id' => $userId,
            'tipo' => 'entrada',
            'cantidad' => $cantidad,
            'comentario' => $comentario ?: 'Ingreso de inventario',
        ]);

        return $this;
    }

    public function decrementar(int $cantidad, int $userId, ?string $comentario = null): bool
    {
        if ($this->cantidad < $cantidad) {
            return false;
        }

        $this->cantidad = $this->cantidad - $cantidad;
        $this->save();
        $this->refresh();

        InventarioMovimiento::create([
            'inventario_bodega_id' => $this->id,
            'user_id' => $userId,
            'tipo' => 'salida',
            'cantidad' => $cantidad,
            'comentario' => $comentario ?: 'Retiro de inventario',
        ]);

        return true;
    }
}