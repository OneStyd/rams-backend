<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\InputController;
use App\Http\Controllers\OutputController;
use App\Http\Controllers\LegerController;
use App\Http\Controllers\RefferenceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['middleware' => 'auth:sanctum'], function () {
    // Manage Users
    Route::prefix('/users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/create', [UserController::class, 'store']);
        Route::patch('/update/{id}', [UserController::class, 'update']);
        Route::delete('/delete/{id}', [UserController::class, 'destroy']);
        Route::get('/{id}', [UserController::class, 'show']);
    });

    // Auth
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);

    // Input Aset
    Route::get('data/list-ruas', [RefferenceController::class, 'getRuasList']);
    Route::get('data/tipe-aset', [RefferenceController::class, 'getTipeAsetList']);
    Route::post('data/input-ruas', [InputController::class, 'inputRuas']);
    Route::post('data/input-aset', [InputController::class, 'inputAset']);

    // Leger
    Route::post('leger/generate', [LegerController::class, 'generate']);
    Route::get('leger/get-data/{kode_leger}', [LegerController::class, 'getData']);
    Route::get('leger/jalan-utama/{jalan_tol_id}/{leger_id_awal}/{leger_id_akhir}', [LegerController::class, 'getDataJalanUtama']); 
    Route::get('leger/jalan-utama-all/{jalan_tol_id}', [LegerController::class, 'getAllDataJalanUtama']);
    Route::get('leger/ruas', [LegerController::class, 'getRuas']);
    Route::get('leger/segmen/{jalan_tol_id}', [LegerController::class, 'getSegmen']);
    Route::get('leger/populate/{jalan_tol_id}', [LegerController::class,'populateLegerJalan']);
    Route::get('leger/jalan-utama-map', [LegerController::class,'getLegerImage']);
    Route::get('leger/jalan-utama-all-test/{jalan_tol_id}', [LegerController::class, 'getDataJalanUtamaAll']);
    
    // Leger Jembatan
    Route::post('leger/jembatan/generate', [LegerJembatanController::class, 'generate']);
    Route::get('leger/jembatan/ruas', [LegerJembatanController::class, 'getRuas']);
    Route::get('leger/jembatan/segmen/{jalan_tol_id}', [LegerJembatanController::class, 'getSegmen']);
    Route::get('leger/jembatan/get-data/{kode_leger}', [LegerJembatanController::class, 'getDataLegerJembatan']);
    Route::get('leger/jembatan/{jalan_tol_id}/{leger_id_awal}/{leger_id_akhir}', [LegerJembatanController::class, 'getDataJembatan']);
    Route::get('leger/jembatan-all/{jalan_tol_id}', [LegerJembatanController::class, 'getAllDataJembatan']);
    Route::get('leger/jembatan/populate/{jalan_tol_id}', [LegerJembatanController::class,'populateLegerJembatan']);
    Route::get('leger/jembatan-all-test/{jalan_tol_id}', [LegerJembatanController::class, 'getDataJembatanAll']);
});

Route::get('/get-km-options', [FilterController::class, 'getKMOptions']);
Route::post('/filter_assets', [FilterController::class, 'filterAssets']);


// Output Aset
Route::get('data/aset/{type}', [OutputController::class, 'getAset']);
Route::get('data/segmen/{awal}/{akhir}', [OutputController::class, 'getSegmenLegerPolygonSelection']);

Route::post('/login', [AuthController::class, 'login']);
