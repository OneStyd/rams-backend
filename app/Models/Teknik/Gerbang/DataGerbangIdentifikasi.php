<?php

namespace App\Models\Teknik\Gerbang;

use Illuminate\Database\Eloquent\Model;

class DataGerbangIdentifikasi extends Model
{
    protected $table = 'data_gerbang_identifikasi';

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
        'id_leger_gerbang',
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

    public function legerGerbang()
    {
        return $this->belongsTo(\App\Models\Teknik\Gerbang\LegerGerbang::class, 'id_leger_gerbang');
    }
}
