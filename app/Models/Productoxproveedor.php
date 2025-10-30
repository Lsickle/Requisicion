<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Productoxproveedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'productoxproveedor';

    protected $fillable = [
        'producto_id',
        'proveedor_id',
        'price_produc',
        'moneda'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}
