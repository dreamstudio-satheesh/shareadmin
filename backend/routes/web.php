<?php

use Carbon\Carbon;
use App\Models\ZerodhaAccount;
use App\Services\ZerodhaApiService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InstrumentsController;
use App\Http\Controllers\ZerodhaAuthController;
use App\Http\Controllers\AdminSettingsController;
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

    Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');

    Route::get('/watchlist', [WatchlistController::class, 'index']);
    Route::post('/watchlist/add', [WatchlistController::class, 'add']);
    Route::post('/watchlist/remove', [WatchlistController::class, 'remove']);
    Route::post('/watchlist/clear', [WatchlistController::class, 'clear']);

    Route::get('/orders/upload', [OrderController::class, 'showUploadForm'])->name('orders.upload');
    Route::post('/orders/import', [OrderController::class, 'import'])->name('orders.import');
    Route::get('/orders/sample', [OrderController::class, 'downloadSample'])->name('orders.download.sample');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::delete('/orders/bulk-delete', [OrderController::class, 'bulkDelete'])->name('orders.bulk-delete');
    // Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    // Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    // Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    // order logs & cron logs
    Route::get('/order-logs', [AdminSettingsController::class, 'logs'])->name('orders.logs');
    Route::get('/cron-logs', [AdminSettingsController::class, 'cronLogs'])->name('cron.logs');

    Route::get('/instruments', [InstrumentsController::class, 'index'])->name('instruments.index');
    Route::post('/instruments/import', [InstrumentsController::class, 'import'])->name('instruments.import');
});


Route::get('/redis-test', function () {
    return \Illuminate\Support\Facades\Redis::keys('tick:*');
});





Route::get('/ticks', function () {
    $keys = Redis::keys('tick:*');
    $ticks = [];

    foreach ($keys as $key) {
        $data = Redis::hgetall($key);
        if (!empty($data)) {
            $token = str_replace('tick:', '', $key);
            $symbol = $data['symbol'] ?? null;

            $ticks[] = [
                'token' => $token,
                'symbol' => $symbol,
                'lp' => $data['lp'] ?? '--',
                'ts' => $data['ts'] ?? null,
                'market_open' => $data['market_open'] ?? null,
                'time' => $data['ts']
                    ? Carbon::createFromTimestamp($data['ts'])->diffForHumans()
                    : null,
            ];
        }
    }

    return response()->json($ticks);
});


Route::view('/live-ticks', 'live-ticks');




Route::get('/broadcast-test', function () {
    broadcast(new \App\Events\TickUpdate([
        'symbol' => 'NSE:INFY',
        'lp' => rand(1000, 1500),
        'ts' => now()->timestamp,
    ]))->toOthers();

    return '📢 TickUpdate event broadcasted!';
});

