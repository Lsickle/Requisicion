<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entrega extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'entrega';

    protected $fillable = [
        'requisicion_id',
        'producto_id',
        'cantidad',
        'cantidad_recibido',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // Relaciones
    public function requisicion()
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}