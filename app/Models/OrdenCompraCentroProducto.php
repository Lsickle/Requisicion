<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraCentroProducto extends Model
{
    use SoftDeletes;

    protected $table = 'ordencompra_centro_producto';

    protected $fillable = [
        'orden_compra_id',
        'producto_id',
        'centro_id',
        'amount',
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function centro(): BelongsTo
    {
        return $this->belongsTo(Centro::class, 'centro_id');
    }
}