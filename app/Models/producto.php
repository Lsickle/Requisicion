<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'proveedor_id', // este es el campo real
        'categoria_produc',
        'name_produc',
        'stock_produc',
        'description_produc',
        'price_produc',
        'unit_produc'
    ];

    protected static function booted()
    {
        static::addGlobalScope('orderById', function (Builder $builder) {
            $builder->orderBy('id', 'asc');
        });
    }

    // Relación con proveedor
    public function proveedor()
    {
        // Usamos la clave correcta
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    // Relación con órdenes de compra
    public function ordenesCompra()
    {
        return $this->belongsToMany(OrdenCompra::class, 'ordencompra_producto')
            ->withPivot('id', 'po_amount', 'precio_unitario', 'observaciones') 
            ->withTimestamps();
    }

    // Relación con centros a través de centro_producto (tabla pivot)   
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
}
