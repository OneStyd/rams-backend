<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DataPatok;
use App\Models\LegerPatok;

class LegerPatokLJController extends Controller
{
    public function show($id)
    {
        // Ambil data patok LJ berdasarkan leger_id yang diberikan
        $dataPatok = DB::table('spatial_patok_lj_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_lj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $id)
            ->select('spatial_patok_lj_point.*')
            ->first();

        // Ambil data administratif terkait, misal provinsi, kabupaten, kecamatan, desa
        $administratif = DB::table('administratif_table')
            ->where('id', $dataPatok->administratif_id ?? 0)
            ->get();

        $data = [
            'patok' => $dataPatok,
            'administratif' => $administratif
        ];

        return view('legerpatoklj', ['data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomor_patok' => 'required|string',
            'nomor_ruas' => 'required|string',
            'nama_ruas' => 'required|string',
            'lokasi' => 'nullable|string',
            'tahun_pemasangan' => 'nullable|integer',
            'koordinat_x' => 'required|numeric',
            'koordinat_y' => 'required|numeric',
            'leger_id' => 'required|integer'
        ]);

        $patok = DataPatok::create([
            'nomor_patok' => $request->nomor_patok,
            'nomor_ruas' => $request->nomor_ruas,
            'nama_ruas' => $request->nama_ruas,
            'lokasi' => $request->lokasi,
            'tahun_pemasangan' => $request->tahun_pemasangan,
            'koordinat_x' => $request->koordinat_x,
            'koordinat_y' => $request->koordinat_y,
            // tambahkan field lain yang diperlukan sesuai model DataPatok
        ]);

        LegerPatok::create([
            'leger_id' => $request->leger_id,
            'kode_leger' => 'LJ',
            'data_patok_id' => $patok->id
        ]);

        return response()->json(['message' => 'Patok LJ berhasil disimpan.']);
    }
}
