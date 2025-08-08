<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estatus_Requisicion extends Model
{
    use HasFactory;

    protected $table = 'estatus_requisicion';

    protected $fillable = [
        'estatus_id',
        'requisicion_id',
        'estatus',
        'date_update',
        'created_at',
        'updated_at',
    ];

    public function estatus()
    {
        return $this->belongsTo(Estatus::class, 'estatus_id');
    }

    public function requisicion()
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d/m/Y H:i');
    }
}
