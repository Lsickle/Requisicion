<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orden_compras';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'ordencompra_producto')
            ->withPivot([
                'po_amount',
                'precio_unitario',
                'observaciones',
                'date_oc',
                'methods_oc',
                'plazo_oc',
                'order_oc'
            ])
            ->withTimestamps();
    }

    public function requisicion()
    {
        return $this->belongsTo(Requisicion::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

}
