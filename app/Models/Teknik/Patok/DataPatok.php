<?php

namespace App\Models\Teknik\Patok;

use Illuminate\Database\Eloquent\Model;

class DataPatok extends Model
{
    protected $table = 'data_patok';

    protected $fillable = [
        'nomor_patok',
        'nomor_ruas',
        'nomor_seksi',
        'nama_ruas',
        'desa',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'lokasi',
        'tahun_pemasangan',
        'koordinat_x',
        'koordinat_y',
        'koordinat_z',
        'sistem_koordinat',
        'tipe_alat_ukur',
        'tanggal_pengukuran',
        'keterangan',
    ];

    // Relasi ke leger_patok (bisa punya banyak leger_patok)
    public function legerPatoks()
    {
        return $this->hasMany(LegerPatok::class, 'data_patok_id');
    }
}
