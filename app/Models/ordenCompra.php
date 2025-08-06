<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenCompra extends Model
{
    use HasFactory;

    protected $table = 'orden_compras';

    protected $fillable = [
        'date_oc',
        'methods_oc',
        'plazo_oc',
        'order_oc'
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'ordencompra_producto', 'orden_compra_id', 'requisicion_producto_id')
                   ->withPivot(['po_amount', 'proveedor_id']);
    }

    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class, 'ordencompra_producto', 'orden_compra_id', 'proveedor_id');
    }
}