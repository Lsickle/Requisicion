<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orden_compras';

    protected $fillable = [
        'proveedor_id',
        'date_oc',
        'methods_oc',
        'plazo_oc',
        'order_oc',
        'observaciones',
        'estado'
    ];

    // Relación con proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // Relación con productos (a través de la tabla pivot)
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'ordencompra_producto')
                   ->withPivot('po_amount')
                   ->withTimestamps();
    }
}