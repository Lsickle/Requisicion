<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraProducto extends Model
{
    protected $table = 'ordencompra_producto';

    protected $fillable = [
        'producto_id',
        'orden_compras_id',
        'producto_requisicion_id',
        'proveedor_seleccionado',
        'observaciones',
        'date_oc',
        'methods_oc',
        'plazo_oc',
        'order_oc'
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compras_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}