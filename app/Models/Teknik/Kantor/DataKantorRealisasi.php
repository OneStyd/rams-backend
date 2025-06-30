<?php

namespace App\Models\Teknik\Kantor;

use Illuminate\Database\Eloquent\Model;

class DataKantorRealisasi extends Model
{
    protected $table = 'data_kantor_realisasi';

    protected $fillable = [
        'tahun',
        'kegiatan',
        'penyedia_jasa',
        'cacah',
        'biaya',
        'sumber_dana',
        'id_leger_kantor',
    ];

    public function legerKantor()
    {
        return $this->belongsTo(\App\Models\Teknik\Kantor\LegerKantor::class, 'id_leger_kantor');
    }
}