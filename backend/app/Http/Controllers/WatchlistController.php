<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Models\Instrument;

class WatchlistController extends Controller
{
    protected $redisKey = 'watchlist:symbols';

    public function index()
    {
        $symbols = Redis::smembers($this->redisKey);
        return view('watchlist.index', compact('symbols'));
    }

    public function add(Request $request)
    {
        $symbol = strtoupper($request->input('symbol'));
        $exchange = strtoupper($request->input('exchange', 'NSE'));
        $entry = "$exchange:$symbol";

        // ✅ Check against instruments table
        $exists = Instrument::where('tradingsymbol', $symbol)
            ->where('exchange', $exchange)
            ->exists();

        if (! $exists) {
            return redirect('/watchlist')->with('error', "$symbol not found in instruments table.");
        }

        Redis::sadd($this->redisKey, $entry);
        return redirect('/watchlist')->with('success', "$entry added.");
    }

    public function remove(Request $request)
    {
        $entry = $request->input('symbol');
        Redis::srem($this->redisKey, $entry);

        return redirect('/watchlist')->with('success', "$entry removed.");
    }

    public function clear()
    {
        Redis::del($this->redisKey);
        return redirect('/watchlist')->with('success', 'Watchlist cleared.');
    }
}
