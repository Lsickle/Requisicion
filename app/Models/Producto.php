<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_produc',
        'name_produc',
        'stock_produc',
        'description_produc',
        'iva',
        'unit_produc'
    ];

    protected $casts = [
        'iva' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::addGlobalScope('orderById', function (Builder $builder) {
            $builder->orderBy('id', 'asc');
        });
    }

    // Relación con órdenes de compra
    public function ordenesCompra()
    {
        return $this->belongsToMany(OrdenCompra::class, 'ordencompra_producto')
            ->withPivot('id', 'po_amount', 'precio_unitario', 'observaciones')
            ->withTimestamps();
    }

    # para requisición de compras
    public function centrosInventario()
    {
        return $this->belongsToMany(Centro::class, 'centro_producto')
            ->withPivot('amount')
            ->withTimestamps();
    }

    # para orden de compra
    public function centrosOrdenCompra()
    {
        return $this->belongsToMany(Centro::class, 'centro_ordencompra', 'producto_id', 'centro_id')
            ->withPivot('rc_amount')
            ->withTimestamps();
    }

    public function centros(): BelongsToMany
    {
        return $this->belongsToMany(Centro::class, 'centro_producto')
            ->withPivot('amount')
            ->withTimestamps();
    }

    // Centros por requisición (pivot incluye requisicion_id)
    public function centrosRequisicion()
    {
        return $this->belongsToMany(Centro::class, 'centro_producto')
            ->withPivot('amount', 'requisicion_id')
            ->withTimestamps();
    }

    // Relación hacia productoxproveedor (1:n)
    public function productoxproveedor()
    {
        return $this->hasMany(Productoxproveedor::class, 'producto_id');
    }

    // Relación con requisiciones (tabla pivot producto_requisicion)
    public function requisiciones()
    {
        return $this->belongsToMany(Requisicion::class, 'producto_requisicion', 'id_producto', 'id_requisicion')
            ->withPivot('pr_amount', 'id_productoxproveedor')
            ->withTimestamps();
    }
}
