<?php

namespace App\Models\Teknik\Kantor;

use Illuminate\Database\Eloquent\Model;

class LegerKantor extends Model
{
    protected $table = 'leger_kantor';

    protected $fillable = [
        'leger_id',
        'kode_leger',
    ];

    public function leger()
    {
        return $this->hasOne(\App\Models\Leger::class, 'id', 'leger_id');
    }

}
