<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'prov_name',
        'prov_descrip',
        'prov_nit',
        'prov_name_c',
        'prov_phone',
        'prov_adress',
        'prov_city'
    ];

    // Relación con productos (1 a muchos)
    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    // Relación con órdenes de compra (1 a muchos)
    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class);
    }
}