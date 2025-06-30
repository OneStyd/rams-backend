<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LegerPatokCPController extends Controller
{
    public function index()
    {
        $patokCP = DB::table('leger_patok')
            ->where('jenis_patok', 'CP')
            ->orderBy('km', 'asc')
            ->get();

        return view('legerpatokcp.index', compact('patokCP'));
    }

    public function show($id)
    {
        $patok = DB::table('leger_patok')
            ->where('id', $id)
            ->where('jenis_patok', 'CP')
            ->first();

        if (!$patok) {
            abort(404, 'Data patok tidak ditemukan.');
        }

        $patokBerikutnya = DB::table('leger_patok')
            ->where('jenis_patok', 'CP')
            ->where('id', '>', $id)
            ->orderBy('id')
            ->first();

        $data = [];

        foreach ([$patok, $patokBerikutnya] as $item) {
            if ($item) {
                $administratif = DB::table('spatial_administratif')
                    ->select('nama_prov', 'nama_kab', 'nama_kec', 'nama_desa')
                    ->where('kode_desa', $item->kode_desa)
                    ->first();

                $data[] = [
                    'id' => $item->id,
                    'nama' => $item->nama_patok,
                    'nama_ruas' => $item->nama_ruas,
                    'nama_seksi' => $item->nama_seksi,
                    'km' => $item->km,
                    'foto' => $item->foto ?? null,
                    'lintang' => $item->lintang,
                    'bujur' => $item->bujur,
                    'tinggi_ellipsoid' => $item->tinggi_ellipsoid,
                    'utm_n' => $item->utm_n,
                    'utm_e' => $item->utm_e,
                    'zona' => $item->zona,
                    'model_geoid' => $item->model_geoid,
                    'tinggi_orto' => $item->tinggi_orthometrik,
                    'administratif' => [
                        'nama_prov' => $administratif->nama_prov ?? '---',
                        'nama_kab' => $administratif->nama_kab ?? '---',
                        'nama_kec' => $administratif->nama_kec ?? '---',
                        'nama_desa' => $administratif->nama_desa ?? '---',
                    ]
                ];
            }
        }

        return view('legerpatokcp.blade', compact('data'));
    }
}
