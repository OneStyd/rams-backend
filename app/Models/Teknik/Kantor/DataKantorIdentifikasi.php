<?php

namespace App\Models\Teknik\Kantor;

use Illuminate\Database\Eloquent\Model;

class DataKantorIdentifikasi extends Model
{
    protected $table = 'data_kantor_identifikasi';

    protected $fillable = [
        'kode_provinsi_id',
        'kode_kabkot_id',
        'kode_kecamatan_id',
        'kode_desakel_id',
        'nomor_ruas',
        'nomor_seksi',
        'nama_ruas',
        'nama_kawasan_kantor',
        'lokasi',
        'titik_ikat_leger_kode',
        'titik_ikat_leger_x',
        'titik_ikat_leger_y',
        'titik_ikat_leger_z',
        'titik_ikat_leger_deskripsi',
        'tanggal_selesai_bangun',
        'tanggal_dibuka',
        'tanggal_ditutup',
        'id_leger_kantor',
    ];

    public function kodeProvinsi()
    {
        return $this->belongsTo(\App\Models\Teknik\KodeProvinsi::class, 'kode_provinsi_id');
    }

    public function kodeKabkot()
    {
        return $this->belongsTo(\App\Models\Teknik\KodeKabkot::class, 'kode_kabkot_id');
    }

    public function kodeKecamatan()
    {
        return $this->belongsTo(\App\Models\Teknik\KodeKecamatan::class, 'kode_kecamatan_id');
    }

    public function kodeDesakel()
    {
        return $this->belongsTo(\App\Models\Teknik\KodeDesakel::class, 'kode_desakel_id');
    }

    public function legerKantor()
    {
        return $this->belongsTo(\App\Models\Teknik\Kantor\LegerKantor::class, 'id_leger_kantor');
    }
}
