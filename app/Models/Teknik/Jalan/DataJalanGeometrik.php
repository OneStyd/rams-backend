<?php

namespace App\Models\Teknik\Jalan;

use Illuminate\Database\Eloquent\Model;

class DataJalanGeometrik extends Model
{
    protected $table = 'data_jalan_geometrik';

    protected $fillable = [
        'tahun',
        'uraian',
        'nilai',
        'id_leger_jalan',
    ];

    public function legerJalan()
    {
        return $this->belongsTo(\App\Models\Teknik\Jalan\LegerJalan::class, 'id_leger_jalan');
    }
}
