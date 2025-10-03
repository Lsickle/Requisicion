<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'user_id',
        'user_name',
        'reception_user_id',
        'reception_user',
    ];

    protected $casts = [
        'fecha' => 'date',
        'user_id' => 'integer',
        'reception_user_id' => 'integer',
    ];

    // Relaciones
    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}