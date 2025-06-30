<?php

namespace App\Models\Teknik\Kantor;

use Illuminate\Database\Eloquent\Model;

class DataKantorTeknik2 extends Model
{
    protected $table = 'data_kantor_teknik2';

    protected $fillable = [
        'tahun',
        'uraian',
        'jumlah',
        'panjang',
        'kondisi',
        'id_leger_kantor',
    ];

    public function legerKantor()
    {
        return $this->belongsTo(\App\Models\Teknik\Kantor\LegerKantor::class, 'id_leger_kantor');
    }
}
