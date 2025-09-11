<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrdenCompra extends Model
{
    use SoftDeletes;

    protected $table = 'orden_compras';

    protected $fillable = [
        'requisicion_id',
    ];

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class);
    }

    public function ordencompraProductos(): HasMany
    {
        return $this->hasMany(OrdenCompraProducto::class, 'orden_compras_id');
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'ordencompra_producto', 'orden_compras_id', 'producto_id')
            ->withPivot('id', 'proveedor_id', 'observaciones', 'date_oc', 'methods_oc', 'plazo_oc', 'order_oc')
            ->withTimestamps();
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    // 🔹 NUEVA RELACIÓN: Distribución de productos por centro de costo para la orden de compra
    public function centrosProductos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'ordencompra_centro_producto', 'orden_compra_id', 'producto_id')
            ->withPivot('centro_id', 'amount')
            ->withTimestamps();
    }

    // 🔹 RELACIÓN DIRECTA para acceder a la distribución centro-producto
    public function distribucionCentrosProductos(): HasMany
    {
        return $this->hasMany(OrdenCompraCentroProducto::class, 'orden_compra_id');
    }
}