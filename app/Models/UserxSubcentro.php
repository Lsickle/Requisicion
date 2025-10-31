<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserxSubcentro extends Model
{
    use HasFactory;

    protected $table = 'userxsubcentro';

    protected $fillable = [
        'email_user',
        'subcentro_id',
    ];

    public function subcentro()
    {
        return $this->belongsTo(Subcentro::class, 'subcentro_id');
    }
}
