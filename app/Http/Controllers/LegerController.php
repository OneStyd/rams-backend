<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Teknik\Jalan\DataJalanGeometrik;
use App\Models\Teknik\Jalan\DataJalanSituasi;
use App\Models\Teknik\Jalan\DataJalanIdentifikasi;
use App\Models\Teknik\Jalan\DataJalanLainnya;
use App\Models\Teknik\Jalan\DataJalanLHR;
use App\Models\Teknik\Jalan\DataJalanLingkungan;
use App\Models\Teknik\Jalan\DataJalanTeknik1;
use App\Models\Teknik\Jalan\DataJalanTeknik2Bahujalan;
use App\Models\Teknik\Jalan\DataJalanTeknik2Lapis;
use App\Models\Teknik\Jalan\DataJalanTeknik2Median;
use App\Models\Teknik\Jalan\DataJalanTeknik3Bangunan;
use App\Models\Teknik\Jalan\DataJalanTeknik3Goronggorong;
use App\Models\Teknik\Jalan\DataJalanTeknik3Saluran;
use App\Models\Teknik\Jalan\DataJalanTeknik4;
use App\Models\Teknik\Jalan\DataJalanTeknik5Bangunan;
use App\Models\Teknik\Jalan\DataJalanTeknik5Utilitas;
use App\Models\Teknik\Jalan\DataJalanGambar;
use App\Models\Teknik\Jalan\LegerJalan;

use App\Models\JalanTol;

use App\Models\Spatial\AdministratifPolygon;
use App\Models\Spatial\BatasDesaLine;
use App\Models\Spatial\BoxCulvertLine;
use App\Models\Spatial\BPTLine;
use App\Models\Spatial\BronjongLine;
use App\Models\Spatial\ConcreteBarrierLine;
use App\Models\Spatial\DataGeometrikJalanPolygon;
use App\Models\Spatial\GerbangLine;
use App\Models\Spatial\GerbangPoint;
use App\Models\Spatial\GorongGorongLine;
use App\Models\Spatial\GuardrailLine;
use App\Models\Spatial\IRIPolygon;
use App\Models\Spatial\JalanLine;
use App\Models\Spatial\JembatanPoint;
use App\Models\Spatial\JembatanPolygon;
use App\Models\Spatial\LampuLalulintasPoint;
use App\Models\Spatial\LapisPermukaanPolygon;
use App\Models\Spatial\LapisPondasiAtas1Polygon;
use App\Models\Spatial\LapisPondasiAtas2Polygon;
use App\Models\Spatial\LapisPondasiBawahPolygon;
use App\Models\Spatial\LHRPolygon;
use App\Models\Spatial\ListrikBawahtanahLine;
use App\Models\Spatial\ManholePoint;
use App\Models\Spatial\MarkaLine;
use App\Models\Spatial\PagarOperasionalLine;
use App\Models\Spatial\PatokHMPoint;
use App\Models\Spatial\PatokKMPoint;
use App\Models\Spatial\PatokLJPoint;
use App\Models\Spatial\PatokPemanduPoint;
use App\Models\Spatial\PatokRMJPoint;
use App\Models\Spatial\PatokROWPoint;
use App\Models\Spatial\PitaKejutLine;
use App\Models\Spatial\RambuLalulintasPoint;
use App\Models\Spatial\RambuPenunjukarahPoint;
use App\Models\Spatial\ReflektorPoint;
use App\Models\Spatial\RiolLine;
use App\Models\Spatial\RumahKabelPoint;
use App\Models\Spatial\RuwasjaPolygon;
use App\Models\Spatial\SaluranLine;
use App\Models\Spatial\SegmenKonstruksiPolygon;
use App\Models\Spatial\SegmenLegerPolygon;
use App\Models\Spatial\SegmenPerlengkapanPolygon;
use App\Models\Spatial\SegmenSeksiPolygon;
use App\Models\Spatial\SegmenTolPolygon;
use App\Models\Spatial\StaTextPoint;
use App\Models\Spatial\SungaiLine;
use App\Models\Spatial\TeleponBawahtanahLine;
use App\Models\Spatial\TiangListrikPoint;
use App\Models\Spatial\TiangTeleponPoint;
use App\Models\Spatial\VMSPoint;



class LegerController extends Controller
{
    public function getData($kode_leger)
    {
        $leger_jalan = LegerJalan::with(
            'dataJalanIdentifikasi',
            'dataJalanTeknik1',
            'dataJalanTeknik2Lapis',
            'dataJalanTeknik2Median',
            'dataJalanTeknik2BahuJalan',
            'dataJalanTeknik3Goronggorong',
            'dataJalanTeknik3Saluran',
            'dataJalanTeknik3Bangunan',
            'dataJalanTeknik4',
            'dataJalanTeknik5Utilitas',
            'dataJalanTeknik5Bangunan',
            'dataJalanLHR',
            'dataJalanGeometrik',
            'dataJalanLingkungan',
            'dataJalanLainnya',
            'dataJalanIdentifikasi.kodeProvinsi',
            'dataJalanIdentifikasi.kodeKabkot',
            'dataJalanIdentifikasi.kodeKecamatan',
            'dataJalanIdentifikasi.kodeDesakel'
        )
            ->where('kode_leger', $kode_leger)->first();
        return response()->json($leger_jalan);
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
        $segmen = DB::table('spatial_segmen_leger_polygon')
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

    //// LEGER JALAN UTAMA DEPAN
    public function getRambuLaluLintas(Request $request)
    {

        //RAMBU LALU LINTAS KIRI
        $rambu_lalulintas_kiri_count = DB::table('spatial_rambu_lalulintas_point')
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
        $rambu_lalulintas_kanan_count = DB::table('spatial_rambu_lalulintas_point')
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
        $rambu_lalulintas_median_count = DB::table('spatial_rambu_lalulintas_point')
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

    public function getGorongGorong(Request $request)
    {
        $gorong = DB::table('spatial_gorong_gorong_line as gr')
            ->select('gr.*')
            ->join('spatial_segmen_leger_polygon as sl', DB::raw('ST_Contains(sl.geom::geometry, gr.geom::geometry)'), '=', DB::raw('true'))
            ->where('sl.id_leger', $request->leger_id)
            ->get();

        return json_encode($gorong);
    }

    public function getDataGeometrikJalan(Request $request)
    {
        $data_geometrik_jalan = DB::table('spatial_data_geometrik_jalan_polygon')
            ->select('spatial_data_geometrik_jalan_polygon.*')
            ->where('spatial_data_geometrik_jalan_polygon.id_leger', $request->leger_id)
            ->get()->first();

        return json_encode($data_geometrik_jalan);
    }

    public function getPatokKM(Request $request)
    {
        // PATOK KM KIRI
        $patok_km_kiri_count = DB::table('spatial_patok_km_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_km_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_patok_km_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->selectRaw('COUNT(spatial_patok_km_point.id) as patok_km_kiri_count')
            ->value('patok_km_kiri_count');

        // PATOK KM KANAN
        $patok_km_kanan_count = DB::table('spatial_patok_km_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_km_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_patok_km_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->selectRaw('COUNT(spatial_patok_km_point.id) as patok_km_kanan_count')
            ->value('patok_km_kanan_count');

        // PATOK KM MEDIAN
        $patok_km_median_count = DB::table('spatial_patok_km_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_km_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_patok_km_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->selectRaw('COUNT(spatial_patok_km_point.id) as patok_km_median_count')
            ->value('patok_km_median_count');

        return json_encode([
            'kiri' => $patok_km_kiri_count,
            'kanan' => $patok_km_kanan_count,
            'median' => $patok_km_median_count
        ]);
    }

    public function getPatokHM(Request $request)
    {
        // PATOK HM KIRI
        $patok_hm_kiri_count = DB::table('spatial_patok_hm_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_hm_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_patok_hm_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->selectRaw('COUNT(spatial_patok_hm_point.id) as patok_hm_kiri_count')
            ->value('patok_hm_kiri_count');

        // PATOK HM KANAN
        $patok_hm_kanan_count = DB::table('spatial_patok_hm_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_hm_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_patok_hm_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->selectRaw('COUNT(spatial_patok_hm_point.id) as patok_hm_kanan_count')
            ->value('patok_hm_kanan_count');

        // PATOK HM MEDIAN
        $patok_hm_median_count = DB::table('spatial_patok_hm_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_hm_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_patok_hm_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->selectRaw('COUNT(spatial_patok_hm_point.id) as patok_hm_median_count')
            ->value('patok_hm_median_count');

        return json_encode([
            'kiri' => $patok_hm_kiri_count,
            'kanan' => $patok_hm_kanan_count,
            'median' => $patok_hm_median_count
        ]);
    }

    public function getPatokLJ(Request $request)
    {
        // PATOK LJ KIRI
        $patok_lj_kiri_count = DB::table('spatial_patok_lj_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_lj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_patok_lj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->selectRaw('COUNT(spatial_patok_lj_point.id) as patok_lj_kiri_count')
            ->value('patok_lj_kiri_count');

        // PATOK LJ KANAN
        $patok_lj_kanan_count = DB::table('spatial_patok_lj_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_lj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_patok_lj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->selectRaw('COUNT(spatial_patok_lj_point.id) as patok_lj_kanan_count')
            ->value('patok_lj_kanan_count');

        // PATOK LJ MEDIAN
        $patok_lj_median_count = DB::table('spatial_patok_lj_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_lj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_patok_lj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->selectRaw('COUNT(spatial_patok_lj_point.id) as patok_lj_median_count')
            ->value('patok_lj_median_count');

        return json_encode([
            'kiri' => $patok_lj_kiri_count,
            'kanan' => $patok_lj_kanan_count,
            'median' => $patok_lj_median_count
        ]);
    }

    public function getPatokRMJ(Request $request)
    {
        // PATOK RMJ KIRI
        $patok_rmj_kiri_count = DB::table('spatial_patok_rmj_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_rmj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_patok_rmj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->selectRaw('COUNT(spatial_patok_rmj_point.id) as patok_rmj_kiri_count')
            ->value('patok_rmj_kiri_count');

        // PATOK RMJ KANAN
        $patok_rmj_kanan_count = DB::table('spatial_patok_rmj_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_rmj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_patok_rmj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->selectRaw('COUNT(spatial_patok_rmj_point.id) as patok_rmj_kanan_count')
            ->value('patok_rmj_kanan_count');

        // PATOK RMJ MEDIAN
        $patok_rmj_median_count = DB::table('spatial_patok_rmj_point')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_rmj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_patok_rmj_point.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->selectRaw('COUNT(spatial_patok_rmj_point.id) as patok_rmj_median_count')
            ->value('patok_rmj_median_count');

        return json_encode([
            'kiri' => $patok_rmj_kiri_count,
            'kanan' => $patok_rmj_kanan_count,
            'median' => $patok_rmj_median_count
        ]);
    }

    public function getLHR(Request $request)
    {
        // LHR GOLONGAN I KIRI
        $lhr__kiri_sum_golongan_i = DB::table('spatial_lhr_polygon')
            ->selectRaw('SUM(CAST(spatial_lhr_polygon.gol_i AS NUMERIC)) as lhr__kiri_sum_golongan_i')
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Overlaps(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lhr_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->where('spatial_lhr_polygon.segmen_tol', 'MAINROAD')
            ->value('lhr__kiri_sum_golongan_i');

        // LHR GOLONGAN I KANAN
        $lhr__kanan_sum_golongan_i = DB::table('spatial_lhr_polygon')
            ->selectRaw('SUM(CAST(spatial_lhr_polygon.gol_i AS NUMERIC)) as lhr__kanan_sum_golongan_i')
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Overlaps(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lhr_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->where('spatial_lhr_polygon.segmen_tol', 'MAINROAD')
            ->value('lhr__kanan_sum_golongan_i');

        // LHR GOLONGAN II KIRI
        $lhr__kiri_sum_golongan_ii = DB::table('spatial_lhr_polygon')
            ->selectRaw('SUM(CAST(spatial_lhr_polygon.gol_ii AS NUMERIC)) as lhr__kiri_sum_golongan_ii')
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Overlaps(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lhr_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->where('spatial_lhr_polygon.segmen_tol', 'MAINROAD')
            ->value('lhr__kiri_sum_golongan_ii');

        // LHR GOLONGAN II KANAN
        $lhr__kanan_sum_golongan_ii = DB::table('spatial_lhr_polygon')
            ->selectRaw('SUM(CAST(spatial_lhr_polygon.gol_ii AS NUMERIC)) as lhr__kanan_sum_golongan_ii')
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Overlaps(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lhr_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->where('spatial_lhr_polygon.segmen_tol', 'MAINROAD')
            ->value('lhr__kanan_sum_golongan_ii');

        // LHR GOLONGAN III KIRI
        $lhr__kiri_sum_golongan_iii = DB::table('spatial_lhr_polygon')
            ->selectRaw('SUM(CAST(spatial_lhr_polygon.gol_iii AS NUMERIC)) as lhr__kiri_sum_golongan_iii')
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Overlaps(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lhr_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->where('spatial_lhr_polygon.segmen_tol', 'MAINROAD')
            ->value('lhr__kiri_sum_golongan_iii');

        // LHR GOLONGAN III KANAN
        $lhr__kanan_sum_golongan_iii = DB::table('spatial_lhr_polygon')
            ->selectRaw('SUM(CAST(spatial_lhr_polygon.gol_iii AS NUMERIC)) as lhr__kanan_sum_golongan_iii')
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Overlaps(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lhr_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->where('spatial_lhr_polygon.segmen_tol', 'MAINROAD')
            ->value('lhr__kanan_sum_golongan_iii');

        // LHR GOLONGAN IV KIRI
        $lhr__kiri_sum_golongan_iv = DB::table('spatial_lhr_polygon')
            ->selectRaw('SUM(CAST(spatial_lhr_polygon.gol_iv AS NUMERIC)) as lhr__kiri_sum_golongan_iv')
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Overlaps(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lhr_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->where('spatial_lhr_polygon.segmen_tol', 'MAINROAD')
            ->value('lhr__kiri_sum_golongan_iv');

        // LHR GOLONGAN IV KANAN
        $lhr__kanan_sum_golongan_iv = DB::table('spatial_lhr_polygon')
            ->selectRaw('SUM(CAST(spatial_lhr_polygon.gol_iv AS NUMERIC)) as lhr__kanan_sum_golongan_iv')
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Overlaps(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lhr_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->where('spatial_lhr_polygon.segmen_tol', 'MAINROAD')
            ->value('lhr__kanan_sum_golongan_iv');

        // LHR GOLONGAN V KIRI
        $lhr__kiri_sum_golongan_v = DB::table('spatial_lhr_polygon')
            ->selectRaw('SUM(CAST(spatial_lhr_polygon.gol_v AS NUMERIC)) as lhr__kiri_sum_golongan_v')
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Overlaps(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lhr_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->where('spatial_lhr_polygon.segmen_tol', 'MAINROAD')
            ->value('lhr__kiri_sum_golongan_v');

        // LHR GOLONGAN V KANAN
        $lhr__kanan_sum_golongan_v = DB::table('spatial_lhr_polygon')
            ->selectRaw('SUM(CAST(spatial_lhr_polygon.gol_v AS NUMERIC)) as lhr__kanan_sum_golongan_v')
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Overlaps(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lhr_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->where('spatial_lhr_polygon.segmen_tol', 'MAINROAD')
            ->value('lhr__kanan_sum_golongan_v');

        return json_encode([
            'golongan_i' => [
                'kiri' => $lhr__kiri_sum_golongan_i,
                'kanan' => $lhr__kanan_sum_golongan_i
            ],
            'golongan_ii' => [
                'kiri' => $lhr__kiri_sum_golongan_ii,
                'kanan' => $lhr__kanan_sum_golongan_ii
            ],
            'golongan_iii' => [
                'kiri' => $lhr__kiri_sum_golongan_iii,
                'kanan' => $lhr__kanan_sum_golongan_iii
            ],
            'golongan_iv' => [
                'kiri' => $lhr__kiri_sum_golongan_iv,
                'kanan' => $lhr__kanan_sum_golongan_iv
            ],
            'golongan_v' => [
                'kiri' => $lhr__kiri_sum_golongan_v,
                'kanan' => $lhr__kanan_sum_golongan_v
            ],
        ]);
    }

    public function getPagarOperasional(Request $request)
    {
        // PAGAR OPERASIONAL KIRI
        $pagar_operasional_kiri = DB::table('spatial_pagar_operasional_line')
            ->selectRaw('COUNT(spatial_pagar_operasional_line.id) as count, SUM(ST_Length(spatial_pagar_operasional_line.geom::geography)) as length')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_operasional_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_pagar_operasional_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get();

        // PAGAR OPERASIONAL KANAN
        $pagar_operasional_kanan = DB::table('spatial_pagar_operasional_line')
            ->selectRaw('COUNT(spatial_pagar_operasional_line.id) as count, SUM(ST_Length(spatial_pagar_operasional_line.geom::geography)) as length')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_pagar_operasional_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_pagar_operasional_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get();

        // PAGAR OPERASIONAL MEDIAN
        $pagar_operasional_median = DB::table('spatial_pagar_operasional_line')
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

    public function getMarkaJalan(Request $request)
    {
        // MARKA JALAN KIRI
        $marka_jalan_kiri = DB::table('spatial_marka_line')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_marka_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id', 1)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->selectRaw('COUNT(spatial_marka_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_marka_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->get();

        // MARKA JALAN KANAN
        $marka_jalan_kanan = DB::table('spatial_marka_line')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_marka_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id', 1)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->selectRaw('COUNT(spatial_marka_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_marka_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->get();

        // MARKA JALAN MEDIAN
        $marka_jalan_median = DB::table('spatial_marka_line')
            ->selectRaw('COUNT(spatial_marka_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_marka_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_marka_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id', 1)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->selectRaw('COUNT(spatial_marka_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_marka_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->get();

        return json_encode([
            'kiri' => $marka_jalan_kiri,
            'kanan' => $marka_jalan_kanan,
            'median' => $marka_jalan_median
        ]);
    }

    public function getTeleponAtasTanah(Request $request)
    {
        // TELEPON ATAS TANAH KIRI
        $telepon_atas_tanah_kiri = DB::table('spatial_telepon_atastanah_line')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_telepon_atastanah_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_telepon_atastanah_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id', 1)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->selectRaw('COUNT(spatial_telepon_atastanah_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_telepon_atastanah_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->get();

        // TELEPON ATAS TANAH KANAN
        $telepon_atas_tanah_kanan = DB::table('spatial_telepon_atastanah_line')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_telepon_atastanah_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_telepon_atastanah_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id', 1)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->selectRaw('COUNT(spatial_telepon_atastanah_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_telepon_atastanah_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->get();

        // TELEPON ATAS TANAH MEDIAN
        $telepon_atas_tanah_median = DB::table('spatial_telepon_atastanah_line')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_telepon_atastanah_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_telepon_atastanah_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id', 1)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->selectRaw('COUNT(spatial_telepon_atastanah_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_telepon_atastanah_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->get();

        return json_encode([
            'kiri' => $telepon_atas_tanah_kiri,
            'kanan' => $telepon_atas_tanah_kanan,
            'median' => $telepon_atas_tanah_median
        ]);
    }

    public function getTeleponBawahTanah(Request $request)
    {
        // TELEPON BAWAH TANAH KIRI
        $telepon_bawah_tanah_kiri = DB::table('spatial_telepon_bawahtanah_line')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_telepon_bawahtanah_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_telepon_bawahtanah_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id', 1)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->selectRaw('COUNT(spatial_telepon_bawahtanah_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_telepon_bawahtanah_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->get();

        // TELEPON BAWAH TANAH KANAN
        $telepon_bawah_tanah_kanan = DB::table('spatial_telepon_bawahtanah_line')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_telepon_bawahtanah_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_telepon_bawahtanah_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id', 1)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->selectRaw('COUNT(spatial_telepon_bawahtanah_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_telepon_bawahtanah_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->get();

        // TELEPON BAWAH TANAH MEDIAN
        $telepon_bawah_tanah_median = DB::table('spatial_telepon_bawahtanah_line')
            ->join('spatial_segmen_leger_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_telepon_bawahtanah_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->join('spatial_segmen_perlengkapan_polygon', function ($join) {
                $join->on(DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_telepon_bawahtanah_line.geom::geometry)'), '=', DB::raw('true'));
            })
            ->where('spatial_segmen_leger_polygon.id', 1)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->selectRaw('COUNT(spatial_telepon_bawahtanah_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_telepon_bawahtanah_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->get();

        return json_encode([
            'kiri' => $telepon_bawah_tanah_kiri,
            'kanan' => $telepon_bawah_tanah_kanan,
            'median' => $telepon_bawah_tanah_median
        ]);
    }

    public function getListrikAtasTanah(Request $request)
    {
        // LISTRIK ATAS KIRI
        $listrik_atas_kiri = DB::table('spatial_listrik_atastanah_line')
            ->selectRaw('COUNT(spatial_listrik_atastanah_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_listrik_atastanah_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_listrik_atastanah_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_listrik_atastanah_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get();

        // LISTRIK ATAS KANAN
        $listrik_atas_kanan = DB::table('spatial_listrik_atastanah_line')
            ->selectRaw('COUNT(spatial_listrik_atastanah_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_listrik_atastanah_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_listrik_atastanah_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_listrik_atastanah_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get();

        // LISTRIK ATAS MEDIAN
        $listrik_atas_median = DB::table('spatial_listrik_atastanah_line')
            ->selectRaw('COUNT(spatial_listrik_atastanah_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_listrik_atastanah_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_listrik_atastanah_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_listrik_atastanah_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->get();

        return json_encode([
            'kiri' => $listrik_atas_kiri,
            'kanan' => $listrik_atas_kanan,
            'median' => $listrik_atas_median
        ]);
    }

    public function getListrikBawahTanah(Request $request)
    {
        // LISTRIK BAWAH TANAH KIRI
        $listrik_bawah_tanah_kiri = DB::table('spatial_listrik_bawahtanah_line')
            ->selectRaw('COUNT(spatial_listrik_bawahtanah_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_listrik_bawahtanah_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_listrik_bawahtanah_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_listrik_bawahtanah_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get();

        // LISTRIK BAWAH TANAH KANAN
        $listrik_bawah_tanah_kanan = DB::table('spatial_listrik_bawahtanah_line')
            ->selectRaw('COUNT(spatial_listrik_bawahtanah_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_listrik_bawahtanah_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_listrik_bawahtanah_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_listrik_bawahtanah_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get();

        // LISTRIK BAWAH TANAH MEDIAN
        $listrik_bawah_tanah_median = DB::table('spatial_listrik_bawahtanah_line')
            ->selectRaw('COUNT(spatial_listrik_bawahtanah_line.id) as count, SUM(ST_Length(ST_Intersection(spatial_listrik_bawahtanah_line.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as length')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_listrik_bawahtanah_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_listrik_bawahtanah_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->get();

        return json_encode([
            'kiri' => $listrik_bawah_tanah_kiri,
            'kanan' => $listrik_bawah_tanah_kanan,
            'median' => $listrik_bawah_tanah_median
        ]);
    }

    public function getManhole(Request $request)
    {
        // MANHOLE KIRI
        $manhole_kiri = DB::table('spatial_manhole_point')
            ->selectRaw('*')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_manhole_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_manhole_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get();

        // MANHOLE KANAN
        $manhole_kanan = DB::table('spatial_manhole_point')
            ->selectRaw('*')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_manhole_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_manhole_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get();

        // MANHOLE MEDIAN
        $manhole_median = DB::table('spatial_manhole_point')
            ->selectRaw('*')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_manhole_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_manhole_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->get();

        return json_encode([
            'kiri' => $manhole_kiri,
            'kanan' => $manhole_kanan,
            'median' => $manhole_median
        ]);
    }

    public function getSaluran(Request $request)
    {
        // SALURAN KIRI
        $saluran_kiri = DB::table('spatial_saluran_line')
            ->selectRaw('spatial_saluran_line.jenis_material, spatial_saluran_line.panjang, spatial_saluran_line.tinggi, spatial_saluran_line.kondisi')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_saluran_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_saluran_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get();

        // SALURAN KANAN
        $saluran_kanan = DB::table('spatial_saluran_line')
            ->selectRaw('spatial_saluran_line.jenis_material, spatial_saluran_line.panjang, spatial_saluran_line.tinggi, spatial_saluran_line.kondisi')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_saluran_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_saluran_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get();

        // SALURAN MEDIAN
        $saluran_median = DB::table('spatial_saluran_line')
            ->selectRaw('spatial_saluran_line.jenis_material, spatial_saluran_line.panjang, spatial_saluran_line.tinggi, spatial_saluran_line.kondisi')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_saluran_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_saluran_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'MEDIAN')
            ->get();

        return json_encode([
            'kiri' => $saluran_kiri,
            'kanan' => $saluran_kanan,
            'median' => $saluran_median
        ]);
    }

    public function getBadanJalan(Request $request)
    {
        //// BADAN JALAN LAPIS PERMUKAAN KIRI
        // BADAN JALAN LAPIS PERMUKAAN LAJUR 1 KIRI
        $badan_jalan_lapis_permukaan_lajur_1_kiri = DB::table('spatial_lapis_permukaan_polygon')
            ->selectRaw('spatial_lapis_permukaan_polygon.tebal, spatial_lapis_permukaan_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 1')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        // BADAN JALAN LAPIS PERMUKAAN LAJUR 2 KIRI
        $badan_jalan_lapis_permukaan_lajur_2_kiri = DB::table('spatial_lapis_permukaan_polygon')
            ->selectRaw('spatial_lapis_permukaan_polygon.tebal, spatial_lapis_permukaan_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 2')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        // BADAN JALAN LAPIS PERMUKAAN LAJUR 3 KIRI
        $badan_jalan_lapis_permukaan_lajur_3_kiri = DB::table('spatial_lapis_permukaan_polygon')
            ->selectRaw('spatial_lapis_permukaan_polygon.tebal, spatial_lapis_permukaan_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 3')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        // BADAN JALAN LAPIS PERMUKAAN LAJUR 4 KIRI
        $badan_jalan_lapis_permukaan_lajur_4_kiri = DB::table('spatial_lapis_permukaan_polygon')
            ->selectRaw('spatial_lapis_permukaan_polygon.tebal, spatial_lapis_permukaan_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 4')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        //// BADAN JALAN LAPIS PERMUKAAN KANAN
        // BADAN JALAN LAPIS PERMUKAAN LAJUR 1 KANAN
        $badan_jalan_lapis_permukaan_lajur_1_kanan = DB::table('spatial_lapis_permukaan_polygon')
            ->selectRaw('spatial_lapis_permukaan_polygon.tebal, spatial_lapis_permukaan_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 1')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        // BADAN JALAN LAPIS PERMUKAAN LAJUR 2 KANAN
        $badan_jalan_lapis_permukaan_lajur_2_kanan = DB::table('spatial_lapis_permukaan_polygon')
            ->selectRaw('spatial_lapis_permukaan_polygon.tebal, spatial_lapis_permukaan_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 2')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        // BADAN JALAN LAPIS PERMUKAAN LAJUR 3 KANAN
        $badan_jalan_lapis_permukaan_lajur_3_kanan = DB::table('spatial_lapis_permukaan_polygon')
            ->selectRaw('spatial_lapis_permukaan_polygon.tebal, spatial_lapis_permukaan_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 3')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        // BADAN JALAN LAPIS PERMUKAAN LAJUR 4 KANAN
        $badan_jalan_lapis_permukaan_lajur_4_kanan = DB::table('spatial_lapis_permukaan_polygon')
            ->selectRaw('spatial_lapis_permukaan_polygon.tebal, spatial_lapis_permukaan_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 4')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        //// BADAN JALAN LAPIS PONDASI ATAS KIRI
        // BADAN JALAN LAPIS PONDASI ATAS LAJUR 1 KIRI
        $badan_jalan_lapis_pondasi_atas_lajur_1_kiri = DB::table('spatial_lapis_pondasi_atas1_polygon')
            ->selectRaw('spatial_lapis_pondasi_atas1_polygon.tebal, spatial_lapis_pondasi_atas1_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 1')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        // BADAN JALAN LAPIS PONDASI ATAS LAJUR 2 KIRI
        $badan_jalan_lapis_pondasi_atas_lajur_2_kiri = DB::table('spatial_lapis_pondasi_atas1_polygon')
            ->selectRaw('spatial_lapis_pondasi_atas1_polygon.tebal, spatial_lapis_pondasi_atas1_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 2')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        // BADAN JALAN LAPIS PONDASI ATAS LAJUR 3 KIRI
        $badan_jalan_lapis_pondasi_atas_lajur_3_kiri = DB::table('spatial_lapis_pondasi_atas1_polygon')
            ->selectRaw('spatial_lapis_pondasi_atas1_polygon.tebal, spatial_lapis_pondasi_atas1_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 3')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        // BADAN JALAN LAPIS PONDASI ATAS LAJUR 4 KIRI
        $badan_jalan_lapis_pondasi_atas_lajur_4_kiri = DB::table('spatial_lapis_pondasi_atas1_polygon')
            ->selectRaw('spatial_lapis_pondasi_atas1_polygon.tebal, spatial_lapis_pondasi_atas1_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 4')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        //// BADAN JALAN LAPIS PONDASI ATAS KANAN
        // BADAN JALAN LAPIS PONDASI ATAS LAJUR 1 KANAN
        $badan_jalan_lapis_pondasi_atas_lajur_1_kanan = DB::table('spatial_lapis_pondasi_atas1_polygon')
            ->selectRaw('spatial_lapis_pondasi_atas1_polygon.tebal, spatial_lapis_pondasi_atas1_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 1')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        // BADAN JALAN LAPIS PONDASI ATAS LAJUR 2 KANAN
        $badan_jalan_lapis_pondasi_atas_lajur_2_kanan = DB::table('spatial_lapis_pondasi_atas1_polygon')
            ->selectRaw('spatial_lapis_pondasi_atas1_polygon.tebal, spatial_lapis_pondasi_atas1_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 2')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        // BADAN JALAN LAPIS PONDASI ATAS LAJUR 3 KANAN
        $badan_jalan_lapis_pondasi_atas_lajur_3_kanan = DB::table('spatial_lapis_pondasi_atas1_polygon')
            ->selectRaw('spatial_lapis_pondasi_atas1_polygon.tebal, spatial_lapis_pondasi_atas1_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 3')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        // BADAN JALAN LAPIS PONDASI ATAS LAJUR 4 KANAN
        $badan_jalan_lapis_pondasi_atas_lajur_4_kanan = DB::table('spatial_lapis_pondasi_atas1_polygon')
            ->selectRaw('spatial_lapis_pondasi_atas1_polygon.tebal, spatial_lapis_pondasi_atas1_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 4')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        //// BADAN JALAN LAPIS PONDASI BAWAH KIRI
        // BADAN JALAN LAPIS PONDASI BAWAH LAJUR 1 KIRI
        $badan_jalan_lapis_pondasi_bawah_lajur_1_kiri = DB::table('spatial_lapis_pondasi_bawah_polygon')
            ->selectRaw('spatial_lapis_pondasi_bawah_polygon.tebal, spatial_lapis_pondasi_bawah_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 1')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        // BADAN JALAN LAPIS PONDASI BAWAH LAJUR 2 KIRI
        $badan_jalan_lapis_pondasi_bawah_lajur_2_kiri = DB::table('spatial_lapis_pondasi_bawah_polygon')
            ->selectRaw('spatial_lapis_pondasi_bawah_polygon.tebal, spatial_lapis_pondasi_bawah_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 2')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        // BADAN JALAN LAPIS PONDASI BAWAH LAJUR 3 KIRI
        $badan_jalan_lapis_pondasi_bawah_lajur_3_kiri = DB::table('spatial_lapis_pondasi_bawah_polygon')
            ->selectRaw('spatial_lapis_pondasi_bawah_polygon.tebal, spatial_lapis_pondasi_bawah_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 3')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        // BADAN JALAN LAPIS PONDASI BAWAH LAJUR 4 KIRI
        $badan_jalan_lapis_pondasi_bawah_lajur_4_kiri = DB::table('spatial_lapis_pondasi_bawah_polygon')
            ->selectRaw('spatial_lapis_pondasi_bawah_polygon.tebal, spatial_lapis_pondasi_bawah_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 4')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        //// BADAN JALAN LAPIS PONDASI BAWAH KANAN
        // BADAN JALAN LAPIS PONDASI BAWAH LAJUR 1 KANAN
        $badan_jalan_lapis_pondasi_bawah_lajur_1_kanan = DB::table('spatial_lapis_pondasi_bawah_polygon')
            ->selectRaw('spatial_lapis_pondasi_bawah_polygon.tebal, spatial_lapis_pondasi_bawah_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 1')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        // BADAN JALAN LAPIS PONDASI BAWAH LAJUR 2 KANAN
        $badan_jalan_lapis_pondasi_bawah_lajur_2_kanan = DB::table('spatial_lapis_pondasi_bawah_polygon')
            ->selectRaw('spatial_lapis_pondasi_bawah_polygon.tebal, spatial_lapis_pondasi_bawah_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 2')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        // BADAN JALAN LAPIS PONDASI BAWAH LAJUR 3 KANAN
        $badan_jalan_lapis_pondasi_bawah_lajur_3_kanan = DB::table('spatial_lapis_pondasi_bawah_polygon')
            ->selectRaw('spatial_lapis_pondasi_bawah_polygon.tebal, spatial_lapis_pondasi_bawah_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 3')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        // BADAN JALAN LAPIS PONDASI BAWAH LAJUR 4 KANAN
        $badan_jalan_lapis_pondasi_bawah_lajur_4_kanan = DB::table('spatial_lapis_pondasi_bawah_polygon')
            ->selectRaw('spatial_lapis_pondasi_bawah_polygon.tebal, spatial_lapis_pondasi_bawah_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 4')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        return json_encode([
            'lapis_permukaan' => [
                'kiri' => [
                    'lajur_1' => $badan_jalan_lapis_permukaan_lajur_1_kiri,
                    'lajur_2' => $badan_jalan_lapis_permukaan_lajur_2_kiri,
                    'lajur_3' => $badan_jalan_lapis_permukaan_lajur_3_kiri,
                    'lajur_4' => $badan_jalan_lapis_permukaan_lajur_4_kiri,
                ],
                'kanan' => [
                    'lajur_1' => $badan_jalan_lapis_permukaan_lajur_1_kanan,
                    'lajur_2' => $badan_jalan_lapis_permukaan_lajur_2_kanan,
                    'lajur_3' => $badan_jalan_lapis_permukaan_lajur_3_kanan,
                    'lajur_4' => $badan_jalan_lapis_permukaan_lajur_4_kanan,
                ],
            ],
            'lapis_pondasi_atas' => [
                'kiri' => [
                    'lajur_1' => $badan_jalan_lapis_pondasi_atas_lajur_1_kiri,
                    'lajur_2' => $badan_jalan_lapis_pondasi_atas_lajur_2_kiri,
                    'lajur_3' => $badan_jalan_lapis_pondasi_atas_lajur_3_kiri,
                    'lajur_4' => $badan_jalan_lapis_pondasi_atas_lajur_4_kiri,
                ],
                'kanan' => [
                    'lajur_1' => $badan_jalan_lapis_pondasi_atas_lajur_1_kanan,
                    'lajur_2' => $badan_jalan_lapis_pondasi_atas_lajur_2_kanan,
                    'lajur_3' => $badan_jalan_lapis_pondasi_atas_lajur_3_kanan,
                    'lajur_4' => $badan_jalan_lapis_pondasi_atas_lajur_4_kanan,
                ],
            ],
            'lapis_pondasi_bawah' => [
                'kiri' => [
                    'lajur_1' => $badan_jalan_lapis_pondasi_bawah_lajur_1_kiri,
                    'lajur_2' => $badan_jalan_lapis_pondasi_bawah_lajur_2_kiri,
                    'lajur_3' => $badan_jalan_lapis_pondasi_bawah_lajur_3_kiri,
                    'lajur_4' => $badan_jalan_lapis_pondasi_bawah_lajur_4_kiri,
                ],
                'kanan' => [
                    'lajur_1' => $badan_jalan_lapis_pondasi_bawah_lajur_1_kanan,
                    'lajur_2' => $badan_jalan_lapis_pondasi_bawah_lajur_2_kanan,
                    'lajur_3' => $badan_jalan_lapis_pondasi_bawah_lajur_3_kanan,
                    'lajur_4' => $badan_jalan_lapis_pondasi_bawah_lajur_4_kanan,
                ],
            ],
        ]);
    }

    public function getMedian(Request $request)
    {
        // MEDIAN
        $median = DB::table('spatial_segmen_konstruksi_polygon')
            ->selectRaw('spatial_segmen_konstruksi_polygon.lebar')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_segmen_konstruksi_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'MEDIAN')
            ->get()->first();

        return json_encode($median);
    }

    public function getBahuJalan(Request $request)
    {
        // BAHU JALAN LAPIS PERMUKAAN KIRI LUAR
        $bahu_jalan_lapis_permukaan_kiri_luar = DB::table('spatial_lapis_permukaan_polygon')
            ->selectRaw('spatial_lapis_permukaan_polygon.tebal, spatial_lapis_permukaan_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU LUAR')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        // BAHU JALAN LAPIS PERMUKAAN KANAN LUAR
        $bahu_jalan_lapis_permukaan_kanan_luar = DB::table('spatial_lapis_permukaan_polygon')
            ->selectRaw('spatial_lapis_permukaan_polygon.tebal, spatial_lapis_permukaan_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU LUAR')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        // BAHU KANAN LAPIS PERMUKAAN KIRI DALAM
        $bahu_jalan_lapis_permukaan_kiri_dalam = DB::table('spatial_lapis_permukaan_polygon')
            ->selectRaw('spatial_lapis_permukaan_polygon.tebal, spatial_lapis_permukaan_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU DALAM')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        // BAHU JALAN LAPIS PERMUKAAN KANAN DALAM
        $bahu_jalan_lapis_permukaan_kanan_dalam = DB::table('spatial_lapis_permukaan_polygon')
            ->selectRaw('spatial_lapis_permukaan_polygon.tebal, spatial_lapis_permukaan_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_permukaan_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU DALAM')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        //BAHU JALAN LAPIS PONDASI ATAS KIRI LUAR
        $bahu_jalan_lapis_pondasi_atas_kiri_luar = DB::table('spatial_lapis_pondasi_atas1_polygon')
            ->selectRaw('spatial_lapis_pondasi_atas1_polygon.tebal, spatial_lapis_pondasi_atas1_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU LUAR')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        //BAHU JALAN LAPIS PONDASI ATAS KANAN LUAR
        $bahu_jalan_lapis_pondasi_atas_kanan_luar = DB::table('spatial_lapis_pondasi_atas1_polygon')
            ->selectRaw('spatial_lapis_pondasi_atas1_polygon.tebal, spatial_lapis_pondasi_atas1_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU LUAR')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        //BAHU JALAN LAPIS PONDASI ATAS KIRI DALAM
        $bahu_jalan_lapis_pondasi_atas_kiri_dalam = DB::table('spatial_lapis_pondasi_atas1_polygon')
            ->selectRaw('spatial_lapis_pondasi_atas1_polygon.tebal, spatial_lapis_pondasi_atas1_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU DALAM')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        //BAHU JALAN LAPIS PONDASI ATAS KANAN DALAM
        $bahu_jalan_lapis_pondasi_atas_kanan_dalam = DB::table('spatial_lapis_pondasi_atas1_polygon')
            ->selectRaw('spatial_lapis_pondasi_atas1_polygon.tebal, spatial_lapis_pondasi_atas1_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_atas1_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU DALAM')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        //BAHU JALAN LAPIS PONDASI BAWAH KIRI LUAR
        $bahu_jalan_lapis_pondasi_bawah_kiri_luar = DB::table('spatial_lapis_pondasi_bawah_polygon')
            ->selectRaw('spatial_lapis_pondasi_bawah_polygon.tebal, spatial_lapis_pondasi_bawah_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU LUAR')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        //BAHU JALAN LAPIS PONDASI BAWAH KANAN LUAR
        $bahu_jalan_lapis_pondasi_bawah_kanan_luar = DB::table('spatial_lapis_pondasi_bawah_polygon')
            ->selectRaw('spatial_lapis_pondasi_bawah_polygon.tebal, spatial_lapis_pondasi_bawah_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU LUAR')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        //BAHU JALAN LAPIS PONDASI BAWAH KIRI DALAM
        $bahu_jalan_lapis_pondasi_bawah_kiri_dalam = DB::table('spatial_lapis_pondasi_bawah_polygon')
            ->selectRaw('spatial_lapis_pondasi_bawah_polygon.tebal, spatial_lapis_pondasi_bawah_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU DALAM')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KIRI')
            ->get()->first();

        //BAHU JALAN LAPIS PONDASI BAWAH KANAN DALAM
        $bahu_jalan_lapis_pondasi_bawah_kanan_dalam = DB::table('spatial_lapis_pondasi_bawah_polygon')
            ->selectRaw('spatial_lapis_pondasi_bawah_polygon.tebal, spatial_lapis_pondasi_bawah_polygon.jenis')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_konstruksi_polygon', DB::raw('ST_Intersects(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_polygon.geom::geometry, spatial_lapis_pondasi_bawah_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU DALAM')
            ->where('spatial_segmen_perlengkapan_polygon.jalur', 'JALUR KANAN')
            ->get()->first();

        return json_encode([
            'lapis_permukaan' => [
                'kiri_luar' => $bahu_jalan_lapis_permukaan_kiri_luar,
                'kanan_luar' => $bahu_jalan_lapis_permukaan_kanan_luar,
                'kiri_dalam' => $bahu_jalan_lapis_permukaan_kiri_dalam,
                'kanan_dalam' => $bahu_jalan_lapis_permukaan_kanan_dalam
            ],
            'lapis_pondasi_atas' => [
                'kiri_luar' => $bahu_jalan_lapis_pondasi_atas_kiri_luar,
                'kanan_luar' => $bahu_jalan_lapis_pondasi_atas_kanan_luar,
                'kiri_dalam' => $bahu_jalan_lapis_pondasi_atas_kiri_dalam,
                'kanan_dalam' => $bahu_jalan_lapis_pondasi_atas_kanan_dalam
            ],
            'lapis_pondasi_bawah' => [
                'kiri_luar' => $bahu_jalan_lapis_pondasi_bawah_kiri_luar,
                'kanan_luar' => $bahu_jalan_lapis_pondasi_bawah_kanan_luar,
                'kiri_dalam' => $bahu_jalan_lapis_pondasi_bawah_kiri_dalam,
                'kanan_dalam' => $bahu_jalan_lapis_pondasi_bawah_kanan_dalam
            ]
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

    public function getRumahKabel(Request $request)
    {
        //count
        $rumah_kabel_count = DB::table('spatial_rumah_kabel_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_rumah_kabel_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->get();

        return json_encode($rumah_kabel_count);
    }

    public function getBronjong(Request $request)
    {
        //count
        $bronjong_count = DB::table('spatial_bronjong_line')
            ->selectRaw('spatial_bronjong_line.jenis_material, spatial_bronjong_line.ukuran_panjang, spatial_bronjong_line.kondisi')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_bronjong_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->get();

        return json_encode($bronjong_count);
    }

    public function getJembatan(Request $request)
    {
        $jembatan = DB::table('spatial_jembatan_point as jembatan')
            ->selectRaw('jembatan.km, jembatan.panjang, jembatan.lebar, jembatan.luas, jembatan.absis_x, jembatan.ordinat_y')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, jembatan.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->get();

        return json_encode($jembatan);
    }

    public function getTiangListrik(Request $request)
    {
        //count
        $tiang_listrik_count = DB::table('spatial_tiang_listrik_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_tiang_listrik_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->get();

        return json_encode($tiang_listrik_count);
    }

    public function getBoxCulvert(Request $request)
    {
        //count
        $box_culvert_count = DB::table('spatial_box_culvert_line')
            ->selectRaw('spatial_box_culvert_line.panjang, spatial_box_culvert_line.lebar, spatial_box_culvert_line.tinggi')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_box_culvert_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->get();

        return json_encode($box_culvert_count);
    }

    public function getLuasRumija(Request $request)
    {
        //count
        $luas_rumija = DB::table('spatial_ruwasja_polygon')
            ->selectRaw('sum(ST_Area(ST_Intersection(spatial_ruwasja_polygon.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as luas')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_ruwasja_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->first();

        return json_encode($luas_rumija);
    }

    public function getLuasBadanJalan(Request $request)
    {
        //HITUNG yang memiliki nilai bagian_jalan: lajur 1/lajur 2/lajur tambahan
        $luas_badan_jalan = DB::table('spatial_segmen_konstruksi_polygon')
            ->selectRaw('sum(ST_Area(ST_Intersection(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as luas')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_segmen_konstruksi_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 1')
            // ->Where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR 2')
            // ->orWhere('spatial_segmen_konstruksi_polygon.bagian_jalan', 'LAJUR TAMBAHAN')
            ->first();

        return json_encode($luas_badan_jalan);
    }

    public function getLuasBahuJalan(Request $request)
    {
        $luas_bahu_jalan = DB::table('spatial_segmen_konstruksi_polygon')
            ->selectRaw('sum(ST_Area(ST_Intersection(spatial_segmen_konstruksi_polygon.geom::geometry, spatial_segmen_leger_polygon.geom::geometry)::geography)) as luas')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_segmen_konstruksi_polygon.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU LUAR')
            // ->orWhere('spatial_segmen_konstruksi_polygon.bagian_jalan', 'BAHU DALAM')
            ->first();

        return json_encode($luas_bahu_jalan);
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

    public function getMarkaMembujur(Request $request)
    {
        //KIRI
        $marka_membujur_kiri = DB::table('spatial_marka_membujur_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_membujur_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_membujur_line.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $marka_membujur_median = DB::table('spatial_marka_membujur_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_membujur_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_membujur_line.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $marka_membujur_kanan = DB::table('spatial_marka_membujur_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_membujur_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_membujur_line.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $marka_membujur_kiri,
            'median' => $marka_membujur_median,
            'kanan' => $marka_membujur_kanan
        ]);
    }

    public function getMarkaMelintang(Request $request)
    {
        //KIRI
        $marka_melintang_kiri = DB::table('spatial_marka_melintang_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_melintang_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_melintang_line.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $marka_melintang_median = DB::table('spatial_marka_melintang_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_melintang_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_melintang_line.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $marka_melintang_kanan = DB::table('spatial_marka_melintang_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_melintang_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_melintang_line.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $marka_melintang_kiri,
            'median' => $marka_melintang_median,
            'kanan' => $marka_melintang_kanan
        ]);
    }

    public function getMarkaSerong(Request $request)
    {
        //KIRI
        $marka_serong_kiri = DB::table('spatial_marka_serong_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_serong_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_serong_line.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $marka_serong_median = DB::table('spatial_marka_serong_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_serong_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_serong_line.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $marka_serong_kanan = DB::table('spatial_marka_serong_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_serong_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_serong_line.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $marka_serong_kiri,
            'median' => $marka_serong_median,
            'kanan' => $marka_serong_kanan
        ]);
    }

    public function getMarkaKotakKuning(Request $request)
    {
        //KIRI
        $marka_kotak_kuning_kiri = DB::table('spatial_marka_kotak_kuning_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_kotak_kuning_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_kotak_kuning_line.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $marka_kotak_kuning_median = DB::table('spatial_marka_kotak_kuning_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_kotak_kuning_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_kotak_kuning_line.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $marka_kotak_kuning_kanan = DB::table('spatial_marka_kotak_kuning_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_kotak_kuning_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_kotak_kuning_line.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $marka_kotak_kuning_kiri,
            'median' => $marka_kotak_kuning_median,
            'kanan' => $marka_kotak_kuning_kanan
        ]);
    }

    public function getMarkaLainnya(Request $request)
    {
        //KIRI
        $marka_lainnya_kiri = DB::table('spatial_marka_lainnya_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_lainnya_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_lainnya_line.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $marka_lainnya_median = DB::table('spatial_marka_lainnya_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_lainnya_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_lainnya_line.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $marka_lainnya_kanan = DB::table('spatial_marka_lainnya_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_marka_lainnya_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_marka_lainnya_line.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $marka_lainnya_kiri,
            'median' => $marka_lainnya_median,
            'kanan' => $marka_lainnya_kanan
        ]);
    }

    public function getPakuJalan(Request $request)
    {
        //KIRI
        $paku_jalan_kiri = DB::table('spatial_paku_jalan_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_paku_jalan_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_paku_jalan_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $paku_jalan_median = DB::table('spatial_paku_jalan_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_paku_jalan_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_paku_jalan_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $paku_jalan_kanan = DB::table('spatial_paku_jalan_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_paku_jalan_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_paku_jalan_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $paku_jalan_kiri,
            'median' => $paku_jalan_median,
            'kanan' => $paku_jalan_kanan
        ]);
    }

    public function getConcreteBarrier(Request $request)
    {
        //KIRI
        $concrete_barrier_kiri = DB::table('spatial_concrete_barrier_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_concrete_barrier_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_concrete_barrier_line.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $concrete_barrier_median = DB::table('spatial_concrete_barrier_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_concrete_barrier_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_concrete_barrier_line.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $concrete_barrier_kanan = DB::table('spatial_concrete_barrier_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_concrete_barrier_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_concrete_barrier_line.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $concrete_barrier_kiri,
            'median' => $concrete_barrier_median,
            'kanan' => $concrete_barrier_kanan
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

    public function getSafetyRoller(Request $request)
    {
        //KIRI
        $safety_roller_kiri = DB::table('spatial_safety_roller_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_safety_roller_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_safety_roller_line.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $safety_roller_median = DB::table('spatial_safety_roller_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_safety_roller_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_safety_roller_line.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $safety_roller_kanan = DB::table('spatial_safety_roller_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_safety_roller_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_safety_roller_line.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $safety_roller_kiri,
            'median' => $safety_roller_median,
            'kanan' => $safety_roller_kanan
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

    public function getPatokLaluLintas(Request $request)
    {
        //KIRI
        $patok_lalu_lintas_kiri = DB::table('spatial_patok_lalu_lintas_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_lalu_lintas_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_patok_lalu_lintas_point.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $patok_lalu_lintas_median = DB::table('spatial_patok_lalu_lintas_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_lalu_lintas_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_patok_lalu_lintas_point.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $patok_lalu_lintas_kanan = DB::table('spatial_patok_lalu_lintas_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_lalu_lintas_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_patok_lalu_lintas_point.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $patok_lalu_lintas_kiri,
            'median' => $patok_lalu_lintas_median,
            'kanan' => $patok_lalu_lintas_kanan
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

    public function getJalurPenghentianDarurat(Request $request)
    {
        //KIRI
        $jalur_penghentian_darurat_kiri = DB::table('spatial_jalur_penghentian_darurat_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_jalur_penghentian_darurat_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_jalur_penghentian_darurat_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $jalur_penghentian_darurat_median = DB::table('spatial_jalur_penghentian_darurat_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_jalur_penghentian_darurat_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_jalur_penghentian_darurat_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $jalur_penghentian_darurat_kanan = DB::table('spatial_jalur_penghentian_darurat_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_jalur_penghentian_darurat_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_jalur_penghentian_darurat_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $jalur_penghentian_darurat_kiri,
            'median' => $jalur_penghentian_darurat_median,
            'kanan' => $jalur_penghentian_darurat_kanan
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

    public function getPenahanSilau(Request $request)
    {
        //KIRI
        $penahan_silau_kiri = DB::table('spatial_penahan_silau_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_penahan_silau_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_penahan_silau_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $penahan_silau_median = DB::table('spatial_penahan_silau_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_penahan_silau_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_penahan_silau_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $penahan_silau_kanan = DB::table('spatial_penahan_silau_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_penahan_silau_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkan_jalan_polygon.geom::geometry, spatial_penahan_silau_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $penahan_silau_kiri,
            'median' => $penahan_silau_median,
            'kanan' => $penahan_silau_kanan
        ]);
    }

    public function getPeredamBising(Request $request)
    {
        //KIRI
        $peredam_bising_kiri = DB::table('spatial_peredam_bising_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_peredam_bising_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_peredam_bising_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $peredam_bising_median = DB::table('spatial_peredam_bising_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_peredam_bising_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_peredam_bising_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $peredam_bising_kanan = DB::table('spatial_peredam_bising_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_peredam_bising_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_peredam_bising_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $peredam_bising_kiri,
            'median' => $peredam_bising_median,
            'kanan' => $peredam_bising_kanan
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

    public function getSpeedgun(Request $request)
    {
        //KIRI
        $speedgun_kiri = DB::table('spatial_speedgun_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_speedgun_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_speedgun_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $speedgun_median = DB::table('spatial_speedgun_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_speedgun_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_speedgun_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $speedgun_kanan = DB::table('spatial_speedgun_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_speedgun_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_speedgun_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $speedgun_kiri,
            'median' => $speedgun_median,
            'kanan' => $speedgun_kanan
        ]);
    }

    public function getPengamanSaluranUdaraTeganganTinggi(Request $request)
    {
        //KIRI
        $pengaman_saluran_udara_tegangan_tinggi_kiri = DB::table('spatial_pengaman_saluran_udara_tegangan_tinggi_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_pengaman_saluran_udara_tegangan_tinggi_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_pengaman_saluran_udara_tegangan_tinggi_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $pengaman_saluran_udara_tegangan_tinggi_median = DB::table('spatial_pengaman_saluran_udara_tegangan_tinggi_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_pengaman_saluran_udara_tegangan_tinggi_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_pengaman_saluran_udara_tegangan_tinggi_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $pengaman_saluran_udara_tegangan_tinggi_kanan = DB::table('spatial_pengaman_saluran_udara_tegangan_tinggi_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_pengaman_saluran_udara_tegangan_tinggi_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_pengaman_saluran_udara_tegangan_tinggi_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $pengaman_saluran_udara_tegangan_tinggi_kiri,
            'median' => $pengaman_saluran_udara_tegangan_tinggi_median,
            'kanan' => $pengaman_saluran_udara_tegangan_tinggi_kanan
        ]);
    }

    public function getPatokUtilitas(Request $request)
    {
        //KIRI
        $patok_utilitas_kiri = DB::table('spatial_patok_utilitas_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_utilitas_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_patok_utilitas_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $patok_utilitas_median = DB::table('spatial_patok_utilitas_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_utilitas_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_patok_utilitas_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $patok_utilitas_kanan = DB::table('spatial_patok_utilitas_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_patok_utilitas_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_patok_utilitas_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $patok_utilitas_kiri,
            'median' => $patok_utilitas_median,
            'kanan' => $patok_utilitas_kanan
        ]);
    }

    public function getPapanPengumumanKepemilikanTanahNegara(Request $request)
    {
        //KIRI
        $papan_pengumuman_kepemilikan_tanah_negara_kiri = DB::table('spatial_papan_pengumuman_kepemilikan_tanah_negara_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_papan_pengumuman_kepemilikan_tanah_negara_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_papan_pengumuman_kepemilikan_tanah_negara_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $papan_pengumuman_kepemilikan_tanah_negara_median = DB::table('spatial_papan_pengumuman_kepemilikan_tanah_negara_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_papan_pengumuman_kepemilikan_tanah_negara_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_papan_pengumuman_kepemilikan_tanah_negara_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $papan_pengumuman_kepemilikan_tanah_negara_kanan = DB::table('spatial_papan_pengumuman_kepemilikan_tanah_negara_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_papan_pengumuman_kepemilikan_tanah_negara_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_papan_pengumuman_kepemilikan_tanah_negara_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $papan_pengumuman_kepemilikan_tanah_negara_kiri,
            'median' => $papan_pengumuman_kepemilikan_tanah_negara_median,
            'kanan' => $papan_pengumuman_kepemilikan_tanah_negara_kanan
        ]);
    }

    public function getReklame(Request $request)
    {
        //KIRI
        $reklame_kiri = DB::table('spatial_reklame_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_reklame_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_reklame_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $reklame_median = DB::table('spatial_reklame_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_reklame_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_reklame_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $reklame_kanan = DB::table('spatial_reklame_point')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Contains(spatial_segmen_leger_polygon.geom::geometry, spatial_reklame_point.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Contains(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_reklame_point.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $reklame_kiri,
            'median' => $reklame_median,
            'kanan' => $reklame_kanan
        ]);
    }

    public function getFasilitasPutarBalik(Request $request)
    {
        //KIRI
        $fasilitas_putar_balik_kiri = DB::table('spatial_fasilitas_putar_balik_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_fasilitas_putar_balik_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_fasilitas_putar_balik_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KIRI')
            ->get();

        //MEDIAN
        $fasilitas_putar_balik_median = DB::table('spatial_fasilitas_putar_balik_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_fasilitas_putar_balik_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_fasilitas_putar_balik_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'MEDIAN')
            ->get();

        //KANAN
        $fasilitas_putar_balik_kanan = DB::table('spatial_fasilitas_putar_balik_line')
            ->selectRaw('count(*) as count')
            ->join('spatial_segmen_leger_polygon', DB::raw('ST_Intersects(spatial_segmen_leger_polygon.geom::geometry, spatial_fasilitas_putar_balik_line.geom::geometry)'), '=', DB::raw('true'))
            ->join('spatial_segmen_perlengkapan_jalan_polygon', DB::raw('ST_Intersects(spatial_segmen_perlengkapan_jalan_polygon.geom::geometry, spatial_fasilitas_putar_balik_line.geom::geometry)'), '=', DB::raw('true'))
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->where('spatial_segmen_perlengkapan_jalan_polygon.jalur', 'JALUR KANAN')
            ->get();

        return json_encode([
            'kiri' => $fasilitas_putar_balik_kiri,
            'median' => $fasilitas_putar_balik_median,
            'kanan' => $fasilitas_putar_balik_kanan
        ]);
    }

    public function getTitikSegmen(Request $request)
    {
        $titik_awal_segmen_ruas_jalan = DB::table('spatial_segmen_leger_polygon')
            ->where('spatial_segmen_leger_polygon.id_leger', $request->leger_id)
            ->selectRaw('spatial_segmen_leger_polygon.km as km')
            ->first();

        $leger_id_selanjutnya = 'M.' . str_pad((int) substr($request->leger_id, 2) + 1, 3, '0', STR_PAD_LEFT);

        $titik_akhir_segmen_ruas_jalan = DB::table('spatial_segmen_leger_polygon')
            ->where('spatial_segmen_leger_polygon.id_leger', $leger_id_selanjutnya)
            ->selectRaw('spatial_segmen_leger_polygon.km as km')
            ->first();

        $titik_akhir_segmen_akhir = DB::table('spatial_segmen_seksi_polygon')
            ->where('spatial_segmen_seksi_polygon.jalan_tol_id', 1)
            ->selectRaw('spatial_segmen_seksi_polygon.km_akhir as km')
            ->first();

        $titik_awal_km = (float) str_replace('+', '.', $titik_awal_segmen_ruas_jalan->km);

        if ($titik_akhir_segmen_ruas_jalan) {
            $titik_akhir_km = (float) str_replace('+', '.', $titik_akhir_segmen_ruas_jalan->km);
        } else {
            $titik_akhir_km = (float) str_replace('+', '.', $titik_akhir_segmen_akhir->km);
        }

        $titik_ikat_patok_km = DB::table('spatial_patok_km_point')
            ->whereRaw("CAST(REPLACE(km, '+', '.') AS FLOAT) BETWEEN ? AND ?", [$titik_awal_km, $titik_akhir_km])
            ->where('km', 'like', '%+000')
            ->selectRaw('km, ST_X(geom::geometry) as x, ST_Y(geom::geometry) as y, ST_Z(geom::geometry) as z')
            ->first();

        return json_encode([
            'titik_awal_segmen' => $titik_awal_segmen_ruas_jalan ?? null,
            'titik_akhir_segmen' => $titik_akhir_segmen_ruas_jalan ?? $titik_akhir_segmen_akhir ?? null,
            'titik_ikat_patok_km' => $titik_ikat_patok_km ?? null,
        ]);
    }

    public function getDataJalanUtama(Request $request)
    {
        $jalan_tol_id = $request->jalan_tol_id;

        $awal = (int) substr($request->leger_id_awal, 2);
        $akhir = (int) substr($request->leger_id_akhir, 2);

        // jika awal lebih besar dari akhir
        if ($awal > $akhir) {
            $awal = (int) substr($request->leger_id_akhir, 2);
            $akhir = (int) substr($request->leger_id_awal, 2);
        }


        $leger = DB::table('leger_jalan')
            ->select('leger_jalan.id', 'leger_jalan.kode_leger')
            ->join('leger', 'leger_jalan.leger_id', '=', 'leger.id')
            ->where('leger.jalan_tol_id', '=', $jalan_tol_id)
            ->where('leger_jalan.kode_leger', 'like', 'M%')
            ->whereBetween(DB::raw('CAST(SUBSTRING(leger_jalan.kode_leger, 3) AS INT)'), [$awal, $akhir])
            ->orderBy(DB::raw('CAST(SUBSTRING(leger_jalan.kode_leger, 3) AS INT)'), 'asc')
            ->get();

        $data = [];
        foreach ($leger as $l) {
            $request = new Request();
            $request->merge(['leger_id' => $l->kode_leger]);

            $data[$l->kode_leger] = [
                "data_jalan_identifikasi" => DataJalanIdentifikasi::where("id_leger_jalan", $l->id)->first(),

                "data_jalan_teknik1" => [
                    "lahan_rumija" => DataJalanTeknik1::where("id_leger_jalan", $l->id)->where("uraian", "lahan rumija")->first(),
                    "badan_jalan" => DataJalanTeknik1::where("id_leger_jalan", $l->id)->where("uraian", "badan jalan")->first(),
                    "bahu_jalan" => DataJalanTeknik1::where("id_leger_jalan", $l->id)->where("uraian", "bahu jalan")->first(),
                ],

                "data_jalan_teknik2_lapis" => [
                    "lapis_permukaan" => [
                        "tebal" => DataJalanTeknik2Lapis::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "1")->where("uraian", "TEBAL")->first(),
                        "jenis" => DataJalanTeknik2Lapis::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "1")->where("uraian", "JENIS")->first(),
                    ],
                    "lapis_pondasi_atas" => [
                        "tebal" => DataJalanTeknik2Lapis::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "2")->where("uraian", "TEBAL")->first(),
                        "jenis" => DataJalanTeknik2Lapis::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "2")->where("uraian", "JENIS")->first(),
                    ],
                    "lapis_pondasi_bawah" => [
                        "tebal" => DataJalanTeknik2Lapis::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "3")->where("uraian", "TEBAL")->first(),
                        "jenis" => DataJalanTeknik2Lapis::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "3")->where("uraian", "JENIS")->first(),
                    ],
                ],

                "data_jalan_teknik2_median" => [
                    "lebar" => DataJalanTeknik2Median::where("id_leger_jalan", $l->id)->where("uraian", "LEBAR")->first(),
                ],

                "data_jalan_teknik2_bahujalan" => [
                    "lapis_permukaan" => [
                        "tebal" => DataJalanTeknik2Bahujalan::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "1")->where("uraian", "TEBAL")->first(),
                        "jenis" => DataJalanTeknik2Bahujalan::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "1")->where("uraian", "JENIS")->first(),
                    ],
                    "lapis_pondasi_atas" => [
                        "tebal" => DataJalanTeknik2Bahujalan::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "2")->where("uraian", "TEBAL")->first(),
                        "jenis" => DataJalanTeknik2Bahujalan::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "2")->where("uraian", "JENIS")->first(),
                    ],
                    "lapis_pondasi_bawah" => [
                        "tebal" => DataJalanTeknik2Bahujalan::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "3")->where("uraian", "TEBAL")->first(),
                        "jenis" => DataJalanTeknik2Bahujalan::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "3")->where("uraian", "JENIS")->first(),
                    ],
                ],

                "data_jalan_teknik3_goronggorong" => [
                    "jenis_material" => DataJalanTeknik3Goronggorong::where("id_leger_jalan", $l->id)->where("uraian", "JENIS MATERIAL")->first(),
                    "ukuran_panjang" => DataJalanTeknik3Goronggorong::where("id_leger_jalan", $l->id)->where("uraian", "UKURAN PANJANG")->first(),
                    "kondisi" => DataJalanTeknik3Goronggorong::where("id_leger_jalan", $l->id)->where("uraian", "KONDISI")->first(),
                ],

                "data_jalan_teknik3_saluran" => [
                    "manhole" => [
                        "jenis_material" => DataJalanTeknik3Saluran::where("id_leger_jalan", $l->id)->where("jenis_saluran_id", "4")->where("uraian", "JENIS MATERIAL")->first(),
                        "ukuran_pokok" => DataJalanTeknik3Saluran::where("id_leger_jalan", $l->id)->where("jenis_saluran_id", "4")->where("uraian", "UKURAN POKOK")->first(),
                        "kondisi" => DataJalanTeknik3Saluran::where("id_leger_jalan", $l->id)->where("jenis_saluran_id", "4")->where("uraian", "KONDISI")->first(),
                    ],
                ],

                "data_jalan_teknik4" => [
                    "pagar_operasional" => DataJalanTeknik4::where("id_leger_jalan", $l->id)->where("uraian", "PAGAR OPERASIONAL")->first(),
                    "patok_km" => DataJalanTeknik4::where("id_leger_jalan", $l->id)->where("uraian", "PATOK KM")->first(),
                    "patok_hm" => DataJalanTeknik4::where("id_leger_jalan", $l->id)->where("uraian", "PATOK HM")->first(),
                    "patok_lj" => DataJalanTeknik4::where("id_leger_jalan", $l->id)->where("uraian", "PATOK LJ")->first(),
                    "patok_rmj" => DataJalanTeknik4::where("id_leger_jalan", $l->id)->where("uraian", "PATOK RMJ")->first(),
                ],

                "data_jalan_teknik5_utilitas" => [
                    "jaringan_listrik_bawah_tanah" => DataJalanTeknik5Utilitas::where("id_leger_jalan", $l->id)->where("uraian", "JARINGAN LISTRIK DIBAWAH TANAH")->first(),
                    "jaringan_telekomunikasi_bawah_tanah" => DataJalanTeknik5Utilitas::where("id_leger_jalan", $l->id)->where("uraian", "JARINGAN TELEKOMUNIKASI DIBAWAH TANAH")->first(),
                ],

                "data_jalan_lhr" => [
                    "golongan_i" => DataJalanLHR::where("id_leger_jalan", $l->id)->where("uraian", "GOLONGAN I")->first(),
                    "golongan_ii" => DataJalanLHR::where("id_leger_jalan", $l->id)->where("uraian", "GOLONGAN II")->first(),
                    "golongan_iii" => DataJalanLHR::where("id_leger_jalan", $l->id)->where("uraian", "GOLONGAN III")->first(),
                    "golongan_iv" => DataJalanLHR::where("id_leger_jalan", $l->id)->where("uraian", "GOLONGAN IV")->first(),
                    "golongan_v" => DataJalanLHR::where("id_leger_jalan", $l->id)->where("uraian", "GOLONGAN V")->first(),
                ],

                "data_jalan_geometrik" => [
                    "lebar_rumija" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "LEBAR RUMIJA")->first(),
                    "kelandaian_kiri" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "KELANDAIAN KIRI")->first(),
                    "kelandaian_kanan" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "KELANDAIAN KANAN")->first(),
                    "crossfall_kiri" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "CROSSFALL KIRI")->first(),
                    "crossfall_kanan" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "CROSSFALL KANAN")->first(),
                    "superelevasi" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "SUPERELEVASI")->first(),
                    "radius" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "RADIUS")->first(),
                ],

                "data_jalan_situasi" => [
                    "terrain_kiri" => DataJalanSituasi::where("id_leger_jalan", $l->id)->where("uraian", "TERRAIN KIRI")->first(),
                    "terrain_kanan" => DataJalanSituasi::where("id_leger_jalan", $l->id)->where("uraian", "TERRAIN KANAN")->first(),
                    "tataguna_lahan_kiri" => DataJalanSituasi::where("id_leger_jalan", $l->id)->where("uraian", "TATAGUNA LAHAN KIRI")->first(),
                    "tataguna_lahan_kanan" => DataJalanSituasi::where("id_leger_jalan", $l->id)->where("uraian", "TATAGUNA LAHAN KANAN")->first(),
                ],
            ];
        }
        return response()->json($data);
    }

    public function getAllDataJalanUtama(Request $request)
    {
        $jalan_tol_id = $request->jalan_tol_id;


        $leger = DB::table('leger_jalan')
            ->select('leger_jalan.id', 'leger_jalan.kode_leger')
            ->join('leger', 'leger_jalan.leger_id', '=', 'leger.id')
            ->where('leger.jalan_tol_id', '=', $jalan_tol_id)
            ->where('leger_jalan.kode_leger', 'like', 'M%')
            ->orderBy(DB::raw('CAST(SUBSTRING(leger_jalan.kode_leger, 3) AS INT)'), 'asc')
            ->get();

        $data = [];
        foreach ($leger as $l) {
            $request = new Request();
            $request->merge(['leger_id' => $l->kode_leger]);

            $data[$l->kode_leger] = [
                "data_jalan_identifikasi" => DataJalanIdentifikasi::where("id_leger_jalan", $l->id)->first(),

                "data_jalan_teknik1" => [
                    "lahan_rumija" => DataJalanTeknik1::where("id_leger_jalan", $l->id)->where("uraian", "lahan rumija")->first(),
                    "badan_jalan" => DataJalanTeknik1::where("id_leger_jalan", $l->id)->where("uraian", "badan jalan")->first(),
                    "bahu_jalan" => DataJalanTeknik1::where("id_leger_jalan", $l->id)->where("uraian", "bahu jalan")->first(),
                ],

                "data_jalan_teknik2_lapis" => [
                    "lapis_permukaan" => [
                        "tebal" => DataJalanTeknik2Lapis::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "1")->where("uraian", "TEBAL")->first(),
                        "jenis" => DataJalanTeknik2Lapis::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "1")->where("uraian", "JENIS")->first(),
                    ],
                    "lapis_pondasi_atas" => [
                        "tebal" => DataJalanTeknik2Lapis::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "2")->where("uraian", "TEBAL")->first(),
                        "jenis" => DataJalanTeknik2Lapis::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "2")->where("uraian", "JENIS")->first(),
                    ],
                    "lapis_pondasi_bawah" => [
                        "tebal" => DataJalanTeknik2Lapis::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "3")->where("uraian", "TEBAL")->first(),
                        "jenis" => DataJalanTeknik2Lapis::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "3")->where("uraian", "JENIS")->first(),
                    ],
                ],

                "data_jalan_teknik2_median" => [
                    "lebar" => DataJalanTeknik2Median::where("id_leger_jalan", $l->id)->where("uraian", "LEBAR")->first(),
                ],

                "data_jalan_teknik2_bahujalan" => [
                    "lapis_permukaan" => [
                        "tebal" => DataJalanTeknik2Bahujalan::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "1")->where("uraian", "TEBAL")->first(),
                        "jenis" => DataJalanTeknik2Bahujalan::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "1")->where("uraian", "JENIS")->first(),
                    ],
                    "lapis_pondasi_atas" => [
                        "tebal" => DataJalanTeknik2Bahujalan::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "2")->where("uraian", "TEBAL")->first(),
                        "jenis" => DataJalanTeknik2Bahujalan::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "2")->where("uraian", "JENIS")->first(),
                    ],
                    "lapis_pondasi_bawah" => [
                        "tebal" => DataJalanTeknik2Bahujalan::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "3")->where("uraian", "TEBAL")->first(),
                        "jenis" => DataJalanTeknik2Bahujalan::where("id_leger_jalan", $l->id)->where("jenis_lapis_id", "3")->where("uraian", "JENIS")->first(),
                    ],
                ],

                "data_jalan_teknik3_goronggorong" => [
                    "jenis_material" => DataJalanTeknik3Goronggorong::where("id_leger_jalan", $l->id)->where("uraian", "JENIS MATERIAL")->first(),
                    "ukuran_panjang" => DataJalanTeknik3Goronggorong::where("id_leger_jalan", $l->id)->where("uraian", "UKURAN PANJANG")->first(),
                    "kondisi" => DataJalanTeknik3Goronggorong::where("id_leger_jalan", $l->id)->where("uraian", "KONDISI")->first(),
                ],

                "data_jalan_teknik3_saluran" => [
                    "manhole" => [
                        "jenis_material" => DataJalanTeknik3Saluran::where("id_leger_jalan", $l->id)->where("jenis_saluran_id", "4")->where("uraian", "JENIS MATERIAL")->first(),
                        "ukuran_pokok" => DataJalanTeknik3Saluran::where("id_leger_jalan", $l->id)->where("jenis_saluran_id", "4")->where("uraian", "UKURAN POKOK")->first(),
                        "kondisi" => DataJalanTeknik3Saluran::where("id_leger_jalan", $l->id)->where("jenis_saluran_id", "4")->where("uraian", "KONDISI")->first(),
                    ],
                ],

                "data_jalan_teknik4" => [
                    "pagar_operasional" => DataJalanTeknik4::where("id_leger_jalan", $l->id)->where("uraian", "PAGAR OPERASIONAL")->first(),
                    "patok_km" => DataJalanTeknik4::where("id_leger_jalan", $l->id)->where("uraian", "PATOK KM")->first(),
                    "patok_hm" => DataJalanTeknik4::where("id_leger_jalan", $l->id)->where("uraian", "PATOK HM")->first(),
                    "patok_lj" => DataJalanTeknik4::where("id_leger_jalan", $l->id)->where("uraian", "PATOK LJ")->first(),
                    "patok_rmj" => DataJalanTeknik4::where("id_leger_jalan", $l->id)->where("uraian", "PATOK RMJ")->first(),
                ],

                "data_jalan_teknik5_utilitas" => [
                    "jaringan_listrik_bawah_tanah" => DataJalanTeknik5Utilitas::where("id_leger_jalan", $l->id)->where("uraian", "JARINGAN LISTRIK DIBAWAH TANAH")->first(),
                    "jaringan_telekomunikasi_bawah_tanah" => DataJalanTeknik5Utilitas::where("id_leger_jalan", $l->id)->where("uraian", "JARINGAN TELEKOMUNIKASI DIBAWAH TANAH")->first(),
                ],

                "data_jalan_lhr" => [
                    "golongan_i" => DataJalanLHR::where("id_leger_jalan", $l->id)->where("uraian", "GOLONGAN I")->first(),
                    "golongan_ii" => DataJalanLHR::where("id_leger_jalan", $l->id)->where("uraian", "GOLONGAN II")->first(),
                    "golongan_iii" => DataJalanLHR::where("id_leger_jalan", $l->id)->where("uraian", "GOLONGAN III")->first(),
                    "golongan_iv" => DataJalanLHR::where("id_leger_jalan", $l->id)->where("uraian", "GOLONGAN IV")->first(),
                    "golongan_v" => DataJalanLHR::where("id_leger_jalan", $l->id)->where("uraian", "GOLONGAN V")->first(),
                ],

                "data_jalan_geometrik" => [
                    "lebar_rumija" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "LEBAR RUMIJA")->first(),
                    "kelandaian_kiri" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "KELANDAIAN KIRI")->first(),
                    "kelandaian_kanan" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "KELANDAIAN KANAN")->first(),
                    "crossfall_kiri" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "CROSSFALL KIRI")->first(),
                    "crossfall_kanan" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "CROSSFALL KANAN")->first(),
                    "superelevasi" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "SUPERELEVASI")->first(),
                    "radius" => DataJalanGeometrik::where("id_leger_jalan", $l->id)->where("uraian", "RADIUS")->first(),
                ],

                "data_jalan_situasi" => [
                    "terrain_kiri" => DataJalanSituasi::where("id_leger_jalan", $l->id)->where("uraian", "TERRAIN KIRI")->first(),
                    "terrain_kanan" => DataJalanSituasi::where("id_leger_jalan", $l->id)->where("uraian", "TERRAIN KANAN")->first(),
                    "tataguna_lahan_kiri" => DataJalanSituasi::where("id_leger_jalan", $l->id)->where("uraian", "TATAGUNA LAHAN KIRI")->first(),
                    "tataguna_lahan_kanan" => DataJalanSituasi::where("id_leger_jalan", $l->id)->where("uraian", "TATAGUNA LAHAN KANAN")->first(),
                ],
            ];
        }
        return response()->json($data);
    }

    public function getLegerImage()
    {
        $data = DataJalanGambar::select("*")
            ->get();
        return response()->json($data);
    }

    public function getDataJalanUtamaAll($jalan_tol_id)
    {

        $leger = DB::table('leger_jalan')
            ->select('leger_jalan.id', 'leger_jalan.kode_leger')
            ->join('leger', 'leger_jalan.leger_id', '=', 'leger.id')
            ->where('leger.jalan_tol_id', '=', $jalan_tol_id)
            ->where('leger_jalan.kode_leger', 'like', 'M%')
            ->orderBy(DB::raw('CAST(SUBSTRING(leger_jalan.kode_leger, 3) AS INT)'), 'asc')
            ->get();

        //init
        $data = [];
        foreach ($leger as $l) {
            $request = new Request();
            $request->merge(['leger_id' => $l->kode_leger]);

            $data[$l->kode_leger] = [
                'administratif' => json_decode($this->getAdministratif($request), true),
                'rambu_lalulintas_count' => json_decode($this->getRambuLaluLintas($request), true),
                'gorong_gorong' => json_decode($this->getGorongGorong($request), true),
                'data_geometrik_jalan' => json_decode($this->getDataGeometrikJalan($request), true),
                'patok_km_count' => json_decode($this->getPatokKM($request), true),
                'patok_hm_count' => json_decode($this->getPatokHM($request), true),
                'patok_lj_count' => json_decode($this->getPatokLJ($request), true),
                'patok_rmj_count' => json_decode($this->getPatokRMJ($request), true),
                'lhr_sum' => json_decode($this->getLHR($request), true),
                'listrik_bawah_tanah' => json_decode($this->getListrikBawahTanah($request), true),
                'manhole' => json_decode($this->getManhole($request), true),
                'badan_jalan' => json_decode($this->getBadanJalan($request), true),
                'median' => json_decode($this->getMedian($request), true),
                'bahu_jalan' => json_decode($this->getBahuJalan($request), true),
                'pagar_operasional' => json_decode($this->getPagarOperasional($request), true),
                'marka_jalan' => json_decode($this->getMarkaJalan($request), true),
                'titik_segmen' => json_decode($this->getTitikSegmen($request), true),
                'bronjong' => json_decode($this->getBronjong($request), true),
                'luas_rumija' => json_decode($this->getLuasRumija($request), true),
                'luas_badan_jalan' => json_decode($this->getLuasBadanJalan($request), true),
                'luas_bahu_jalan' => json_decode($this->getLuasBahuJalan($request), true),
                'administratif' => json_decode($this->getAdministratif($request), true),
                'rumah_kabel' => json_decode($this->getRumahKabel($request), true),
                'telepon_bawah_tanah' => json_decode($this->getTeleponBawahTanah($request), true),
                'jembatan' => json_decode($this->getJembatan($request), true),
                'tiang_listrik' => json_decode($this->getTiangListrik($request), true),
            ];
        }

        return $data;
    }

    public function populateLegerJalan(Request $request)
    {
        //placeholder
        $jalan_tol_id = $request->jalan_tol_id;

        $leger = LegerJalan::select('leger_jalan.id', 'leger_jalan.kode_leger')
            ->join('leger', 'leger_jalan.leger_id', '=', 'leger.id')
            ->where('leger.jalan_tol_id', $jalan_tol_id)
            ->where('leger_jalan.kode_leger', 'like', 'M%')
            ->orderByRaw('CAST(SUBSTRING(leger_jalan.kode_leger FROM 3) AS INT)')
            ->get()
            ->toArray();

        $tol = JalanTol::select("tahun")
            ->where("id", $jalan_tol_id)
            ->get()->first();

        $data = $this->getDataJalanUtamaAll($jalan_tol_id);
        $zipped = array_map(null, $leger, $data);

        foreach ($zipped as [$l, $d]) {

            DataJalanIdentifikasi::where('id_leger_jalan', isset($l['id']) ? $l['id'] : null)
                ->delete();
            DataJalanTeknik1::where('id_leger_jalan', isset($l['id']) ? $l['id'] : null)
                ->delete();
            DataJalanTeknik2Lapis::where('id_leger_jalan', isset($l['id']) ? $l['id'] : null)
                ->delete();
            DataJalanTeknik2Median::where('id_leger_jalan', isset($l['id']) ? $l['id'] : null)
                ->delete();
            DataJalanTeknik2Bahujalan::where('id_leger_jalan', isset($l['id']) ? $l['id'] : null)
                ->delete();
            DataJalanTeknik3Goronggorong::where('id_leger_jalan', isset($l['id']) ? $l['id'] : null)
                ->delete();
            DataJalanTeknik3Saluran::where('id_leger_jalan', isset($l['id']) ? $l['id'] : null)
                ->delete();
            DataJalanTeknik4::where('id_leger_jalan', isset($l['id']) ? $l['id'] : null)
                ->delete();
            DataJalanTeknik5Utilitas::where('id_leger_jalan', isset($l['id']) ? $l['id'] : null)
                ->delete();
            DataJalanLHR::where('id_leger_jalan', isset($l['id']) ? $l['id'] : null)
                ->delete();
            DataJalanGeometrik::where('id_leger_jalan', isset($l['id']) ? $l['id'] : null)
                ->delete();
            DataJalanSituasi::where('id_leger_jalan', isset($l['id']) ? $l['id'] : null)
                ->delete();

            $data_jalan_identifikasi =
                [
                    'kode_provinsi_id' => isset($d['administratif']['kode_prov']) ? $d['administratif']['kode_prov'] : null,
                    'kode_kabkot_id' => isset($d['administratif']['kode_kab']) ? $d['administratif']['kode_kab'] : null,
                    'kode_kecamatan_id' => isset($d['administratif']['kode_kec']) ? $d['administratif']['kode_kec'] : null,
                    'kode_desakel_id' => isset($d['administratif']['kode_desa']) ? $d['administratif']['kode_desa'] : null,
                    'nomor_ruas' => null,
                    'nomor_seksi' => null,
                    'deskripsi_seksi' => null,
                    'lokasi' => null,
                    'titik_ikat_leger_kode' => null,
                    'titik_ikat_leger_x' => null,
                    'titik_ikat_leger_y' => null,
                    'titik_ikat_leger_z' => null,
                    'titik_ikat_leger_deskripsi' => null,
                    'titik_ikat_patok_kode' => null,
                    'titik_ikat_patok_deskripsi' => null,
                    'titik_awal_segmen_kode' => null,
                    'titik_awal_segmen_x' => null,
                    'titik_awal_segmen_y' => null,
                    'titik_awal_segmen_z' => null,
                    'titik_awal_segmen_deskripsi' => null,
                    'titik_akhir_segmen_kode' => null,
                    'titik_akhir_segmen_x' => null,
                    'titik_akhir_segmen_y' => null,
                    'titik_akhir_segmen_z' => null,
                    'titik_akhir_segmen_deskripsi' => null,
                    'id_leger_jalan' => isset($l['id']) ? $l['id'] : null,
                    'titik_ikat_patok_km' => isset($d['titik_segmen']['titik_ikat_patok_km']['km']) ? $d['titik_segmen']['titik_ikat_patok_km']['km'] : null,
                    'titik_ikat_patok_x' => isset($d['titik_segmen']['titik_ikat_patok_km']['x']) ? $d['titik_segmen']['titik_ikat_patok_km']['x'] : null,
                    'titik_ikat_patok_y' => isset($d['titik_segmen']['titik_ikat_patok_km']['y']) ? $d['titik_segmen']['titik_ikat_patok_km']['y'] : null,
                    'titik_ikat_patok_z' => isset($d['titik_segmen']['titik_ikat_patok_km']['z']) ? $d['titik_segmen']['titik_ikat_patok_km']['z'] : null,
                    'titik_awal_segmen_km' => isset($d['titik_segmen']['titik_awal_segmen']) ? $d['titik_segmen']['titik_awal_segmen']['km'] : null,
                    'titik_akhir_segmen_km' => isset($d['titik_segmen']['titik_akhir_segmen']) ? $d['titik_segmen']['titik_akhir_segmen']['km'] : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            DataJalanIdentifikasi::insert($data_jalan_identifikasi);

            $data_jalan_teknik1 =
                [
                    [
                        'id_leger_jalan' => isset($l['id']) ? $l['id'] : null,
                        'tahun' => isset($tol['tahun']) ? $tol['tahun'] : null,
                        'uraian' => 'lahan rumija',
                        'luas' => isset($d['luas_rumija']['luas']) ? $d['luas_rumija']['luas'] : null,
                        'data_perolehan' => 'HASIL LAPANGAN',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => isset($l['id']) ? $l['id'] : null,
                        'tahun' => isset($tol['tahun']) ? $tol['tahun'] : null,
                        'uraian' => 'badan jalan',
                        'luas' => isset($d['luas_badan_jalan']['luas']) ? $d['luas_badan_jalan']['luas'] : null,
                        'data_perolehan' => 'HASIL LAPANGAN',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => isset($l['id']) ? $l['id'] : null,
                        'tahun' => isset($tol['tahun']) ? $tol['tahun'] : null,
                        'uraian' => 'bahu jalan',
                        'luas' => isset($d['luas_badan_jalan']['luas']) ? $d['luas_badan_jalan']['luas'] : null,
                        'data_perolehan' => 'HASIL LAPANGAN',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
            DataJalanTeknik1::insert($data_jalan_teknik1);

            $data_jalan_teknik2_lapis =
                [
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'jenis_lapis_id' => 1,
                        'uraian' => 'TEBAL',
                        'nilai_ki_lajur1' => $d['badan_jalan']['lapis_permukaan']['kiri']['lajur_1']['tebal'] ?? null,
                        'nilai_ki_lajur2' => $d['badan_jalan']['lapis_permukaan']['kiri']['lajur_2']['tebal'] ?? null,
                        'nilai_ki_lajur3' => $d['badan_jalan']['lapis_permukaan']['kiri']['lajur_3']['tebal'] ?? null,
                        'nilai_ki_lajur4' => $d['badan_jalan']['lapis_permukaan']['kiri']['lajur_4']['tebal'] ?? null,
                        'nilai_ka_lajur1' => $d['badan_jalan']['lapis_permukaan']['kanan']['lajur_1']['tebal'] ?? null,
                        'nilai_ka_lajur2' => $d['badan_jalan']['lapis_permukaan']['kanan']['lajur_2']['tebal'] ?? null,
                        'nilai_ka_lajur3' => $d['badan_jalan']['lapis_permukaan']['kanan']['lajur_3']['tebal'] ?? null,
                        'nilai_ka_lajur4' => $d['badan_jalan']['lapis_permukaan']['kanan']['lajur_4']['tebal'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'jenis_lapis_id' => 1,
                        'uraian' => 'JENIS',
                        'nilai_ki_lajur1' => $d['badan_jalan']['lapis_permukaan']['kiri']['lajur_1']['jenis'] ?? null,
                        'nilai_ki_lajur2' => $d['badan_jalan']['lapis_permukaan']['kiri']['lajur_2']['jenis'] ?? null,
                        'nilai_ki_lajur3' => $d['badan_jalan']['lapis_permukaan']['kiri']['lajur_3']['jenis'] ?? null,
                        'nilai_ki_lajur4' => $d['badan_jalan']['lapis_permukaan']['kiri']['lajur_4']['jenis'] ?? null,
                        'nilai_ka_lajur1' => $d['badan_jalan']['lapis_permukaan']['kanan']['lajur_1']['jenis'] ?? null,
                        'nilai_ka_lajur2' => $d['badan_jalan']['lapis_permukaan']['kanan']['lajur_2']['jenis'] ?? null,
                        'nilai_ka_lajur3' => $d['badan_jalan']['lapis_permukaan']['kanan']['lajur_3']['jenis'] ?? null,
                        'nilai_ka_lajur4' => $d['badan_jalan']['lapis_permukaan']['kanan']['lajur_4']['jenis'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'jenis_lapis_id' => 2,
                        'uraian' => 'TEBAL',
                        'nilai_ki_lajur1' => $d['badan_jalan']['lapis_pondasi_atas']['kiri']['lajur_1']['tebal'] ?? null,
                        'nilai_ki_lajur2' => $d['badan_jalan']['lapis_pondasi_atas']['kiri']['lajur_2']['tebal'] ?? null,
                        'nilai_ki_lajur3' => $d['badan_jalan']['lapis_pondasi_atas']['kiri']['lajur_3']['tebal'] ?? null,
                        'nilai_ki_lajur4' => $d['badan_jalan']['lapis_pondasi_atas']['kiri']['lajur_4']['tebal'] ?? null,
                        'nilai_ka_lajur1' => $d['badan_jalan']['lapis_pondasi_atas']['kanan']['lajur_1']['tebal'] ?? null,
                        'nilai_ka_lajur2' => $d['badan_jalan']['lapis_pondasi_atas']['kanan']['lajur_2']['tebal'] ?? null,
                        'nilai_ka_lajur3' => $d['badan_jalan']['lapis_pondasi_atas']['kanan']['lajur_3']['tebal'] ?? null,
                        'nilai_ka_lajur4' => $d['badan_jalan']['lapis_pondasi_atas']['kanan']['lajur_4']['tebal'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'jenis_lapis_id' => 2,
                        'uraian' => 'JENIS',
                        'nilai_ki_lajur1' => $d['badan_jalan']['lapis_pondasi_atas']['kiri']['lajur_1']['jenis'] ?? null,
                        'nilai_ki_lajur2' => $d['badan_jalan']['lapis_pondasi_atas']['kiri']['lajur_2']['jenis'] ?? null,
                        'nilai_ki_lajur3' => $d['badan_jalan']['lapis_pondasi_atas']['kiri']['lajur_3']['jenis'] ?? null,
                        'nilai_ki_lajur4' => $d['badan_jalan']['lapis_pondasi_atas']['kiri']['lajur_4']['jenis'] ?? null,
                        'nilai_ka_lajur1' => $d['badan_jalan']['lapis_pondasi_atas']['kanan']['lajur_1']['jenis'] ?? null,
                        'nilai_ka_lajur2' => $d['badan_jalan']['lapis_pondasi_atas']['kanan']['lajur_2']['jenis'] ?? null,
                        'nilai_ka_lajur3' => $d['badan_jalan']['lapis_pondasi_atas']['kanan']['lajur_3']['jenis'] ?? null,
                        'nilai_ka_lajur4' => $d['badan_jalan']['lapis_pondasi_atas']['kanan']['lajur_4']['jenis'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'jenis_lapis_id' => 3,
                        'uraian' => 'TEBAL',
                        'nilai_ki_lajur1' => $d['badan_jalan']['lapis_pondasi_bawah']['kiri']['lajur_1']['tebal'] ?? null,
                        'nilai_ki_lajur2' => $d['badan_jalan']['lapis_pondasi_bawah']['kiri']['lajur_2']['tebal'] ?? null,
                        'nilai_ki_lajur3' => $d['badan_jalan']['lapis_pondasi_bawah']['kiri']['lajur_3']['tebal'] ?? null,
                        'nilai_ki_lajur4' => $d['badan_jalan']['lapis_pondasi_bawah']['kiri']['lajur_4']['tebal'] ?? null,
                        'nilai_ka_lajur1' => $d['badan_jalan']['lapis_pondasi_bawah']['kanan']['lajur_1']['tebal'] ?? null,
                        'nilai_ka_lajur2' => $d['badan_jalan']['lapis_pondasi_bawah']['kanan']['lajur_2']['tebal'] ?? null,
                        'nilai_ka_lajur3' => $d['badan_jalan']['lapis_pondasi_bawah']['kanan']['lajur_3']['tebal'] ?? null,
                        'nilai_ka_lajur4' => $d['badan_jalan']['lapis_pondasi_bawah']['kanan']['lajur_4']['tebal'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'jenis_lapis_id' => 3,
                        'uraian' => 'JENIS',
                        'nilai_ki_lajur1' => $d['badan_jalan']['lapis_pondasi_bawah']['kiri']['lajur_1']['jenis'] ?? null,
                        'nilai_ki_lajur2' => $d['badan_jalan']['lapis_pondasi_bawah']['kiri']['lajur_2']['jenis'] ?? null,
                        'nilai_ki_lajur3' => $d['badan_jalan']['lapis_pondasi_bawah']['kiri']['lajur_3']['jenis'] ?? null,
                        'nilai_ki_lajur4' => $d['badan_jalan']['lapis_pondasi_bawah']['kiri']['lajur_4']['jenis'] ?? null,
                        'nilai_ka_lajur1' => $d['badan_jalan']['lapis_pondasi_bawah']['kanan']['lajur_1']['jenis'] ?? null,
                        'nilai_ka_lajur2' => $d['badan_jalan']['lapis_pondasi_bawah']['kanan']['lajur_2']['jenis'] ?? null,
                        'nilai_ka_lajur3' => $d['badan_jalan']['lapis_pondasi_bawah']['kanan']['lajur_3']['jenis'] ?? null,
                        'nilai_ka_lajur4' => $d['badan_jalan']['lapis_pondasi_bawah']['kanan']['lajur_4']['jenis'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
            dataJalanTeknik2Lapis::insert($data_jalan_teknik2_lapis);

            $data_jalan_teknik2_median =
                [
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'LEBAR' ?? null,
                        'nilai' => $d['median']['lebar'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
            dataJalanTeknik2Median::insert($data_jalan_teknik2_median);

            $data_jalan_teknik2_bahujalan =
                [
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'jenis_lapis_id' => 1 ?? null,
                        'uraian' => 'TEBAL' ?? null,
                        'nilai_ki_dalam' => $d['bahu_jalan']['lapis_permukaan']['kiri_dalam']['tebal'] ?? null,
                        'nilai_ki_luar' => $d['bahu_jalan']['lapis_permukaan']['kiri_luar']['tebal'] ?? null,
                        'nilai_ka_dalam' => $d['bahu_jalan']['lapis_permukaan']['kanan_dalam']['tebal'] ?? null,
                        'nilai_ka_luar' => $d['bahu_jalan']['lapis_permukaan']['kanan_luar']['tebal'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'jenis_lapis_id' => 1 ?? null,
                        'uraian' => 'JENIS' ?? null,
                        'nilai_ki_dalam' => $d['bahu_jalan']['lapis_permukaan']['kiri_dalam']['jenis'] ?? null,
                        'nilai_ki_luar' => $d['bahu_jalan']['lapis_permukaan']['kiri_luar']['jenis'] ?? null,
                        'nilai_ka_dalam' => $d['bahu_jalan']['lapis_permukaan']['kanan_dalam']['jenis'] ?? null,
                        'nilai_ka_luar' => $d['bahu_jalan']['lapis_permukaan']['kanan_luar']['jenis'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'jenis_lapis_id' => 2 ?? null,
                        'uraian' => 'TEBAL' ?? null,
                        'nilai_ki_dalam' => $d['bahu_jalan']['lapis_pondasi_atas']['kiri_dalam']['tebal'] ?? null,
                        'nilai_ki_luar' => $d['bahu_jalan']['lapis_pondasi_atas']['kiri_luar']['tebal'] ?? null,
                        'nilai_ka_dalam' => $d['bahu_jalan']['lapis_pondasi_atas']['kanan_dalam']['tebal'] ?? null,
                        'nilai_ka_luar' => $d['bahu_jalan']['lapis_pondasi_atas']['kanan_luar']['tebal'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'jenis_lapis_id' => 2 ?? null,
                        'uraian' => 'JENIS' ?? null,
                        'nilai_ki_dalam' => $d['bahu_jalan']['lapis_pondasi_atas']['kiri_dalam']['jenis'] ?? null,
                        'nilai_ki_luar' => $d['bahu_jalan']['lapis_pondasi_atas']['kiri_luar']['jenis'] ?? null,
                        'nilai_ka_dalam' => $d['bahu_jalan']['lapis_pondasi_atas']['kanan_dalam']['jenis'] ?? null,
                        'nilai_ka_luar' => $d['bahu_jalan']['lapis_pondasi_atas']['kanan_luar']['jenis'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'jenis_lapis_id' => 3 ?? null,
                        'uraian' => 'TEBAL' ?? null,
                        'nilai_ki_dalam' => $d['bahu_jalan']['lapis_pondasi_bawah']['kiri_dalam']['tebal'] ?? null,
                        'nilai_ki_luar' => $d['bahu_jalan']['lapis_pondasi_bawah']['kiri_luar']['tebal'] ?? null,
                        'nilai_ka_dalam' => $d['bahu_jalan']['lapis_pondasi_bawah']['kanan_dalam']['tebal'] ?? null,
                        'nilai_ka_luar' => $d['bahu_jalan']['lapis_pondasi_bawah']['kanan_luar']['tebal'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'jenis_lapis_id' => 3 ?? null,
                        'uraian' => 'JENIS' ?? null,
                        'nilai_ki_dalam' => $d['bahu_jalan']['lapis_pondasi_bawah']['kiri_dalam']['jenis'] ?? null,
                        'nilai_ki_luar' => $d['bahu_jalan']['lapis_pondasi_bawah']['kiri_luar']['jenis'] ?? null,
                        'nilai_ka_dalam' => $d['bahu_jalan']['lapis_pondasi_bawah']['kanan_dalam']['jenis'] ?? null,
                        'nilai_ka_luar' => $d['bahu_jalan']['lapis_pondasi_bawah']['kanan_luar']['jenis'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
            DataJalanTeknik2BahuJalan::insert($data_jalan_teknik2_bahujalan);

            $data_jalan_teknik3_goronggorong =
                [
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'JENIS MATERIAL' ?? null,
                        'nilai_ke1' => $d['gorong_gorong'][0]['jenis_material'] ?? null,
                        'nilai_ke2' => $d['gorong_gorong'][1]['jenis_material'] ?? null,
                        'nilai_ke3' => $d['gorong_gorong'][2]['jenis_material'] ?? null,
                        'nilai_ke4' => $d['gorong_gorong'][3]['jenis_material'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'UKURAN PANJANG' ?? null,
                        'nilai_ke1' => $d['gorong_gorong'][0]['ukuran_panjang'] ?? null,
                        'nilai_ke2' => $d['gorong_gorong'][1]['ukuran_panjang'] ?? null,
                        'nilai_ke3' => $d['gorong_gorong'][2]['ukuran_panjang'] ?? null,
                        'nilai_ke4' => $d['gorong_gorong'][3]['ukuran_panjang'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'KONDISI' ?? null,
                        'nilai_ke1' => $d['gorong_gorong'][0]['kondisi'] ?? null,
                        'nilai_ke2' => $d['gorong_gorong'][1]['kondisi'] ?? null,
                        'nilai_ke3' => $d['gorong_gorong'][2]['kondisi'] ?? null,
                        'nilai_ke4' => $d['gorong_gorong'][3]['kondisi'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
            DataJalanTeknik3GorongGorong::insert($data_jalan_teknik3_goronggorong);

            $data_jalan_teknik3_saluran =
                [
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'JENIS MATERIAL' ?? null,
                        'jenis_saluran_id' => 4 ?? null,
                        'nilai_ke1_ki' => $d['manhole']['kiri'][0]['jenis_material'] ?? null,
                        'nilai_ke1_md' => $d['manhole']['median'][0]['jenis_material'] ?? null,
                        'nilai_ke1_ka' => $d['manhole']['kanan'][0]['jenis_material'] ?? null,
                        'nilai_ke2_ki' => $d['manhole']['kiri'][1]['jenis_material'] ?? null,
                        'nilai_ke2_md' => $d['manhole']['median'][1]['jenis_material'] ?? null,
                        'nilai_ke2_ka' => $d['manhole']['kanan'][1]['jenis_material'] ?? null,
                        'nilai_ke3_ki' => $d['manhole']['kiri'][2]['jenis_material'] ?? null,
                        'nilai_ke3_md' => $d['manhole']['median'][2]['jenis_material'] ?? null,
                        'nilai_ke3_ka' => $d['manhole']['kanan'][2]['jenis_material'] ?? null,
                        'nilai_ke4_ki' => $d['manhole']['kiri'][3]['jenis_material'] ?? null,
                        'nilai_ke4_md' => $d['manhole']['median'][3]['jenis_material'] ?? null,
                        'nilai_ke4_ka' => $d['manhole']['kanan'][3]['jenis_material'] ?? null,
                        'created_at' => now() ?? null,
                        'updated_at' => now() ?? null,
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'UKURAN POKOK' ?? null,
                        'jenis_saluran_id' => 4 ?? null,
                        'nilai_ke1_ki' => $d['manhole']['kiri'][0]['ukuran_pokok'] ?? null,
                        'nilai_ke1_md' => $d['manhole']['median'][0]['ukuran_pokok'] ?? null,
                        'nilai_ke1_ka' => $d['manhole']['kanan'][0]['ukuran_pokok'] ?? null,
                        'nilai_ke2_ki' => $d['manhole']['kiri'][1]['ukuran_pokok'] ?? null,
                        'nilai_ke2_md' => $d['manhole']['median'][1]['ukuran_pokok'] ?? null,
                        'nilai_ke2_ka' => $d['manhole']['kanan'][1]['ukuran_pokok'] ?? null,
                        'nilai_ke3_ki' => $d['manhole']['kiri'][2]['ukuran_pokok'] ?? null,
                        'nilai_ke3_md' => $d['manhole']['median'][2]['ukuran_pokok'] ?? null,
                        'nilai_ke3_ka' => $d['manhole']['kanan'][2]['ukuran_pokok'] ?? null,
                        'nilai_ke4_ki' => $d['manhole']['kiri'][3]['ukuran_pokok'] ?? null,
                        'nilai_ke4_md' => $d['manhole']['median'][3]['ukuran_pokok'] ?? null,
                        'nilai_ke4_ka' => $d['manhole']['kanan'][3]['ukuran_pokok'] ?? null,
                        'created_at' => now() ?? null,
                        'updated_at' => now() ?? null,
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'KONDISI' ?? null,
                        'jenis_saluran_id' => 4 ?? null,
                        'nilai_ke1_ki' => $d['manhole']['kiri'][0]['kondisi'] ?? null,
                        'nilai_ke1_md' => $d['manhole']['median'][0]['kondisi'] ?? null,
                        'nilai_ke1_ka' => $d['manhole']['kanan'][0]['kondisi'] ?? null,
                        'nilai_ke2_ki' => $d['manhole']['kiri'][1]['kondisi'] ?? null,
                        'nilai_ke2_md' => $d['manhole']['median'][1]['kondisi'] ?? null,
                        'nilai_ke2_ka' => $d['manhole']['kanan'][1]['kondisi'] ?? null,
                        'nilai_ke3_ki' => $d['manhole']['kiri'][2]['kondisi'] ?? null,
                        'nilai_ke3_md' => $d['manhole']['median'][2]['kondisi'] ?? null,
                        'nilai_ke3_ka' => $d['manhole']['kanan'][2]['kondisi'] ?? null,
                        'nilai_ke4_ki' => $d['manhole']['kiri'][3]['kondisi'] ?? null,
                        'nilai_ke4_md' => $d['manhole']['median'][3]['kondisi'] ?? null,
                        'nilai_ke4_ka' => $d['manhole']['kanan'][3]['kondisi'] ?? null,
                        'created_at' => now() ?? null,
                        'updated_at' => now() ?? null,
                    ],
                ];
            DataJalanTeknik3Saluran::insert($data_jalan_teknik3_saluran);

            // $data_jalan_teknik3_bangunan =
            // [
            //     [
            //         [
            //             'id_leger' => $l['id'],
            //             'uraian' => 'jenis material',
            //             'nilai_ke1_ki' => $data['gorong_gorong'][0]['jenis_material'],
            //             'nilai_ke2_ki' => $data['gorong_gorong'][1]['jenis_material'],
            //             'nilai_ke3_ki' => $data['gorong_gorong'][2]['jenis_material'],
            //             'nilai_ke4_ki' => $data['gorong_gorong'][3]['jenis_material'],
            //         ],
            //     ],
            // ];

            $data_jalan_teknik4 =
                [
                    //PAGAR OPERASIONAL
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'PAGAR OPERASIONAL' ?? null,
                        'nilai_ki' => $d['patok_km_count']['kiri']['count'] ?? null,
                        'nilai_md' => $d['patok_km_count']['median']['count'] ?? null,
                        'nilai_ka' => $d['patok_km_count']['kanan']['count'] ?? null,
                        'created_at' => now() ?? null,
                        'updated_at' => now() ?? null,
                    ],

                    //PATOK KM
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'PATOK KM' ?? null,
                        'nilai_ki' => $d['patok_km_count']['kiri'] ?? null,
                        'nilai_md' => $d['patok_km_count']['median'] ?? null,
                        'nilai_ka' => $d['patok_km_count']['kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    //PATOK HM
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'PATOK HM' ?? null,
                        'nilai_ki' => $d['patok_hm_count']['kiri'] ?? null,
                        'nilai_md' => $d['patok_hm_count']['median'] ?? null,
                        'nilai_ka' => $d['patok_hm_count']['kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    //PATOK LJ
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'PATOK LJ' ?? null,
                        'nilai_ki' => $d['patok_lj_count']['kiri'] ?? null,
                        'nilai_md' => $d['patok_lj_count']['median'] ?? null,
                        'nilai_ka' => $d['patok_lj_count']['kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    //PATOK RMJ
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'PATOK RMJ' ?? null,
                        'nilai_ki' => $d['patok_rmj_count']['kiri'] ?? null,
                        'nilai_md' => $d['patok_rmj_count']['median'] ?? null,
                        'nilai_ka' => $d['patok_rmj_count']['kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
            dataJalanTeknik4::insert($data_jalan_teknik4);


            $data_jalan_teknik5_utilitas =
                [
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'JARINGAN LISTRIK DIBAWAH TANAH' ?? null,
                        'nilai_ki' => $d['listrik_bawah_tanah']['kiri']['count'] ?? null,
                        'nilai_md' => $d['listrik_bawah_tanah']['median']['count'] ?? null,
                        'nilai_ka' => $d['listrik_bawah_tanah']['kanan']['count'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'JARINGAN TELEKOMUNIKASI DIBAWAH TANAH' ?? null,
                        'nilai_ki' => $d['telepon_bawah_tanah']['kiri']['count'] ?? null,
                        'nilai_md' => $d['telepon_bawah_tanah']['median']['count'] ?? null,
                        'nilai_ka' => $d['telepon_bawah_tanah']['kanan']['count'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
            DataJalanTeknik5Utilitas::insert($data_jalan_teknik5_utilitas);

            $data_jalan_lhr =
                [
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'GOLONGAN I' ?? null,
                        'lhr_ki' => $d['lhr_sum']['golongan_i']['kiri'] ?? null,
                        'lhr_ka' => $d['lhr_sum']['golongan_i']['kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'GOLONGAN II' ?? null,
                        'lhr_ki' => $d['lhr_sum']['golongan_ii']['kiri'] ?? null,
                        'lhr_ka' => $d['lhr_sum']['golongan_ii']['kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'GOLONGAN III' ?? null,
                        'lhr_ki' => $d['lhr_sum']['golongan_iii']['kiri'] ?? null,
                        'lhr_ka' => $d['lhr_sum']['golongan_iii']['kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'GOLONGAN IV' ?? null,
                        'lhr_ki' => $d['lhr_sum']['golongan_iv']['kiri'] ?? null,
                        'lhr_ka' => $d['lhr_sum']['golongan_iv']['kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'GOLONGAN V' ?? null,
                        'lhr_ki' => $d['lhr_sum']['golongan_v']['kiri'] ?? null,
                        'lhr_ka' => $d['lhr_sum']['golongan_v']['kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
            DataJalanLHR::insert($data_jalan_lhr);

            $data_geometrik_jalan =
                [
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'LEBAR RUMIJA' ?? null,
                        'nilai' => $d['data_geometrik_jalan']['lebar_rmj'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'KELANDAIAN KIRI' ?? null,
                        'nilai' => $d['data_geometrik_jalan']['gradien_kiri'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'KELANDAIAN KANAN' ?? null,
                        'nilai' => $d['data_geometrik_jalan']['gradien_kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'CROSSFALL KIRI' ?? null,
                        'nilai' => $d['data_geometrik_jalan']['crossfall_kiri'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'CROSSFALL KANAN' ?? null,
                        'nilai' => $d['data_geometrik_jalan']['crossfall_kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'SUPERELEVASI' ?? null,
                        'nilai' => $d['data_geometrik_jalan']['super_elevasi'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'RADIUS' ?? null,
                        'nilai' => $d['data_geometrik_jalan']['radius'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
            DataJalanGeometrik::insert($data_geometrik_jalan);

            $data_situasi_jalan =
                [
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'TERRAIN KIRI' ?? null,
                        'nilai' => $d['data_geometrik_jalan']['terrain_kiri'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'TERRAIN KANAN' ?? null,
                        'nilai' => $d['data_geometrik_jalan']['terrain_kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'TATAGUNA LAHAN KIRI' ?? null,
                        'nilai' => $d['data_geometrik_jalan']['tataguna_lahan_kiri'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'id_leger_jalan' => $l['id'] ?? null,
                        'tahun' => $tol['tahun'] ?? null,
                        'uraian' => 'TATAGUNA LAHAN KANAN' ?? null,
                        'nilai' => $d['data_geometrik_jalan']['tataguna_lahan_kanan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
            DataJalanSituasi::insert($data_situasi_jalan);
        };

        return 'finished';
    }
}
