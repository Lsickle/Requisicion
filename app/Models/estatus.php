<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estatus extends Model
{
    use HasFactory;

    protected $table = 'estatus';

    protected $fillable = [
        'status_name',
        'status_date',
        'status_curso'
    ];

    public function requisiciones()
    {
        return $this->belongsToMany(Requisicion::class, 'estatusxrequisicion');
    }
}