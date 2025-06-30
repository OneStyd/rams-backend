<?php

namespace App\Models\Teknik\Gerbang;

use Illuminate\Database\Eloquent\Model;

class DataGerbangTeknik1 extends Model
{
    protected $table = 'data_gerbang_teknik1';

    protected $fillable = [
        'tahun',
        'uraian',
        'jumlah',
        'luas_lahan',
        'luas_bangunan',
        'kondisi',
        'id_leger_gerbang',
    ];

    public function legerGerbang()
    {
        return $this->belongsTo(\App\Models\Teknik\Gerbang\LegerGerbang::class, 'id_leger_gerbang');
    }
}
