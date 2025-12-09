<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subcentro extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'subcentros';

    protected $fillable = [
        'name_subcentro',
        'centro_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function centro()
    {
        return $this->belongsTo(Centro::class, 'centro_id');
    }
}
