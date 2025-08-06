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
        return $this->belongsToMany(Producto::class)
                   ->withPivot(['po_amount', 'proveedor_id']);
    }

    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class);
    }
}