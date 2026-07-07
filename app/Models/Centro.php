<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class Centro extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'centro';

    protected $fillable = [
        'name_centro'
    ];

    public static function obtenerCentros()
    {
        return DB::table('centro as centros')
            ->select('id', 'name_centro')
            ->orderBy('name_centro')
            ->get();
    }


    # para requisición de compras
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'centro_ordencompra')
            ->withPivot('rc_amount')
            ->withTimestamps();
    }

    public function requisiciones()
    {
        return $this->belongsToMany(Requisicion::class)
            ->withPivot('rc_amount');
    }

    public function subcentros()
    {
        return $this->hasMany(Subcentro::class, 'centro_id');
    }
}
