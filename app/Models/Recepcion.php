<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recepcion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recepcion';

    protected $fillable = [
        'orden_compra_id',
        'producto_id',
        'cantidad',
        'cantidad_recibido',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // Relaciones
    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}