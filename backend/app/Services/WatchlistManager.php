<?php

namespace App\Services;

use App\Models\Watchlist;
use App\Models\Instrument;

class WatchlistManager
{
    public static function ensureExists(string $fullSymbol): void
    {
        if (Watchlist::where('symbol', $fullSymbol)->exists()) {
            return;
        }

        [$exchange, $symbol] = explode(':', $fullSymbol);

        $instrument = Instrument::where('exchange', $exchange)
            ->where('tradingsymbol', $symbol)
            ->first();

        if (! $instrument) {
            throw new \Exception("Instrument not found for $fullSymbol.");
        }

        Watchlist::create([
            'symbol' => $fullSymbol,
            'exchange' => $exchange,
            'instrument_token' => $instrument->instrument_token,
        ]);
    }
}
