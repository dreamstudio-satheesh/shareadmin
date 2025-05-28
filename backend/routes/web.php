<?php

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ZerodhaAuthController;
use App\Http\Controllers\ZerodhaAccountController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::post('zerodha_accounts/{zerodha_account}/update-token', [ZerodhaAccountController::class, 'updateToken'])->name('zerodha_accounts.update_token');

Route::middleware('admin.auth')->group(function () {
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');
    Route::resource('zerodha_accounts', ZerodhaAccountController::class);
    
    Route::post('zerodha_accounts/check-now', [ZerodhaAccountController::class, 'checkNow'])->name('zerodha_accounts.check_now');
    
    Route::prefix('zerodha')->group(function () {
        Route::get('/login/{id}', [ZerodhaAuthController::class, 'redirect'])->name('zerodha.login');
        Route::get('/callback', [ZerodhaAuthController::class, 'callback'])->name('zerodha.callback');
    });
});


Route::get('/redis-test', function () {
    return \Illuminate\Support\Facades\Redis::keys('tick:*');
});


Route::get('/ticks', function () {
    $keys = Redis::keys('tick:*');
    $ticks = [];

    foreach ($keys as $key) {
        $json = Redis::get($key);
        if ($json) {
            $data = json_decode($json, true);
            $ticks[] = $data;
        }
    }

    return response()->json($ticks);
});




Route::get('/ticksui', function () {
    return view('ticks');
});


Route::get('/broadcast-test', function () {
    return view('broadcast-test');
});