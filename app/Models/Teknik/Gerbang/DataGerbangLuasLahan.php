<?php

namespace App\Models\Teknik\Gerbang;

use Illuminate\Database\Eloquent\Model;

class DataGerbangLuasLahan extends Model
{
    protected $table = 'data_gerbang_luas_lahan';

    protected $fillable = [
        'tahun',
        'luas',
        'data_perolehan',
        'nilai_perolehan',
        'bukti_perolehan',
        'id_leger_gerbang',
    ];

    public function legerGerbang()
    {
        return $this->belongsTo(\App\Models\Teknik\Gerbang\LegerGerbang::class, 'id_leger_gerbang');
    }
}
