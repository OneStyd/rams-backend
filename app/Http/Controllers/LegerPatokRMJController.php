<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DataPatok;
use App\Models\LegerPatok;

class LegerPatokRMJController extends Controller
{
    public function index(Request $request)
    {
        // Ambil data patok RMJ beserta info administratif dan segmen leger
        $data = DB::table('spatial_patok_rmj_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_rmj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->leftJoin('administratif', 'administratif.kode_wilayah', '=', 'spatial_patok_rmj_point.kode_wilayah')
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->select(
                'spatial_patok_rmj_point.*',
                'administratif.nama_prov',
                'administratif.nama_kab',
                'administratif.nama_kec',
                'administratif.nama_desa'
            )
            ->get();

        // Kirim ke view atau JSON, tergantung kebutuhan
        // Kalau ini API bisa langsung json, kalau web bisa return view
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomor_patok' => 'required|string',
            'nomor_ruas' => 'required|string',
            'nama_ruas' => 'required|string',
            'nomor_seksi' => 'nullable|string',
            'nama_seksi' => 'nullable|string',
            'nomor_urut' => 'nullable|integer',
            'nomor_rmj' => 'nullable|string',
            'posisi' => 'nullable|string',
            'koordinat_x' => 'required|numeric',
            'koordinat_y' => 'required|numeric',
            'koordinat_z' => 'nullable|numeric',
            'tahun_pemasangan' => 'nullable|integer',
            'leger_id' => 'required|integer',
        ]);

        // Simpan data patok dulu
        $patok = DataPatok::create([
            'nomor_patok' => $request->nomor_patok,
            'nomor_ruas' => $request->nomor_ruas,
            'nama_ruas' => $request->nama_ruas,
            'nomor_seksi' => $request->nomor_seksi,
            'nama_seksi' => $request->nama_seksi,
            'nomor_urut' => $request->nomor_urut,
            'nomor_rmj' => $request->nomor_rmj,
            'posisi' => $request->posisi,
            'koordinat_x' => $request->koordinat_x,
            'koordinat_y' => $request->koordinat_y,
            'koordinat_z' => $request->koordinat_z,
            'tahun_pemasangan' => $request->tahun_pemasangan,
        ]);

        // Simpan relasi leger patok dengan kode RMJ
        LegerPatok::create([
            'leger_id' => $request->leger_id,
            'kode_leger' => 'RMJ',
            'data_patok_id' => $patok->id
        ]);

        return response()->json(['message' => 'Patok RMJ berhasil disimpan.']);
    }
}
