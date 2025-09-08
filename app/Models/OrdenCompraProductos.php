<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdencompraProducto extends Model
{
    protected $table = 'ordencompra_producto';
    
    protected $fillable = [
        'producto_id',
        'orden_compras_id',
        'producto_requisicion_id', // Mantenemos el campo pero sin relación
        'proveedor_seleccionado',
        'observaciones',
        'date_oc',
        'methods_oc',
        'plazo_oc',
        'order_oc',
        'cantidad',
        'precio_unitario'
    ];

    protected $casts = [
        'date_oc' => 'date',
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the producto that owns the OrdencompraProducto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Get the ordenCompra that owns the OrdencompraProducto
     */
    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compras_id');
    }

    /**
     * Get the proveedor that owns the OrdencompraProducto
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_seleccionado');
    }

    /**
     * Get the requisicion through ordenCompra
     */
    public function requisicion()
    {
        return $this->hasOneThrough(
            Requisicion::class,
            OrdenCompra::class,
            'id', // Foreign key on OrdenCompra table
            'id', // Foreign key on Requisicion table
            'orden_compras_id', // Local key on OrdencompraProducto table
            'requisicion_id' // Local key on OrdenCompra table
        );
    }

    /**
     * Calculate the total for this line item
     */
    public function getTotalAttribute(): float
    {
        return $this->cantidad * $this->precio_unitario;
    }

    /**
     * Get the product name through producto relationship
     */
    public function getNombreProductoAttribute(): string
    {
        return $this->producto->name_produc ?? 'Producto no encontrado';
    }

    /**
     * Get the provider name through proveedor relationship
     */
    public function getNombreProveedorAttribute(): string
    {
        return $this->proveedor->prov_name ?? 'Proveedor no encontrado';
    }

    /**
     * Scope a query to only include records for a specific orden compra.
     */
    public function scopeForOrdenCompra($query, $ordenCompraId)
    {
        return $query->where('orden_compras_id', $ordenCompraId);
    }

    /**
     * Scope a query to only include records for a specific producto.
     */
    public function scopeForProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    /**
     * Scope a query to only include records for a specific proveedor.
     */
    public function scopeForProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_seleccionado', $proveedorId);
    }

    /**
     * Scope a query to only include records for a specific requisicion.
     */
    public function scopeForRequisicion($query, $requisicionId)
    {
        return $query->whereHas('ordenCompra', function($q) use ($requisicionId) {
            $q->where('requisicion_id', $requisicionId);
        });
    }

    /**
     * Scope a query to only include records with a specific producto_requisicion_id.
     */
    public function scopeForProductoRequisicion($query, $productoRequisicionId)
    {
        return $query->where('producto_requisicion_id', $productoRequisicionId);
    }
}