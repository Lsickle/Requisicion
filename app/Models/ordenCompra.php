<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrdenCompra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orden_compras';

    protected $fillable = [
        'requisicion_id',
        'observaciones',
        'date_oc',
        'methods_oc',
        'plazo_oc',
        'order_oc',
        'validation_hash',
    ];

    // Permitir cargar/guardar PDF en la orden (sin casts especiales)
    protected $casts = [
        // no class cast for binary blobs; store raw string/blob
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