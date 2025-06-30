<?php

namespace App\Models\Teknik\Kantor;

use Illuminate\Database\Eloquent\Model;

class DataKantorLuasLahan extends Model
{
    protected $table = 'data_kantor_luas_lahan';

    protected $fillable = [
        'tahun',
        'luas',
        'data_perolehan',
        'nilai_perolehan',
        'bukti_perolehan',
        'id_leger_kantor',
    ];

    public function legerKantor()
    {
        return $this->belongsTo(\App\Models\Teknik\Kantor\LegerKantor::class, 'id_leger_kantor');
    }
}
