<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompraProducto extends Model
{
    use SoftDeletes;

    protected $table = 'ordencompra_producto';

    protected $fillable = [
        'producto_id',
        'orden_compras_id',
        'proveedor_id',
        'observaciones',
        'date_oc',
        'methods_oc',
        'plazo_oc',
        'order_oc'
    ];

    protected $dates = [
        'date_oc',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compras_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }
}
