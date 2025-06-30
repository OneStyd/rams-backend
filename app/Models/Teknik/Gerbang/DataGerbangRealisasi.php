<?php

namespace App\Models\Teknik\Gerbang;

use Illuminate\Database\Eloquent\Model;

class DataGerbangRealisasi extends Model
{
    protected $table = 'data_gerbang_realisasi';

    protected $fillable = [
        'tahun',
        'kegiatan',
        'penyedia_jasa',
        'cacah',
        'biaya',
        'sumber_dana',
        'id_leger_gerbang',
    ];

    public function legerGerbang()
    {
        return $this->belongsTo(\App\Models\Teknik\Gerbang\LegerGerbang::class, 'id_leger_gerbang');
    }
}
