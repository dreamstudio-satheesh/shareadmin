<?php

use App\Events\TickUpdated;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/redis-test', function () {
    return Redis::keys('tick:*');
});

    Route::get('/test-broadcast', function () {
        $token = '779521';
        $data = [
            'ltp' => rand(90, 110),
            'time' => now()->toIso8601String(),
        ];

       //  broadcast(new TickUpdated($token, $data));
       event(new TickUpdated($token, $data)); 
        return 'Tick broadcasted';
    });

 Route::get('/ticks', function () {
        return view('ticks');
    });