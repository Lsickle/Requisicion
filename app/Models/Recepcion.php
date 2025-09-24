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
        'reception_user',
        'fecha'
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad' => 'integer',
        'cantidad_recibido' => 'integer'
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            // Si no se proporciona reception_user, tomarlo de la session (usuario proveniente del API)
            if (empty($model->reception_user)) {
                $model->reception_user = session('user.name') ?? session('user.email') ?? session('user.id') ?? 'unknown';
            }
            // Si no hay fecha, asignar hoy
            if (empty($model->fecha)) {
                $model->fecha = now()->toDateString();
            }
        });
    }

    // Relaciones mínimas
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function ordenCompra()
    {
        return $this->belongsTo(\App\Models\OrdenCompra::class, 'orden_compra_id');
    }
}