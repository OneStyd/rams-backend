<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Teknik\Gerbang\DataGerbangHargaTarif;
use App\Models\Teknik\Gerbang\DataGerbangIdentifikasi;
use App\Models\Teknik\Gerbang\DataGerbangLuasLahan;
use App\Models\Teknik\Gerbang\DataGerbangRealisasi;
use App\Models\Teknik\Gerbang\DataGerbangTeknik1;
use App\Models\Teknik\Gerbang\DataGerbangTeknik2;
use App\Models\Teknik\Gerbang\LegerGerbang;

use App\Models\JalanTol;

use App\Models\Spatial\AdministratifPolygon;
use App\Models\Spatial\GerbangLine;
use App\Models\Spatial\GerbangPoint;
use App\Models\Spatial\ReflektorPoint;
use App\Models\Spatial\SegmenLegerPolygon;
use App\Models\Spatial\SegmenPerlengkapanPolygon;
use App\Models\Spatial\SegmenSeksiPolygon;

class LegerGerbangController extends Controller
{
    public function getDataLegerGerbang($kode_leger)
    {
        $leger_gerbang = LegerGerbang::with(
            'dataGerbangIdentifikasi',
            'dataGerbangTeknik1',
            'dataGerbangTeknik2',
            'dataGerbangLuasLahan',
            'dataGerbangHargaTarif',
            'dataGerbangRealisasi',
            'dataGerbangIdentifikasi.kodeProvinsi',
            'dataGerbangIdentifikasi.kodeKabkot',
            'dataGerbangIdentifikasi.kodeKecamatan',
            'dataGerbangIdentifikasi.kodeDesakel'
        )
            ->where('kode_leger', $kode_leger)->first();
        return response()->json($leger_gerbang);
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

    public function getRambuPeringatan(Request $request)
    {
        //KIRI
        $rambu_peringatan_kiri = DB::table('spatial_rambu_peringatan_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_peringatan_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_peringatan_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $rambu_peringatan_median = DB::table('spatial_rambu_peringatan_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_peringatan_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_peringatan_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $rambu_peringatan_kanan = DB::table('spatial_rambu_peringatan_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_peringatan_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_peringatan_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $rambu_peringatan_kiri,
            'median' => $rambu_peringatan_median,
            'kanan' => $rambu_peringatan_kanan
        ]);
    }

    public function getRambuLarangan(Request $request)
    {
        //KIRI
        $rambu_larangan_kiri = DB::table('spatial_rambu_larangan_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_larangan_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_larangan_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $rambu_larangan_median = DB::table('spatial_rambu_larangan_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_larangan_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_larangan_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $rambu_larangan_kanan = DB::table('spatial_rambu_larangan_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_larangan_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_larangan_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $rambu_larangan_kiri,
            'median' => $rambu_larangan_median,
            'kanan' => $rambu_larangan_kanan
        ]);
    }

    public function getRambuPerintah(Request $request)
    {
        //KIRI
        $rambu_perintah_kiri = DB::table('spatial_rambu_perintah_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_perintah_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_perintah_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $rambu_perintah_median = DB::table('spatial_rambu_perintah_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_perintah_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_perintah_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $rambu_perintah_kanan = DB::table('spatial_rambu_perintah_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_perintah_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_perintah_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $rambu_perintah_kiri,
            'median' => $rambu_perintah_median,
            'kanan' => $rambu_perintah_kanan
        ]);
    }

    public function getRambuPetunjuk(Request $request)
    {
        //KIRI
        $rambu_petunjuk_kiri = DB::table('spatial_rambu_petunjuk_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_petunjuk_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_petunjuk_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $rambu_petunjuk_median = DB::table('spatial_rambu_petunjuk_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_petunjuk_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_petunjuk_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $rambu_petunjuk_kanan = DB::table('spatial_rambu_petunjuk_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_petunjuk_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_petunjuk_point.jalur', 'JALUR KANAN')
            ->get();
    }

    public function getRambuElektronik(Request $request)
    {
        //KIRI
        $rambu_elektronik_kiri = DB::table('spatial_rambu_elektronik_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_elektronik_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_elektronik_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $rambu_elektronik_median = DB::table('spatial_rambu_elektronik_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_elektronik_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_elektronik_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $rambu_elektronik_kanan = DB::table('spatial_rambu_elektronik_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rambu_elektronik_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_rambu_elektronik_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $rambu_elektronik_kiri,
            'median' => $rambu_elektronik_median,
            'kanan' => $rambu_elektronik_kanan
        ]);
    }

    public function getLampuPJU(Request $request)
    {
        //KIRI
        $lampu_pju_kiri = DB::table('spatial_lampu_pju_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_lampu_pju_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_lampu_pju_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $lampu_pju_median = DB::table('spatial_lampu_pju_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_lampu_pju_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_lampu_pju_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $lampu_pju_kanan = DB::table('spatial_lampu_pju_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_lampu_pju_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_lampu_pju_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $lampu_pju_kiri,
            'median' => $lampu_pju_median,
            'kanan' => $lampu_pju_kanan
        ]);
    }

    public function getHighmastTower(Request $request)
    {
        //KIRI
        $highmast_tower_kiri = DB::table('spatial_highmast_tower_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_highmast_tower_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_highmast_tower_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $highmast_tower_median = DB::table('spatial_highmast_tower_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_highmast_tower_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_highmast_tower_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $highmast_tower_kanan = DB::table('spatial_highmast_tower_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_highmast_tower_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_highmast_tower_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $highmast_tower_kiri,
            'median' => $highmast_tower_median,
            'kanan' => $highmast_tower_kanan
        ]);
    }

    public function getLampuSatuWarna(Request $request)
    {
        //KIRI
        $lampu_satu_warna_kiri = DB::table('spatial_lampu_satu_warna_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_lampu_satu_warna_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_lampu_satu_warna_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $lampu_satu_warna_median = DB::table('spatial_lampu_satu_warna_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_lampu_satu_warna_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_lampu_satu_warna_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $lampu_satu_warna_kanan = DB::table('spatial_lampu_satu_warna_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_lampu_satu_warna_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_lampu_satu_warna_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $lampu_satu_warna_kiri,
            'median' => $lampu_satu_warna_median,
            'kanan' => $lampu_satu_warna_kanan
        ]);
    }

    public function getLampuDuaWarna(Request $request)
    {
        //KIRI
        $lampu_dua_warna_kiri = DB::table('spatial_lampu_dua_warna_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_lampu_dua_warna_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_lampu_dua_warna_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $lampu_dua_warna_median = DB::table('spatial_lampu_dua_warna_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_lampu_dua_warna_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_lampu_dua_warna_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $lampu_dua_warna_kanan = DB::table('spatial_lampu_dua_warna_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_lampu_dua_warna_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_lampu_dua_warna_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $lampu_dua_warna_kiri,
            'median' => $lampu_dua_warna_median,
            'kanan' => $lampu_dua_warna_kanan
        ]);
    }

    public function getLampuTigaWarna(Request $request)
    {
        //KIRI
        $lampu_tiga_warna_kiri = DB::table('spatial_lampu_tiga_warna_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_lampu_tiga_warna_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_lampu_tiga_warna_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $lampu_tiga_warna_median = DB::table('spatial_lampu_tiga_warna_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_lampu_tiga_warna_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_lampu_tiga_warna_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $lampu_tiga_warna_kanan = DB::table('spatial_lampu_tiga_warna_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_lampu_tiga_warna_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_lampu_tiga_warna_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $lampu_tiga_warna_kiri,
            'median' => $lampu_tiga_warna_median,
            'kanan' => $lampu_tiga_warna_kanan
        ]);
    }

    public function getPagarPengamanKaku(Request $request)
    {
        //KIRI
        $pagar_pengaman_kaku_kiri = DB::table('spatial_pagar_pengaman_kaku_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_pengaman_kaku_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_pagar_pengaman_kaku_line.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $pagar_pengaman_kaku_median = DB::table('spatial_pagar_pengaman_kaku_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_pengaman_kaku_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_pagar_pengaman_kaku_line.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $pagar_pengaman_kaku_kanan = DB::table('spatial_pagar_pengaman_kaku_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_pengaman_kaku_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_pagar_pengaman_kaku_line.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $pagar_pengaman_kaku_kiri,
            'median' => $pagar_pengaman_kaku_median,
            'kanan' => $pagar_pengaman_kaku_kanan
        ]);
    }

    public function getPagarPengamanSemiKaku(Request $request)
    {
        //KIRI
        $pagar_pengaman_semi_kaku_kiri = DB::table('spatial_pagar_pengaman_semi_kaku_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_pengaman_semi_kaku_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_pagar_pengaman_semi_kaku_line.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $pagar_pengaman_semi_kaku_median = DB::table('spatial_pagar_pengaman_semi_kaku_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_pengaman_semi_kaku_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_pagar_pengaman_semi_kaku_line.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $pagar_pengaman_semi_kaku_kanan = DB::table('spatial_pagar_pengaman_semi_kaku_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_pengaman_semi_kaku_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_pagar_pengaman_semi_kaku_line.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $pagar_pengaman_semi_kaku_kiri,
            'median' => $pagar_pengaman_semi_kaku_median,
            'kanan' => $pagar_pengaman_semi_kaku_kanan
        ]);
    }

    public function getPagarPengamanFleksibel(Request $request)
    {
        //KIRI
        $pagar_pengaman_fleksibel_kiri = DB::table('spatial_pagar_pengaman_fleksibel_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_pengaman_fleksibel_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_pagar_pengaman_fleksibel_line.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $pagar_pengaman_fleksibel_median = DB::table('spatial_pagar_pengaman_fleksibel_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_pengaman_fleksibel_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_pagar_pengaman_fleksibel_line.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $pagar_pengaman_fleksibel_kanan = DB::table('spatial_pagar_pengaman_fleksibel_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_pengaman_fleksibel_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_pagar_pengaman_fleksibel_line.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $pagar_pengaman_fleksibel_kiri,
            'median' => $pagar_pengaman_fleksibel_median,
            'kanan' => $pagar_pengaman_fleksibel_kanan
        ]);
    }

    public function getCrashCushion(Request $request)
    {
        //KIRI
        $crash_cushion_kiri = DB::table('spatial_crash_cushion_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_crash_cushion_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_crash_cushion_line.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $crash_cushion_median = DB::table('spatial_crash_cushion_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_crash_cushion_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_crash_cushion_line.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $crash_cushion_kanan = DB::table('spatial_crash_cushion_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_crash_cushion_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_crash_cushion_line.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $crash_cushion_kiri,
            'median' => $crash_cushion_median,
            'kanan' => $crash_cushion_kanan
        ]);
    }

    public function getCerminTikungan(Request $request)
    {
        //KIRI
        $cermin_tikungan_kiri = DB::table('spatial_cermin_tikungan_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_cermin_tikungan_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_cermin_tikungan_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $cermin_tikungan_median = DB::table('spatial_cermin_tikungan_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_cermin_tikungan_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_cermin_tikungan_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $cermin_tikungan_kanan = DB::table('spatial_cermin_tikungan_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_cermin_tikungan_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_cermin_tikungan_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $cermin_tikungan_kiri,
            'median' => $cermin_tikungan_median,
            'kanan' => $cermin_tikungan_kanan
        ]);
    }

    public function getReflektor(Request $request)
    {
        //KIRI
        $reflektor_kiri = DB::table('spatial_reflektor_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_reflektor_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_reflektor_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $reflektor_median = DB::table('spatial_reflektor_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_reflektor_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_reflektor_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $reflektor_kanan = DB::table('spatial_reflektor_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_reflektor_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_reflektor_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $reflektor_kiri,
            'median' => $reflektor_median,
            'kanan' => $reflektor_kanan
        ]);
    }

    public function getPitaPenggaduh(Request $request)
    {
        //KIRI
        $pita_penggaduh_kiri = DB::table('spatial_pita_kejut_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_pita_kejut_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_pita_kejut_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $pita_penggaduh_median = DB::table('spatial_pita_kejut_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_pita_kejut_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_pita_kejut_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $pita_penggaduh_kanan = DB::table('spatial_pita_kejut_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_pita_kejut_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_pita_kejut_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $pita_penggaduh_kiri,
            'median' => $pita_penggaduh_median,
            'kanan' => $pita_penggaduh_kanan
        ]);
    }

    public function getPembatasKecepatan(Request $request)
    {
        //KIRI
        $pembatas_kecepatan_kiri = DB::table('spatial_pembatas_kecepatan_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pembatas_kecepatan_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_pembatas_kecepatan_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $pembatas_kecepatan_median = DB::table('spatial_pembatas_kecepatan_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pembatas_kecepatan_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_pembatas_kecepatan_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $pembatas_kecepatan_kanan = DB::table('spatial_pembatas_kecepatan_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pembatas_kecepatan_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_pembatas_kecepatan_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $pembatas_kecepatan_kiri,
            'median' => $pembatas_kecepatan_median,
            'kanan' => $pembatas_kecepatan_kanan
        ]);
    }

    public function getPembatasTinggidanLebar(Request $request)
    {
        //KIRI
        $pembatas_tinggi_dan_lebar_kiri = DB::table('spatial_pembatas_tinggi_dan_lebar_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pembatas_tinggi_dan_lebar_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_pembatas_tinggi_dan_lebar_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $pembatas_tinggi_dan_lebar_median = DB::table('spatial_pembatas_tinggi_dan_lebar_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pembatas_tinggi_dan_lebar_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_pembatas_tinggi_dan_lebar_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $pembatas_tinggi_dan_lebar_kanan = DB::table('spatial_pembatas_tinggi_dan_lebar_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pembatas_tinggi_dan_lebar_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_pembatas_tinggi_dan_lebar_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $pembatas_tinggi_dan_lebar_kiri,
            'median' => $pembatas_tinggi_dan_lebar_median,
            'kanan' => $pembatas_tinggi_dan_lebar_kanan
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


    public function getDataGerbang(Request $request)
    {
        $jalan_tol_id = $request->jalan_tol_id;

            $awal = (int) substr($request->leger_id_awal,2);
            $akhir = (int) substr($request->leger_id_akhir,2);
    
            $leger = DB::table('leger_gerbang')
            ->select('leger_gerbang.id', 'leger_gerbang.kode_leger')
            ->join('leger', 'leger_gerbang.leger_id', '=', 'leger.id') 
            ->where('leger.jalan_tol_id', '=', $jalan_tol_id) 
            ->where('leger_gerbang.kode_leger', 'like', 'M%')
            ->whereBetween(DB::raw('CAST(SUBSTRING(leger_gerbang.kode_leger, 3) AS INT)'), [$awal, $akhir])
            ->orderBy(DB::raw('CAST(SUBSTRING(leger_gerbang.kode_leger, 3) AS INT)'), 'asc')
            ->get();

        $data = [];
        foreach ($leger as $l) {
            $request = new Request();
            $request->merge(['leger_id' => $l->kode_leger]);

            $data[$l->kode_leger] = [
                // IDENTIFIKASI
                "data_gerbang_identifikasi" => DataGerbangIdentifikasi::where("id_leger_gerbang", $l->id)->first(),
                // DATA GERBANG TOL
                "data_gerbang_teknik1" => [
                    "gardu_tol_permanen" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "GARDU TOL PERMANEN")->first(),
                    "gto_gardu_tol_otomatis" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "GTO/GARDU TOL OTOMATIS")->first(),
                    "gerbang_tol_thundem" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "GERBANG TOL THUNDEM")->first(),
                    "gardu_tol_non_permanen" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "GARDU TOL NON PERMANEN")->first(),
                    "toll_booth" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "TOLL BOOTH")->first(),
                    "kantor_gerbang" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "KANTOR GERBANG")->first(),
                    "jalan_pejalan_kaki" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "JALAN PEJALAN KAKI")->first(),
                    "terowongan_pejalan_kaki" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "TEROWONGAN PEJALAN KAKI")->first(),
                ],

                // DATA PERLENGKAPAN GERBANG TOL
                "data_gerbang_teknik2" => [
                    "rambu_peringatan" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "RAMBU PERINGATAN")->first(),
                    "rambu_larangan" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "RAMBU LARANGAN")->first(),
                    "rambu_perintah" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "RAMBU PERINTAH")->first(),
                    "rambu_petunjuk" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "RAMBU PETUNJUK")->first(),
                    "rambu_elektronik" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "RAMBU ELEKTRONIK")->first(),
                    "lampu_tiga_warna" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "LAMPU TIGA WARNA")->first(),
                    "lampu_dua_warna" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "LAMPU DUA WARNA")->first(),
                    "lampu_satu_warna" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "LAMPU SATU WARNA")->first(),
                    "lampu_pju" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "LAMPU PJU")->first(),
                    "highmast_tower" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "HIGHMAST TOWER")->first(),
                    "pagar_pengaman_kaku" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PAGAR PENGAMAN KAKU")->first(),
                    "pagar_pengaman_semi_kaku" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PAGAR PENGAMAN SEMI KAKU")->first(),
                    "pagar_fleksibel" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PAGAR FLEKSIBEL")->first(),
                    "crash_cushion" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "CRASH CUSHION")->first(),
                    "cermin_tikungan" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "CERMIN TIKUNGAN")->first(),
                    "reflektor" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "REFLEKTOR")->first(),
                    "pita_penggaduh" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PITA PENGGADUH")->first(),
                    "pembatas_kecepatan" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PEMBATAS KECEPATAN")->first(),
                    "pembatas_tinggi_dan_lebar" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PEMBATAS TINGGI DAN LEBAR")->first(),
                    "kamera_pengawas" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("jenis_perlengkapan_id", "5")->where("uraian", "KAMERA PENGAWAS")->first(),
                    "pagar_operasional" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PAGAR OPERASIONAL")->first(),
                    "papan_informasi_tarif_tol" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PAPAN INFORMASI TARIF TOL")->first(),
                    "patok_kilometer" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PATOK KILOMETER")->first(),
                    "loop_coil" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "LOOP COIL")->first(),
                    "palang_pintu_tol" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PALANG PINTU TOL")->first(),
                ],

                // LUAS LAHAN
                "data_gerbang_luas_lahan" => DataGerbangLuasLahan::where("id_leger_gerbang", $l->id)->first(),

                // TARIF TOL
                "data_gerbang_harga_tarif" => [
                    "gol1" => DataGerbangHargaTarif::where("id_leger_gerbang", $l->id)->where("uraian", "GOL1")->first(),
                    "gol2" => DataGerbangHargaTarif::where("id_leger_gerbang", $l->id)->where("uraian", "GOL2")->first(),
                    "gol3" => DataGerbangHargaTarif::where("id_leger_gerbang", $l->id)->where("uraian", "GOL3")->first(),
                    "gol4" => DataGerbangHargaTarif::where("id_leger_gerbang", $l->id)->where("uraian", "GOL4")->first(),
                    "gol5" => DataGerbangHargaTarif::where("id_leger_gerbang", $l->id)->where("uraian", "GOL5")->first(),
                    "gol6" => DataGerbangHargaTarif::where("id_leger_gerbang", $l->id)->where("uraian", "GOL6")->first(),
                ],

                // PERWUJUDAN (REALISASI)
                "data_gerbang_realisasi" => [
                    "pembebasan_lahan" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "PEMBEBASAN LAHAN")->first(),
                    "desain" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "DESAIN")->first(),
                    "pembangunan" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "PEMBANGUNAN")->first(),
                    "peningkatan" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "PENINGKATAN")->first(),
                    "rekonstruksi" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "REKONSTRUKSI")->first(),
                    "pemeliharaan_dan_rehabilitasi" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "PEMELIHARAAN & REHABILITASI")->first(),
                    "supervisi" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "SUPERVISI")->first(),
                    "pengendali_mutu_independen" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "PENGENDALI MUTU INDEPENDEN")->first(),
                    "lainnya" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "LAINNYA")->first(),
                ],
                
            ];
        }
        return response()->json($data);
    }

    public function getAllDataGerbang(Request $request)
    {
        $jalan_tol_id = $request->jalan_tol_id;

            $leger = DB::table('leger_gerbang')
            ->select('leger_gerbang.id', 'leger_gerbang.kode_leger')
            ->join('leger', 'leger_gerbang.leger_id', '=', 'leger.id') 
            ->where('leger.jalan_tol_id', '=', $jalan_tol_id) 
            ->where('leger_gerbang.kode_leger', 'like', 'M%')
            ->orderBy(DB::raw('CAST(SUBSTRING(leger_gerbang.kode_leger, 3) AS INT)'), 'asc')
            ->get();

        $data = [];
        foreach ($leger as $l) {
            $request = new Request();
            $request->merge(['leger_id' => $l->kode_leger]);

            $data[$l->kode_leger] = [
                // IDENTIFIKASI
                "data_gerbang_identifikasi" => DataGerbangIdentifikasi::where("id_leger_gerbang", $l->id)->first(),
                // DATA GERBANG TOL
                "data_gerbang_teknik1" => [
                    "gardu_tol_permanen" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "GARDU TOL PERMANEN")->first(),
                    "gto_gardu_tol_otomatis" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "GTO/GARDU TOL OTOMATIS")->first(),
                    "gerbang_tol_thundem" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "GERBANG TOL THUNDEM")->first(),
                    "gardu_tol_non_permanen" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "GARDU TOL NON PERMANEN")->first(),
                    "toll_booth" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "TOLL BOOTH")->first(),
                    "kantor_gerbang" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "KANTOR GERBANG")->first(),
                    "jalan_pejalan_kaki" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "JALAN PEJALAN KAKI")->first(),
                    "terowongan_pejalan_kaki" => DataGerbangTeknik1::where("id_leger_gerbang", $l->id)->where("uraian", "TEROWONGAN PEJALAN KAKI")->first(),
                ],
                // DATA PERLENGKAPAN GERBANG TOL
                "data_gerbang_teknik2" => [
                    "rambu_peringatan" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "RAMBU PERINGATAN")->first(),
                    "rambu_larangan" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "RAMBU LARANGAN")->first(),
                    "rambu_perintah" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "RAMBU PERINTAH")->first(),
                    "rambu_petunjuk" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "RAMBU PETUNJUK")->first(),
                    "rambu_elektronik" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "RAMBU ELEKTRONIK")->first(),
                    "lampu_tiga_warna" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "LAMPU TIGA WARNA")->first(),
                    "lampu_dua_warna" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "LAMPU DUA WARNA")->first(),
                    "lampu_satu_warna" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "LAMPU SATU WARNA")->first(),
                    "lampu_pju" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "LAMPU PJU")->first(),
                    "highmast_tower" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "HIGHMAST TOWER")->first(),
                    "pagar_pengaman_kaku" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PAGAR PENGAMAN KAKU")->first(),
                    "pagar_pengaman_semi_kaku" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PAGAR PENGAMAN SEMI KAKU")->first(),
                    "pagar_fleksibel" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PAGAR FLEKSIBEL")->first(),
                    "crash_cushion" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "CRASH CUSHION")->first(),
                    "cermin_tikungan" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "CERMIN TIKUNGAN")->first(),
                    "reflektor" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "REFLEKTOR")->first(),
                    "pita_penggaduh" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PITA PENGGADUH")->first(),
                    "pembatas_kecepatan" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PEMBATAS KECEPATAN")->first(),
                    "pembatas_tinggi_dan_lebar" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PEMBATAS TINGGI DAN LEBAR")->first(),
                    "kamera_pengawas" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("jenis_perlengkapan_id", "5")->where("uraian", "KAMERA PENGAWAS")->first(),
                    "pagar_operasional" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PAGAR OPERASIONAL")->first(),
                    "papan_informasi_tarif_tol" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PAPAN INFORMASI TARIF TOL")->first(),
                    "patok_kilometer" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PATOK KILOMETER")->first(),
                    "loop_coil" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "LOOP COIL")->first(),
                    "palang_pintu_tol" => DataGerbangTeknik2::where("id_leger_gerbang", $l->id)->where("uraian", "PALANG PINTU TOL")->first(),
                ],

                // LUAS LAHAN
                "data_gerbang_luas_lahan" => DataGerbangLuasLahan::where("id_leger_gerbang", $l->id)->first(),

                // TARIF TOL
                "data_gerbang_harga_tarif" => [
                    "gol1" => DataGerbangHargaTarif::where("id_leger_gerbang", $l->id)->where("uraian", "GOL1")->first(),
                    "gol2" => DataGerbangHargaTarif::where("id_leger_gerbang", $l->id)->where("uraian", "GOL2")->first(),
                    "gol3" => DataGerbangHargaTarif::where("id_leger_gerbang", $l->id)->where("uraian", "GOL3")->first(),
                    "gol4" => DataGerbangHargaTarif::where("id_leger_gerbang", $l->id)->where("uraian", "GOL4")->first(),
                    "gol5" => DataGerbangHargaTarif::where("id_leger_gerbang", $l->id)->where("uraian", "GOL5")->first(),
                    "gol6" => DataGerbangHargaTarif::where("id_leger_gerbang", $l->id)->where("uraian", "GOL6")->first(),
                ],

                // PERWUJUDAN (REALISASI)
                "data_gerbang_realisasi" => [
                    "pembebasan_lahan" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "PEMBEBASAN LAHAN")->first(),
                    "desain" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "DESAIN")->first(),
                    "pembangunan" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "PEMBANGUNAN")->first(),
                    "peningkatan" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "PENINGKATAN")->first(),
                    "rekonstruksi" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "REKONSTRUKSI")->first(),
                    "pemeliharaan_dan_rehabilitasi" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "PEMELIHARAAN & REHABILITASI")->first(),
                    "supervisi" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "SUPERVISI")->first(),
                    "pengendali_mutu_independen" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "PENGENDALI MUTU INDEPENDEN")->first(),
                    "lainnya" => DataGerbangRealisasi::where("id_leger_gerbang", $l->id)->where("uraian", "LAINNYA")->first(),
                ],
                
            ];
        }
        return response()->json($data);
    }
    
    public function getDataGerbangAll($jalan_tol_id)
    {

        $leger = DB::table('leger_gerbang')
        ->select('leger_gerbang.id', 'leger_gerbang.kode_leger')
        ->join('leger', 'leger_gerbang.leger_id', '=', 'leger.id') 
        ->where('leger.jalan_tol_id', '=', $jalan_tol_id)
        ->where('leger_gerbang.kode_leger', 'like', 'M%')
        ->orderBy(DB::raw('CAST(SUBSTRING(leger_gerbang.kode_leger, 3) AS INT)'), 'asc')
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
                'rambu_peringatan' => json_decode($this->getRambuPeringatan($request), true),
                'rambu_larangan' => json_decode($this->getRambuLarangan($request), true),
                'rambu_perintah' => json_decode($this->getRambuPerintah($request), true),
                'rambu_petunjuk' => json_decode($this->getRambuPetunjuk($request), true),
                'rambu_elektronik' => json_decode($this->getRambuElektronik($request), true),
                'lampu_pju' => json_decode($this->getLampuPJU($request), true),
                'highmast_tower' => json_decode($this->getHighmastTower($request), true),
                'lampu_satu_warna' => json_decode($this->getLampuSatuWarna($request), true),
                'lampu_dua_warna' => json_decode($this->getLampuDuaWarna($request), true),
                'lampu_tiga_warna' => json_decode($this->getLampuTigaWarna($request), true),
                'pagar_pengaman_kaku' => json_decode($this->getPagarPengamanKaku($request), true),
                'pagar_pengaman_semi_kaku' => json_decode($this->getPagarPengamanSemiKaku($request), true),
                'pagar_fleksibel' => json_decode($this->getPagarPengamanFleksibel($request), true),
                'crash_cushion' => json_decode($this->getCrashCushion($request), true),
                'cermin_tikungan' => json_decode($this->getCerminTikungan($request), true),
                'reflektor' => json_decode($this->getReflektor($request), true),
                'pita_penggaduh' => json_decode($this->getPitaPenggaduh($request), true),
                'pembatas_kecepatan' => json_decode($this->getPembatasKecepatan($request), true),
                'pembatas_tinggi_dan_lebar' => json_decode($this->getPembatasTinggidanLebar($request), true),
                'kamera_pengawas' => json_decode($this->getKameraPengawas($request), true),
            ];
        }
        return $data;
    }

    public function populateLegerGerbang(Request $request)
    {
        //placeholder
        $jalan_tol_id = $request->jalan_tol_id;

        $leger = LegerGerbang::select('leger_gerbang.id', 'leger_gerbang.kode_leger')
        ->join('leger', 'leger_gerbang.leger_id', '=', 'leger.id')
        ->where('leger.jalan_tol_id', $jalan_tol_id)
        ->where('leger_gerbang.kode_leger', 'like', 'M%')
        ->orderByRaw('CAST(SUBSTRING(leger_gerbang.kode_leger FROM 3) AS INT)')
        ->get()
        ->toArray();

        $tol = JalanTol::select("tahun")
        ->where("id", $jalan_tol_id)
        ->get()->first();
        
        $data = $this->getDataGerbangAll($jalan_tol_id);
        $zipped = array_map(null, $leger, $data);

        foreach($zipped as [$l, $d]){

            DataGerbangIdentifikasi::where('id_leger_gerbang', isset($l['id']) ? $l['id'] : null)
            ->delete();
            DataGerbangTeknik1::where('id_leger_gerbang', isset($l['id']) ? $l['id'] : null)
            ->delete();
            DataGerbangTeknik2::where('id_leger_gerbang', isset($l['id']) ? $l['id'] : null)
            ->delete();
            DataGerbangLuasLahan::where('id_leger_gerbang', isset($l['id']) ? $l['id'] : null)
            ->delete();
            DataGerbangHargaTarif::where('id_leger_gerbang', isset($l['id']) ? $l['id'] : null)
            ->delete();
            DataGerbangRealisasi::where('id_leger_gerbang', isset($l['id']) ? $l['id'] : null)
            ->delete();

            // DATA IDENTIFIKASI
            $data_gerbang_identifikasi =
            [
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
                'id_leger_gerbang' => $l['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DataGerbangIdentifikasi::insert($data_gerbang_identifikasi);

            // DATA GERBANG TOL
            $uraian_teknik1 = [
                'GARDU TOL PERMANEN',
                'GTO/GARDU TOL OTOMATIS',
                'GERBANG TOL THUNDEM',
                'GARDU TOL NON PERMANEN',
                'TOLL BOOTH',
                'KANTOR GERBANG',
                'JALAN PEJALAN KAKI',
                'TEROWONGAN PEJALAN KAKI',
            ];

            $data_gerbang_teknik1 = [];

            foreach ($uraian_teknik1 as $uraian) {
                $data_gerbang_teknik1[] = [
                    'id_leger_gerbang' => $l['id'] ?? null,
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

            DataGerbangTeknik1::insert($data_gerbang_teknik1);
            
            // DATA PERLENGKAPAN GERBANG TOL 
            $uraian_teknik2 = [
                "RAMBU PERINGATAN",
                "RAMBU LARANGAN",
                "RAMBU PERINTAH",
                "RAMBU PETUNJUK",
                "RAMBU ELEKTRONIK",
                "LAMPU TIGA WARNA",
                "LAMPU DUA WARNA",
                "LAMPU SATU WARNA",
                "LAMPU PJU",
                "HIGHMAST TOWER",
                "PAGAR PENGAMAN KAKU",
                "PAGAR PENGAMAN SEMI KAKU",
                "PAGAR FLEKSIBEL",
                "CRASH CUSHION",
                "CERMIN TIKUNGAN",
                "REFLEKTOR",
                "PITA PENGGADUH",
                "PEMBATAS KECEPATAN",
                "PEMBATAS TINGGI DAN LEBAR",
                "KAMERA PENGAWAS",
                "PAGAR OPERASIONAL",
                "PAPAN INFORMASI TARIF TOL",
                "PATOK KILOMETER",
                "LOOP COIL",
                "PALANG PINTU TOL",
            ];

            $data_gerbang_teknik2 = [];

            foreach ($uraian_teknik2 as $uraian) {
                $data_gerbang_teknik2[] = [
                    'id_leger_gerbang' => $l['id'] ?? null,
                    'tahun' => $tol['tahun'] ?? null,
                    'uraian' => $uraian,
                    'jumlah' => null,
                    'panjang' => null,
                    'kondisi' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DataGerbangTeknik2::insert($data_gerbang_teknik2);

            // DATA LUAS LAHAN
            $data_gerbang_luas_lahan =
            [
                [
                    'id_leger_gerbang' => $l['id'] ?? null,
                    'tahun' => isset($tol['tahun']) ? $tol['tahun'] : null,
                    'luas' => isset($d['luas_rumija']['luas']) ? $d['luas_rumija']['luas'] : null,
                    'data_perolehan' => 'HASIL LAPANGAN',
                    'nilai_perolehan' => null,
                    'bukti_perolehan' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];
            DataGerbangLuasLahan::insert($data_gerbang_luas_lahan);
            
            // DATA HARGA TARIF
            $data_gerbang_harga_tarif =
            [
                [
                    'id_leger_gerbang' => $l['id'] ?? null,
                    'tahun' => isset($tol['tahun']) ? $tol['tahun'] : null,
                    'gerbang' => $l['kode_leger'] ?? null,
                    'gol1' => null,
                    'gol2' => null,
                    'gol3' => null,
                    'gol4' => null,
                    'gol5' => null,
                    'gol6' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];
            DataGerbangHargaTarif::insert($data_gerbang_harga_tarif);

            // DATA REALISASI (PERWUJUDAN)
            $kegiatan_realisasi = [
                "PEMBEBASAN LAHAN",
                "DESAIN",
                "PEMBANGUNAN",
                "PENINGKATAN",
                "REKONSTRUKSI",
                "PEMELIHARAAN & REHABILITASI",
                "SUPERVISI",
                "PENGENDALI MUTU INDEPENDEN",
                "LAINNYA",
            ];

            $data_gerbang_realisasi = [];

            foreach ($kegiatan_realisasi as $kegiatan) {
                $data_gerbang_realisasi[] = [
                    'id_leger_gerbang' => $l['id'] ?? null,
                    'tahun' => $tol['tahun'] ?? null,
                    'kegiatan' => $kegiatan,
                    'penyedia_jasa' => null,
                    'cacah' => null,
                    'biaya' => null,
                    'sumber_dana' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DataGerbangRealisasi::insert($data_gerbang_realisasi);

        }
        return 'finished';
    }
}

