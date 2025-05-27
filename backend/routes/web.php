<?php

use App\Events\TickUpdated;
use Illuminate\Http\Request;
use App\Models\WatchlistSymbol;
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


    Route::get('/test-broadcast', function () {
        $token = '779521';
        $data = [
            'ltp' => rand(90, 110),
            'time' => now()->toIso8601String(),
        ];

        broadcast(new TickUpdated($token, $data));
        return 'Tick broadcasted';
    });


    Route::get('/ticks', function () {
        return view('ticks');
    });


    
});



Route::get('/redis-test', function () {
    return \Illuminate\Support\Facades\Redis::keys('tick:*');
});





/* Route::post('/temp0', function () {
    // When a user adds a stock
    Redis::sadd('watchlist:symbols', 'NSE:RELIANCE');

    // When a user removes a stock
    Redis::srem('watchlist:symbols', 'NSE:RELIANCE');

    // Or, to sync the entire list from your database
    $activeSymbols = Watchlist::where('is_active', true)->pluck('trading_symbol')->toArray();
    Redis::del('watchlist:symbols'); // Delete the old set
    if (!empty($activeSymbols)) {
        Redis::sadd('watchlist:symbols', $activeSymbols); // Add all current symbols at once
    }
}); */


Route::get('/redis-test-raw', function () {
    try {
        $keys = Redis::keys('tick:*');
        $result = [];

        foreach ($keys as $key) {
            $value = Redis::get($key);
            $result[$key] = json_decode($value, true);
        }

        return response()->json([
            'success' => true,
            'count' => count($result),
            'data' => $result,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});
