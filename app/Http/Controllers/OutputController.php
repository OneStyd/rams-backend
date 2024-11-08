<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\Http\Resources\GeoJSONResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\JalanTol;
use App\Models\Spatial\{
    AdministratifPolygon,
    BatasDesaLine,
    BoxCulvertLine,
    BPTLine,
    BronjongLine,
    ConcreteBarrierLine,
    DataGeometrikJalanPolygon,
    GerbangLine,
    GerbangPoint,
    GorongGorongLine,
    GuardRailLine,
    IRIPolygon,
    JalanLine,
    JembatanPoint,
    JembatanPolygon,
    LampuLalulintasPoint,
    LapisPermukaanPolygon,
    LapisPondasiAtas1Polygon,
    LapisPondasiAtas2Polygon,
    LapisPondasiBawahPolygon,
    LHRPolygon,
    ListrikBawahtanahLine,
    ManholePoint,
    MarkaLine,
    PagarOperasionalLine,
    PatokHMPoint,
    PatokKMPoint,
    PatokLJPoint,
    PatokPemanduPoint,
    PatokRMJPoint,
    PatokROWPoint,
    PitaKejutLine,
    RambuLalulintasPoint,
    RambuPenunjukarahPoint,
    ReflektorPoint,
    RiolLine,
    RumahKabelPoint,
    RuwasjaPolygon,
    SaluranLine,
    SegmenKonstruksiPolygon,
    SegmenLegerPolygon,
    SegmenPerlengkapanPolygon,
    SegmenSeksiPolygon,
    SegmenTolPolygon,
    StaTextPoint,
    SungaiLine,
    TeleponBawahtanahLine,
    TiangListrikPoint,
    TiangTeleponPoint,
    VMSPoint
};

class OutputController extends Controller
{
    public function getAset($type, Request $request)
    {
        $getFunctions = [
            'administratif_polygon' => 'getAdministratifPolygon',
            'batas_desa_line' => 'getBatasDesaLine',
            'box_culvert_line' => 'getBoxCulvertLine',
            'bpt_line' => 'getBPTLine',
            'bronjong_line' => 'getBronjongLine',
            'concrete_barrier_line' => 'getConcreteBarrierLine',
            'data_geometrik_jalan_polygon' => 'getDataGeometrikJalanPolygon',
            'gerbang_line' => 'getGerbangLine',
            'gerbang_point' => 'getGerbangPoint',
            'gorong_gorong_line' => 'getGorongGorongLine',
            'guardrail_line' => 'getGuardrailLine',
            'iri_polygon' => 'getIRIPolygon',
            'jalan_line' => 'getJalanLine',
            'jembatan_point' => 'getJembatanPoint',
            'jembatan_polygon' => 'getJembatanPolygon',
            'lampu_lalulintas_point' => 'getLampuLalulintasPoint',
            'lapis_permukaan_polygon' => 'getLapisPermukaanPolygon',
            'lapis_pondasi_atas1_polygon' => 'getLapisPondasiAtas1Polygon',
            'lapis_pondasi_atas2_polygon' => 'getLapisPondasiAtas2Polygon',
            'lapis_pondasi_bawah_polygon' => 'getLapisPondasiBawahPolygon',
            'lhr_polygon' => 'getLHRPolygon',
            'listrik_bawahtanah_line' => 'getListrikBawahtanahLine',
            'manhole_point' => 'getManholePoint',
            'marka_line' => 'getMarkaLine',
            'pagar_operasional_line' => 'getPagarOperasionalLine',
            'patok_hm_point' => 'getPatokHMPoint',
            'patok_km_point' => 'getPatokKMPoint',
            'patok_lj_point' => 'getPatokLJPoint',
            'patok_pemandu_point' => 'getPatokPemanduPoint',
            'patok_rmj_point' => 'getPatokRMJPoint',
            'patok_row_point' => 'getPatokROWPoint',
            'pita_kejut_line' => 'getPitaKejutLine',
            'rambu_lalulintas_point' => 'getRambuLalulintasPoint',
            'rambu_penunjukarah_point' => 'getRambuPenunjukarahPoint',
            'reflektor_point' => 'getReflektorPoint',
            'riol_line' => 'getRiolLine',
            'rumah_kabel_point' => 'getRumahKabelPoint',
            'ruwasja_polygon' => 'getRuwasjaPolygon',
            'saluran_line' => 'getSaluranLine',
            'segmen_konstruksi_polygon' => 'getSegmenKonstruksiPolygon',
            'segmen_leger_polygon' => 'getSegmenLegerPolygon',
            'segmen_perlengkapan_polygon' => 'getSegmenPerlengkapanPolygon',
            'segmen_seksi_polygon' => 'getSegmenSeksiPolygon',
            'segmen_tol_polygon' => 'getSegmenTolPolygon',
            'sta_text_point' => 'getStaTextPoint',
            'sungai_line' => 'getSungaiLine',
            'telepon_bawahtanah_line' => 'getTeleponBawahtanahLine',
            'tiang_listrik_point' => 'getTiangListrikPoint',
            'tiang_telepon_point' => 'getTiangTeleponPoint',
            'vms_point' => 'getVMSPoint',
        ];

        if (array_key_exists($type, $getFunctions)) {
            return $this->{$getFunctions[$type]}($request->query('start_km'), $request->query('end_km'));
        } else {
            abort(404, 'Tipe Aset tidak ditemukan');
        }
    }

    private function getPointFromKM($km)
    {
        $km = $km ? str_replace(['+', ' '], '', $km) : null;

        if ($km === null) {
            return null;
        }

        return DB::table('spatial_patok_km_point')
            ->select(DB::raw('ST_X(geom) as x, ST_Y(geom) as y'))
            ->whereRaw("CAST(REPLACE(REPLACE(km, '+', ''), ' ', '') AS INTEGER) = ?", [$km])
            ->first();
    }
    private function getBoundingBoxData($table, $startPoint, $endPoint)
    {
        if (!$startPoint || !$endPoint) {
            return response()->json([
                'error' => 'Start or end km point not found',
                'missing' => [
                    'start_km' => !$startPoint ? 'Missing start_km' : null,
                    'end_km' => !$endPoint ? 'Missing end_km' : null,
                ]
            ], 404);
        }

        $data = DB::select("
            WITH original_points AS (
                SELECT 
                    ST_SetSRID(ST_MakePoint(:x1, :y1), 4326) AS geom1,
                    ST_SetSRID(ST_MakePoint(:x2, :y2), 4326) AS geom2
            ),
            projected_points AS (
                SELECT 
                    ST_Project(geom1::geography, 1000, radians(270))::geometry AS point_kiri1,
                    ST_Project(geom1::geography, 1000, radians(90))::geometry AS point_kanan1,
                    ST_Project(geom2::geography, 1000, radians(270))::geometry AS point_kiri2,
                    ST_Project(geom2::geography, 1000, radians(90))::geometry AS point_kanan2
                FROM original_points
            ),
            bounding_box AS (
                SELECT 
                    ST_SetSRID(
                        ST_MakePolygon(
                            ST_MakeLine(
                                ARRAY[
                                    (SELECT point_kiri1 FROM projected_points),
                                    (SELECT point_kanan1 FROM projected_points),
                                    (SELECT point_kanan2 FROM projected_points),
                                    (SELECT point_kiri2 FROM projected_points),
                                    (SELECT point_kiri1 FROM projected_points)
                                ]
                            )
                        ), 4326
                    ) AS geom
            )
            SELECT
               tb.*, ST_AsGeoJSON(ST_Intersection(bb.geom, tb.geom::geometry)) AS geojson
            FROM
                bounding_box bb
            JOIN
                {$table} tb ON ST_Intersects(bb.geom, tb.geom::geometry);
        ", [
            'x1' => $startPoint->x,
            'y1' => $startPoint->y,
            'x2' => $endPoint->x,
            'y2' => $endPoint->y,
        ]);

        $features = array_map(function ($item) {
            $properties = [];
            foreach ($item as $key => $value) {
                if ($key !== 'geom' && $key !== 'geojson') {
                    $properties[$key] = $value;
                }
            }

            return [
                'type' => 'Feature',
                'geometry' => json_decode($item->geojson),
                'properties' => $properties,
            ];
        }, $data);

        return [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
    }

    public function getAdministratifPolygon($start_km = null, $end_km = null)
    {
        // $startPoint = DB::table('spatial_patok_km_point')
        //     ->select(DB::raw('ST_X(geom) as x, ST_Y(geom) as y'))
        //     ->where('km', 'LIKE', '%' . $start_km . '%')
        //     ->first();

        // $endPoint = DB::table('spatial_patok_km_point')
        //     ->select(DB::raw('ST_X(geom) as x, ST_Y(geom) as y'))
        //     ->where('km', 'LIKE', '%' . $end_km . '%')
        //     ->first();

        // Normalize km format by removing '+' and any spaces
        $start_km = $start_km ? str_replace(['+', ' '], '', $start_km) : null;
        $end_km = $end_km ? str_replace(['+', ' '], '', $end_km) : null;

        // Query start point and end point
        $startPoint = DB::table('spatial_patok_km_point')
            ->select(DB::raw('ST_X(geom) as x, ST_Y(geom) as y'))
            ->whereRaw("CAST(REPLACE(REPLACE(km, '+', ''), ' ', '') AS INTEGER) = ?", [$start_km])
            ->first();

        $endPoint = DB::table('spatial_patok_km_point')
            ->select(DB::raw('ST_X(geom) as x, ST_Y(geom) as y'))
            ->whereRaw("CAST(REPLACE(REPLACE(km, '+', ''), ' ', '') AS INTEGER) = ?", [$end_km])
            ->first();

        if ($start_km && $end_km !== null) {
            if (!$startPoint || !$endPoint) {
                return response()->json([
                    'error' => 'Start or end km point not found',
                    'missing' => [
                        'start_km' => !$startPoint ? $start_km : null,
                        'end_km' => !$endPoint ? $end_km : null,
                    ]
                ], 404);
            } else {
                $data = DB::select(
                    "
                    WITH original_points AS (
                        SELECT 
                            ST_SetSRID(ST_MakePoint(:x1, :y1), 4326) AS geom1,
                            ST_SetSRID(ST_MakePoint(:x2, :y2), 4326) AS geom2
                    ),
                    projected_points AS (
                        SELECT 
                            ST_Project(geom1::geography, 1000, radians(270))::geometry AS point_kiri1,
                            ST_Project(geom1::geography, 1000, radians(90))::geometry AS point_kanan1,
                            ST_Project(geom2::geography, 1000, radians(270))::geometry AS point_kiri2,
                            ST_Project(geom2::geography, 1000, radians(90))::geometry AS point_kanan2
                        FROM original_points
                    ),
                    bounding_box AS (
                        SELECT 
                            ST_SetSRID(
                                ST_MakePolygon(
                                    ST_MakeLine(
                                        ARRAY[
                                            (SELECT point_kiri1 FROM projected_points),
                                            (SELECT point_kanan1 FROM projected_points),
                                            (SELECT point_kanan2 FROM projected_points),
                                            (SELECT point_kiri2 FROM projected_points),
                                            (SELECT point_kiri1 FROM projected_points)
                                        ]
                                    )
                                ), 4326
                            ) AS geom
                    )

                    SELECT 
                        ap.*, ST_AsGeoJSON(ST_Intersection(bb.geom, ap.geom::geometry)) AS geojson
                    FROM 
                        bounding_box bb
                    JOIN 
                        spatial_administratif_polygon ap ON ST_Intersects(bb.geom, ap.geom::geometry);
                ",
                    [
                        'x1' => $startPoint->x,
                        'y1' => $startPoint->y,
                        'x2' => $endPoint->x,
                        'y2' => $endPoint->y,
                    ]
                );

                $features = array_map(function ($item) {
                    // Buat array properties dari semua kolom di tabel kecuali 'geom' dan 'geojson'
                    $properties = [];
                    foreach ($item as $key => $value) {
                        if ($key !== 'geom' && $key !== 'geojson'
                        ) {
                            $properties[$key] = $value;
                        }
                    }

                    return [
                        'type' => 'Feature',
                        'geometry' => json_decode($item->geojson),
                        'properties' => $properties,
                    ];
                }, $data);

                return [
                    "type" => "FeatureCollection",
                    "features" => $features,
                ];

                return response()->json($featureCollection);
            }
        }

        // Default return if no start or end km
        $data = AdministratifPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")
            ->get()
            ->makeHidden('geom');
        $features = GeoJSONResource::collection($data);
        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];

        return response()->json($featureCollection);
    }

    public function getBatasDesaLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_batas_desa_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = BatasDesaLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getBoxCulvertLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_box_culvert_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = BoxCulvertLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getBPTLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_bpt_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = BPTLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getBronjongLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_bronjong_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = BronjongLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getConcreteBarrierLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);
        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_concrete_barrier_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }
        $data = ConcreteBarrierLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getDataGeometrikJalanPolygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_data_geometrik_jalan_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }
        $data = DataGeometrikJalanPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getGerbangLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);
        
        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_gerbang_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = GerbangLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getGerbangPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_gerbang_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = GerbangPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getGorongGorongLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_gorong_gorong_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = GorongGorongLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getGuardrailLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_guardrail_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = GuardrailLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getIRIPolygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_iri_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = IRIPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getJalanLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_jalan_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = JalanLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getJembatanPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_jembatan_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = JembatanPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getJembatanPolygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_jembatan_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = JembatanPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getLampuLalulintasPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_lampu_lalulintas_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = LampuLalulintasPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getLapisPermukaanPolygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_lapis_permukaan_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = LapisPermukaanPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getLapisPondasiAtas1Polygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_lapis_pondasi_atas1_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = LapisPondasiAtas1Polygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getLapisPondasiAtas2Polygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_lapis_pondasi_atas2_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = LapisPondasiAtas2Polygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getLapisPondasiBawahPolygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_lapis_pondasi_bawah_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = LapisPondasiBawahPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getLHRPolygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_lhr_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = LHRPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getListrikBawahtanahLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_listrik_bawahtanah_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = ListrikBawahtanahLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getManholePoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_manhole_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = ManholePoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getMarkaLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_marka_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = MarkaLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getPagarOperasionalLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_pagar_operasional_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = PagarOperasionalLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getPatokHMPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_patok_hm_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = PatokHMPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getPatokKMPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);
        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_patok_km_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = PatokKMPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getPatokLJPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_patok_lj_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = PatokLJPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getPatokPemanduPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_patok_pemandu_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = PatokPemanduPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getPatokRMJPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_patok_rmj_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = PatokRMJPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getPatokROWPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_patok_row_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = PatokROWPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getPitaKejutLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_pita_kejut_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = PitaKejutLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getRambuLalulintasPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_rambu_lalulintas_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = RambuLalulintasPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getRambuPenunjukarahPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_rambu_penunjukarah_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = RambuPenunjukarahPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getReflektorPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_reflektor_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = ReflektorPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getRiolLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_riol_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = RiolLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getRumahKabelPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_rumah_kabel_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = RumahKabelPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getRuwasjaPolygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_ruwasja_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = RuwasjaPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getSaluranLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_saluran_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = SaluranLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getSegmenKonstruksiPolygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_segmen_konstruksi_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = SegmenKonstruksiPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getSegmenLegerPolygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_segmen_leger_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = SegmenLegerPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getSegmenPerlengkapanPolygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_segmen_perlengkapan_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = SegmenPerlengkapanPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getSegmenSeksiPolygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_segmen_seksi_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = SegmenSeksiPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getSegmenTolPolygon($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_segmen_tol_polygon', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = SegmenTolPolygon::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getStaTextPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_sta_text_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = StaTextPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getSungaiLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_sungai_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = SungaiLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getTeleponBawahtanahLine($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_telepon_bawahtanah_line', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = TeleponBawahtanahLine::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getTiangListrikPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_tiang_listrik_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = TiangListrikPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getTiangTeleponPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_tiang_telepon_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }

        $data = TiangTeleponPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }

    public function getVMSPoint($start_km = null, $end_km = null)
    {
        $startPoint = $this->getPointFromKM($start_km);
        $endPoint = $this->getPointFromKM($end_km);

        if ($start_km && $end_km) {
            $featureCollection = $this->getBoundingBoxData('spatial_vms_point', $startPoint, $endPoint);
            return response()->json($featureCollection);
        }
        
        $data = VMSPoint::selectRaw("*, ST_AsGeoJSON(ST_Transform(geom::geometry, 4326)) AS geojson")->get()->makeHidden('geom');
        $features = GeoJSONResource::collection($data);

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features,
        ];
        return response()->json($featureCollection);
    }
}
