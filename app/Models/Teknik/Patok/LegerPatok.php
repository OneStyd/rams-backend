<?php

namespace App\Models\Teknik\Patok;

use Illuminate\Database\Eloquent\Model;

class LegerPatok extends Model
{
    protected $table = 'leger_patok';

    protected $fillable = [
        'leger_id',
        'kode_leger',
        'data_patok_id',
    ];

    // Relasi ke tabel leger
    public function leger()
    {
        return $this->belongsTo(\App\Models\Leger::class, 'leger_id', 'id');
    }

    // Relasi ke data patok
    public function dataPatok()
    {
        return $this->belongsTo(DataPatok::class, 'data_patok_id', 'id');
    }
}
