<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orden_compras';

    protected $fillable = [
        'requisicion_id',
        'oc_user',
        'observaciones',
        'date_oc',
        'order_oc',
        'validation_hash',
        'fecha_estimada_recepcion',
    ];

    // Permitir cargar/guardar PDF en la orden (sin casts especiales)
    protected $casts = [
        'date_oc' => 'date',
        'fecha_estimada_recepcion' => 'date',
    ];

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }

    public function ordencompraProductos(): HasMany
    {
        return $this->hasMany(OrdenCompraProducto::class, 'orden_compras_id');
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'ordencompra_producto', 'orden_compras_id', 'producto_id')
            ->withPivot('id', 'proveedor_id', 'total')
            ->withTimestamps();
    }

    // RELACIÓN DIRECTA para acceder a la distribución centro-producto
    public function distribucionCentrosProductos(): HasMany
    {
        return $this->hasMany(OrdenCompraCentroProducto::class, 'orden_compra_id');
    }

    // NOTE: PDFs are no longer stored in the database to save space; only a validation_hash is kept.
}
