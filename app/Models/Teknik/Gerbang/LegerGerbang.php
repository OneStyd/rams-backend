<?php

namespace App\Models\Teknik\Gerbang;

use Illuminate\Database\Eloquent\Model;

class LegerGerbang extends Model
{
    protected $table = 'leger_gerbang';

    protected $fillable = [
        'leger_id',
        'kode_leger',
    ];

    public function leger()
    {
        return $this->hasOne(\App\Models\Leger::class, 'id', 'leger_id');
    }
}
