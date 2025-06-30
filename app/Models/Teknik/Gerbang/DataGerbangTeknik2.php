<?php

namespace App\Models\Teknik\Gerbang;

use Illuminate\Database\Eloquent\Model;

class DataGerbangTeknik2 extends Model
{
    protected $table = 'data_gerbang_teknik2';

    protected $fillable = [
        'tahun',
        'uraian',
        'jumlah',
        'panjang',
        'kondisi',
        'id_leger_gerbang',
    ];

    public function legerGerbang()
    {
        return $this->belongsTo(\App\Models\Teknik\Gerbang\LegerGerbang::class, 'id_leger_gerbang');
    }
}
