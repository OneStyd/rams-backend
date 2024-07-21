<?php

namespace App\Models\Teknik;

use Illuminate\Database\Eloquent\Model;

class DataJalanLingkungan extends Model
{
    protected $table = 'data_jalan_lingkungan';

    protected $fillable = [
        'tahun',
        'uraian',
        'nilai',
    ];
}
