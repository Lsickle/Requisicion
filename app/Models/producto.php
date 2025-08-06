<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'id_proveedores',
        'name_produc',
        'stock_produc',
        'description_produc',
        'price_produc',
        'unit_produc'
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedores');
    }

    public function centros()
    {
        return $this->belongsToMany(Centro::class, 'productoxcentro')
                   ->withPivot('amount');
    }

    public function requisiciones()
    {
        return $this->belongsToMany(Requisicion::class, 'productoxrequisicion')
                   ->withPivot('pr_amount');
    }
    public function ordenCompras()
    {
        return $this->belongsToMany(ordenCompra::class );
    }
}