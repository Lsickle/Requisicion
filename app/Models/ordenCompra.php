<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenCompra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orden_compras';

    protected $fillable = [
        'requisicion_id',
        'proveedor_id',
        'observaciones',
        'date_oc',
        'methods_oc',
        'plazo_oc',
        'order_oc',
    ];

    protected $dates = [
        'date_oc',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Relación con requisición
    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }

    // Relación con proveedor
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    // Relación con los productos a través de la tabla pivot ordencompra_producto
    public function ordencompraProductos(): HasMany
    {
        return $this->hasMany(OrdencompraProducto::class, 'orden_compras_id');
    }

    // Relación muchos a muchos con productos a través de la tabla pivot
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'ordencompra_producto', 'orden_compras_id', 'producto_id')
            ->withPivot([
                'producto_requisicion_id',
                'proveedor_seleccionado',
                'observaciones',
                'date_oc',
                'methods_oc',
                'plazo_oc',
                'order_oc',
                'cantidad',
                'precio_unitario'
            ])
            ->withTimestamps();
    }

    // Obtener todos los proveedores de los productos en esta orden
    public function proveedoresProductos()
    {
        return $this->hasManyThrough(
            Proveedor::class,
            OrdencompraProducto::class,
            'orden_compras_id',
            'id',
            'id',
            'proveedor_seleccionado'
        )->distinct();
    }

    // Calcular el total de la orden de compra
    public function getTotalAttribute(): float
    {
        return $this->ordencompraProductos->sum(function ($item) {
            return $item->cantidad * $item->precio_unitario;
        });
    }

    // Obtener la cantidad total de items en la orden
    public function getTotalItemsAttribute(): int
    {
        return $this->ordencompraProductos->sum('cantidad');
    }

    // Obtener la cantidad de productos diferentes
    public function getCantidadProductosAttribute(): int
    {
        return $this->ordencompraProductos->count();
    }
}