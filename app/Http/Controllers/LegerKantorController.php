<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


use App\Models\Teknik\Kantor\DataKantorIdentifikasi;
use App\Models\Teknik\Kantor\DataKantorLuasLahan;
use App\Models\Teknik\Kantor\DataKantorRealisasi;
use App\Models\Teknik\Kantor\DataKantorTeknik1;
use App\Models\Teknik\Kantor\DataKantorTeknik2;
use App\Models\Teknik\Kantor\LegerKantor;

use App\Models\JalanTol;

use App\Models\Spatial\AdministratifPolygon;
use App\Models\Spatial\GerbangLine;
use App\Models\Spatial\GerbangPoint;
use App\Models\Spatial\ReflektorPoint;
use App\Models\Spatial\SegmenLegerPolygon;
use App\Models\Spatial\SegmenPerlengkapanPolygon;
use App\Models\Spatial\SegmenSeksiPolygon;

class LegerKantorController extends Controller
{
    public function getDataLegerKantor($kode_leger)
    {
        $leger_kantor = LegerKantor::with(
            'dataKantorIdentifikasi',
            'dataKantorTeknik1',
            'dataKantorTeknik2',
            'dataKantorLuasLahan',
            'dataKantorRealisasi',
            'dataKantorIdentifikasi.kodeProvinsi',
            'dataKantorIdentifikasi.kodeKabkot',
            'dataKantorIdentifikasi.kodeKecamatan',
            'dataKantorIdentifikasi.kodeDesakel'
        )
            ->where('kode_leger', $kode_leger)->first();
        return response()->json($leger_kantor);
    }

    public function generate(Request $request)
    {
        $data = null;
        return response()->json($data);
    }

    public function getRuas(Request $request)
    {
        $ruas = DB::table('jalan_tol')
        ->selectRaw('id, nama, tahun')
        ->get();
        return json_encode($ruas);
    }

    public function getSegmen(Request $request)
    {
        $segmen =DB::table('spatial_segmen_leger_polygon')
        ->where('spatial_segmen_leger_polygon.jalan_tol_id', $request->jalan_tol_id)
        ->where('spatial_segmen_leger_polygon.id_leger', 'like', 'M%') // Only select id_leger that starts with 'M.'
        ->selectRaw('spatial_segmen_leger_polygon.id_leger, spatial_segmen_leger_polygon.km')
        ->orderBy('spatial_segmen_leger_polygon.id_leger', 'asc') // Sort in ascending order
        ->get();

        $ruas = DB::table('jalan_tol')
        ->where('id', $request->jalan_tol_id)
        ->selectRaw('id, nama, tahun')
        ->first();

        return json_encode([
            'segmen' => $segmen,
            'ruas' => $ruas,
        ]);
    }

    public function getAdministratif(Request $request)
    {
        $administratif = DB::table('spatial_administratif_polygon')
            ->select('kode_prov', 'kode_kab', 'kode_kec', 'kode_desa')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->whereRaw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_administratif_polygon.geom::geometry)');
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->first();
        
        if (!$administratif) {
            return response()->json(['error' => 'Data not found'], 404);
        }
        
        // Ambil semua ID dalam satu query per tabel
        $kode_prov = DB::table('reff_kode_provinsi')->where('kode', $administratif->kode_prov)->value('id');
        $kode_kab = DB::table('reff_kode_kabkot')->where('kode', $administratif->kode_kab)->value('id');
        $kode_kec = DB::table('reff_kode_kecamatan')->where('kode', $administratif->kode_kec)->value('id');
        $kode_desa = DB::table('reff_kode_desakel')->where('kode', $administratif->kode_desa)->value('id');
        
        return json_encode([
            'kode_prov' => $kode_prov,
            'kode_kab'  => $kode_kab,
            'kode_kec'  => $kode_kec,
            'kode_desa' => $kode_desa,
        ]);
    }

    public function getRambuLaluLintas(Request $request)
    {

        //RAMBU LALU LINTAS KIRI
        $rambu_lalulintas_kiri_count =DB::table('spatial_rambu_lalulintas_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_lalulintas_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_rambu_lalulintas_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->selectRaw('COUNT(spatial_rambu_lalulintas_point.id) as rambu_lalulintas_kiri_count')
            ->value('rambu_lalulintas_kiri_count');

        //RAMBU LALU LINTAS KANAN
        $rambu_lalulintas_kanan_count =DB::table('spatial_rambu_lalulintas_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_lalulintas_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_rambu_lalulintas_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->selectRaw('COUNT(spatial_rambu_lalulintas_point.id) as rambu_lalulintas_kanan_count')
            ->value('rambu_lalulintas_kanan_count');

        //RAMBU LALU LINTAS MEDIAN
        $rambu_lalulintas_median_count =DB::table('spatial_rambu_lalulintas_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_lalulintas_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_rambu_lalulintas_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->selectRaw('COUNT(spatial_rambu_lalulintas_point.id) as rambu_lalulintas_median_count')
            ->value('rambu_lalulintas_median_count');

        return json_encode([
            'kiri' => $rambu_lalulintas_kiri_count,
            'kanan' => $rambu_lalulintas_kanan_count,
            'median' => $rambu_lalulintas_median_count
        ]);
    }

    public function getPagarOperasional(Request $request)
    {
        // PAGAR OPERASIONAL KIRI
        $pagar_operasional_kiri =DB::table('spatial_pagar_operasional_line')
            ->selectRaw('COUNT(spatial_pagar_operasional_line.id) as count, SUM(ST_Length(spatial_pagar_operasional_line.geom::geography)) as length')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_operasional_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_pagar_operasional_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get();

        // PAGAR OPERASIONAL KANAN
        $pagar_operasional_kanan =DB::table('spatial_pagar_operasional_line')
            ->selectRaw('COUNT(spatial_pagar_operasional_line.id) as count, SUM(ST_Length(spatial_pagar_operasional_line.geom::geography)) as length')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_operasional_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_pagar_operasional_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get();

        // PAGAR OPERASIONAL MEDIAN
        $pagar_operasional_median =DB::table('spatial_pagar_operasional_line')
            ->selectRaw('COUNT(spatial_pagar_operasional_line.id) as count, SUM(ST_Length(spatial_pagar_operasional_line.geom::geography)) as length')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_operasional_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_pagar_operasional_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->get();

        return json_encode([
            'kiri' => $pagar_operasional_kiri,
            'kanan' => $pagar_operasional_kanan,
            'median' => $pagar_operasional_median
        ]);
    }

    public function getKameraPengawas(Request $request)
    {
        //KIRI
        $kamera_pengawas_kiri = DB::table('spatial_kamera_pengawas_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_kamera_pengawas_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_kamera_pengawas_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $kamera_pengawas_median = DB::table('spatial_kamera_pengawas_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_kamera_pengawas_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_kamera_pengawas_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $kamera_pengawas_kanan = DB::table('spatial_kamera_pengawas_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_kamera_pengawas_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_kamera_pengawas_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $kamera_pengawas_kiri,
            'median' => $kamera_pengawas_median,
            'kanan' => $kamera_pengawas_kanan
        ]);
    }


    public function getdataKantor(Request $request)
    {
        $jalan_tol_id = $request->jalan_tol_id;

            $awal = (int) substr($request->leger_id_awal,2);
            $akhir = (int) substr($request->leger_id_akhir,2);
    
            $leger = DB::table('leger_kantor')
            ->select('leger_kantor.id', 'leger_kantor.kode_leger')
            ->join('leger', 'leger_kantor.leger_id', '=', 'leger.id') 
            ->where('leger.jalan_tol_id', '=', $jalan_tol_id) 
            ->where('leger_kantor.kode_leger', 'like', 'M%')
            ->whereBetween(DB::raw('CAST(SUBSTRING(leger_kantor.kode_leger, 3) AS INT)'), [$awal, $akhir])
            ->orderBy(DB::raw('CAST(SUBSTRING(leger_kantor.kode_leger, 3) AS INT)'), 'asc')
            ->get();

        $data = [];
        foreach ($leger as $l) {
            $request = new Request();
            $request->merge(['leger_id' => $l->kode_leger]);

            $data[$l->kode_leger] = [
                // IDENTIFIKASI
                "data_gerbang_identifikasi" => dataKantorIdentifikasi::where("id_leger_kantor", $l->id)->first(),
                // DATA TEKNIK 1
                "data_gerbang_teknik1" => [
                    "ruang_kepala_kantor" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "RUANG KEPALA KANTOR")->first(),
                    "ruang_staff" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "RUANG STAFF")->first(),
                    "ruang_rapat" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "RUANG RAPAT")->first(),
                    "ruang_tamu_lobby" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "RUANG TAMU/LOBBY")->first(),
                    "ruang_server" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "RUANG SERVER")->first(),
                    "mushola" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "MUSHOLA")->first(),
                    "dapur_pantry" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "DAPUR/PANTRY")->first(),
                    "toilet" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "TOILET")->first(),
                    "pengolahan_limbah" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "PENGOLAHAN LIMBAH")->first(),
                    "ruang_genset" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "RUANG GENSET")->first(),
                    "gudang" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "GUDANG")->first(),
                    "pos_keamanan" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "POS KEAMANAN")->first(),
                    "area_parkir" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "AREA PARKIR")->first(),
                    "menara_air" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "MENARA AIR")->first(),
                ],

                // DATA TEKNIK 2
                "data_gerbang_teknik2" => [
                    "pagar_operasional_kantor" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "PAGAR OPERASIONAL KANTOR")->first(),
                    "rambu_lalu_lintas" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "RAMBU LALU LINTAS")->first(),
                    "lampu_lalu_lintas" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "LAMPU LALU LINTAS")->first(),
                    "lampu_penerangan" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "LAMPU PENERANGAN")->first(),
                    "kamera_pengawas" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "KAMERA PENGAWAS")->first(),
                    "apar" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "APAR")->first(),
                    "ground_water_tank" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "GROUND WATER TANK")->first(),
                    "menara_air" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "MENARA AIR")->first(),
                    "layanan_mobil_derek" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "LAYANAN MOBIL DEREK")->first(),
                    
                ],

                // LUAS LAHAN
                "data_gerbang_luas_lahan" => [
                    "bangunan" => dataKantorLuasLahan::where("id_leger_kantor", $l->id)->where("uraian", "BANGUNAN")->first(),
                    "tanah" => dataKantorLuasLahan::where("id_leger_kantor", $l->id)->where("uraian", "TANAH")->first(),
                ]
            ];
        }
        return response()->json($data);
    }

    public function getAlldataKantor(Request $request)
    {
        $jalan_tol_id = $request->jalan_tol_id;

            $leger = DB::table('leger_kantor')
            ->select('leger_kantor.id', 'leger_kantor.kode_leger')
            ->join('leger', 'leger_kantor.leger_id', '=', 'leger.id') 
            ->where('leger.jalan_tol_id', '=', $jalan_tol_id) 
            ->where('leger_kantor.kode_leger', 'like', 'M%')
            ->orderBy(DB::raw('CAST(SUBSTRING(leger_kantor.kode_leger, 3) AS INT)'), 'asc')
            ->get();

        $data = [];
        foreach ($leger as $l) {
            $request = new Request();
            $request->merge(['leger_id' => $l->kode_leger]);

            $data[$l->kode_leger] = [
                // IDENTIFIKASI
                "data_kantor_identifikasi" => dataKantorIdentifikasi::where("id_leger_kantor", $l->id)->first(),
                // DATA TEKNIK 1
                "data_kantor_teknik1" => [
                    "ruang_kepala_kantor" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "RUANG KEPALA KANTOR")->first(),
                    "ruang_staff" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "RUANG STAFF")->first(),
                    "ruang_rapat" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "RUANG RAPAT")->first(),
                    "ruang_tamu_lobby" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "RUANG TAMU/LOBBY")->first(),
                    "ruang_server" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "RUANG SERVER")->first(),
                    "mushola" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "MUSHOLA")->first(),
                    "dapur_pantry" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "DAPUR/PANTRY")->first(),
                    "toilet" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "TOILET")->first(),
                    "pengolahan_limbah" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "PENGOLAHAN LIMBAH")->first(),
                    "ruang_genset" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "RUANG GENSET")->first(),
                    "gudang" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "GUDANG")->first(),
                    "pos_keamanan" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "POS KEAMANAN")->first(),
                    "area_parkir" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "AREA PARKIR")->first(),
                    "menara_air" => dataKantorTeknik1::where("id_leger_kantor", $l->id)->where("uraian", "MENARA AIR")->first(),
                ],

                // DATA TEKNIK 2
                "data_kantor_teknik2" => [
                    "pagar_operasional_kantor" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "PAGAR OPERASIONAL KANTOR")->first(),
                    "rambu_lalu_lintas" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "RAMBU LALU LINTAS")->first(),
                    "lampu_lalu_lintas" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "LAMPU LALU LINTAS")->first(),
                    "lampu_penerangan" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "LAMPU PENERANGAN")->first(),
                    "kamera_pengawas" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "KAMERA PENGAWAS")->first(),
                    "apar" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "APAR")->first(),
                    "ground_water_tank" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "GROUND WATER TANK")->first(),
                    "menara_air" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "MENARA AIR")->first(),
                    "layanan_mobil_derek" => dataKantorTeknik2::where("id_leger_kantor", $l->id)->where("uraian", "LAYANAN MOBIL DEREK")->first(),  
                ],

                // LUAS LAHAN
                "data_kantor_luas_lahan" => [
                    "bangunan" => dataKantorLuasLahan::where("id_leger_kantor", $l->id)->where("uraian", "BANGUNAN")->first(),
                    "tanah" => dataKantorLuasLahan::where("id_leger_kantor", $l->id)->where("uraian", "TANAH")->first(),
                ]
            ];
        }
        return response()->json($data);
    }
    
    public function getdataKantorAll($jalan_tol_id)
    {

        $leger = DB::table('leger_kantor')
        ->select('leger_kantor.id', 'leger_kantor.kode_leger')
        ->join('leger', 'leger_kantor.leger_id', '=', 'leger.id') 
        ->where('leger.jalan_tol_id', '=', $jalan_tol_id)
        ->where('leger_kantor.kode_leger', 'like', 'M%')
        ->orderBy(DB::raw('CAST(SUBSTRING(leger_kantor.kode_leger, 3) AS INT)'), 'asc')
        ->get();

        //init
        $data = [];
        foreach ($leger as $l) {
            $request = new Request();
            $request->merge(['leger_id' => $l->kode_leger]);

            $data[$l->kode_leger] = [
                'administratif' => json_decode($this->getAdministratif($request), true),
                'titik_segmen' => json_decode($this->getTitikSegmen($request), true),
                'pagar_operasional' => json_decode($this->getPagarOperasional($request), true),
                'rambu_lalu_lintas' => json_decode($this->getRambuLaluLintas($request), true),
                'kamera_pengawas' => json_decode($this->getKameraPengawas($request), true),
            ];
        }
        return $data;
    }

    public function populateLegerKantor(Request $request)
    {
        //placeholder
        $jalan_tol_id = $request->jalan_tol_id;

        $leger = LegerKantor::select('leger_kantor.id', 'leger_kantor.kode_leger')
        ->join('leger', 'leger_kantor.leger_id', '=', 'leger.id')
        ->where('leger.jalan_tol_id', $jalan_tol_id)
        ->where('leger_kantor.kode_leger', 'like', 'M%')
        ->orderByRaw('CAST(SUBSTRING(leger_kantor.kode_leger FROM 3) AS INT)')
        ->get()
        ->toArray();

        $tol = JalanTol::select("tahun")
        ->where("id", $jalan_tol_id)
        ->get()->first();
        
        $data = $this->getdataKantorAll($jalan_tol_id);
        $zipped = array_map(null, $leger, $data);

        foreach($zipped as [$l, $d]){

            dataKantorIdentifikasi::where('id_leger_kantor', isset($l['id']) ? $l['id'] : null)
            ->delete();
            dataKantorTeknik1::where('id_leger_kantor', isset($l['id']) ? $l['id'] : null)
            ->delete();
            dataKantorTeknik2::where('id_leger_kantor', isset($l['id']) ? $l['id'] : null)
            ->delete();
            dataKantorLuasLahan::where('id_leger_kantor', isset($l['id']) ? $l['id'] : null)
            ->delete();

            // DATA IDENTIFIKASI
            $data_kantor_identifikasi = [
                'kode_provinsi_id' => isset($d['administratif']['kode_prov']) ? $d['administratif']['kode_prov'] : null,
                'kode_kabkot_id' => isset($d['administratif']['kode_kab']) ? $d['administratif']['kode_kab'] : null,
                'kode_kecamatan_id' => isset($d['administratif']['kode_kec']) ? $d['administratif']['kode_kec'] : null,
                'kode_desakel_id' => isset($d['administratif']['kode_desa']) ? $d['administratif']['kode_desa'] : null,
                'nomor_ruas' => null,
                'nomor_seksi' => null,
                'nama_ruas' => null,
                'nama_kawasan_kantor' => null,
                'lokasi' => null,
                'titik_ikat_leger_kode' => null,
                'titik_ikat_leger_x' => null,
                'titik_ikat_leger_y' => null,
                'titik_ikat_leger_z' => null,
                'titik_ikat_leger_deskripsi' => null,
                'tanggal_selesai_bangun' => null,
                'tanggal_dibuka' => null,
                'tanggal_ditutup' => null,
                'id_leger_kantor' => $l['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            dataKantorIdentifikasi::insert($data_kantor_identifikasi);

            // DATA TEKNIK 1
            $uraian_teknik1 = [
                "RUANG KEPALA KANTOR",
                "RUANG STAFF",
                "RUANG RAPAT",
                "RUANG TAMU/LOBBY",
                "RUANG SERVER",
                "MUSHOLA",
                "DAPUR/PANTRY",
                "TOILET",
                "PENGOLAHAN LIMBAH",
                "RUANG GENSET",
                "GUDANG",
                "POS KEAMANAN",
                "AREA PARKIR",
                "MENARA AIR",
            ];

            $data_kantor_teknik1 = [];

            foreach ($uraian_teknik1 as $uraian) {
                $data_kantor_teknik1[] = [
                    'id_leger_kantor' => $l['id'] ?? null,
                    'tahun' => $tol['tahun'] ?? null,
                    'uraian' => $uraian,
                    'jumlah' => null,
                    'luas_lahan' => null,
                    'luas_bangunan' => null,
                    'kondisi' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            dataKantorTeknik1::insert($data_kantor_teknik1);
            
            // DATA TEKNIK 2
            $uraian_teknik2 = [
                "PAGAR OPERASIONAL KANTOR",
                "RAMBU LALU LINTAS",
                "LAMPU LALU LINTAS",
                "LAMPU PENERANGAN",
                "KAMERA PENGAWAS",
                "APAR",
                "GROUND WATER TANK",
                "MENARA AIR",
                "LAYANAN MOBIL DEREK",
            ];

            $data_kantor_teknik2 = [];

            foreach ($uraian_teknik2 as $uraian) {
                $data_kantor_teknik2[] = [
                    'id_leger_kantor' => $l['id'] ?? null,
                    'tahun' => $tol['tahun'] ?? null,
                    'uraian' => $uraian,
                    'jumlah' => null,
                    'panjang' => null,
                    'kondisi' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            dataKantorTeknik2::insert($data_kantor_teknik2);

            // DATA LUAS LAHAN
            $data_kantor_luas_lahan =
            [
                [
                    'id_leger_kantor' => $l['id'] ?? null,
                    'tahun' => isset($tol['tahun']) ? $tol['tahun'] : null,
                    'uraian' => 'BANGUNAN',
                    'luas' => isset($d['luas_rumija']['luas']) ? $d['luas_rumija']['luas'] : null,
                    'data_perolehan' => 'HASIL LAPANGAN',
                    'nilai_perolehan' => null,
                    'bukti_perolehan' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id_leger_kantor' => $l['id'] ?? null,
                    'tahun' => isset($tol['tahun']) ? $tol['tahun'] : null,
                    'uraian' => 'TANAH',
                    'luas' => isset($d['luas_rumija']['luas']) ? $d['luas_rumija']['luas'] : null,
                    'data_perolehan' => 'HASIL LAPANGAN',
                    'nilai_perolehan' => null,
                    'bukti_perolehan' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];
            dataKantorLuasLahan::insert($data_kantor_luas_lahan);
        }
        return 'finished';
    }
}

