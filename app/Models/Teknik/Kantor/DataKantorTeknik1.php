<?php

namespace App\Models\Teknik\Kantor;

use Illuminate\Database\Eloquent\Model;

class DataKantorTeknik1 extends Model
{
    protected $table = 'data_kantor_teknik1';

    protected $fillable = [
        'tahun',
        'uraian',
        'jumlah',
        'luas_lahan',
        'luas_bangunan',
        'kondisi',
        'id_leger_kantor',
    ];

    public function legerKantor()
    {
        return $this->belongsTo(\App\Models\Teknik\Kantor\LegerKantor::class, 'id_leger_kantor');
    }
}
