<?php

use App\Models\Instrument;
use App\Models\AdminSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Get token for a given symbol (must include exchange prefix if needed).
 */
function getTokenFromSymbol(string $symbol): ?int
{
    $symbol = strtoupper(trim($symbol));
    if (str_contains($symbol, ':')) {
        [$exchange, $name] = explode(':', $symbol, 2);
        return Instrument::where('exchange', $exchange)->where('tradingsymbol', $name)->value('instrument_token');
    }

    return Instrument::where('tradingsymbol', $symbol)->value('instrument_token');
}

/**
 * Get stoploss percent from settings (default: 1.5%).
 */
function getStoplossPercent(): float
{
    return AdminSetting::value('stoploss_percent') ?? 1.5;
}

/**
 * Check if market is currently open (Mon-Fri, 9:15 to 15:30 IST).
 */
function isMarketOpen(): bool
{
    $now = now()->timezone('Asia/Kolkata');
    $start = $now->copy()->setTime(9, 15);
    $end = $now->copy()->setTime(15, 30);

    return $now->isWeekday() && $now->between($start, $end);
}


/**
 * Check if today is a trading day (weekday and not in holiday list).
 */
function isTradingDay(?Carbon $date = null): bool
{
    $date = $date?->copy() ?? now()->timezone('Asia/Kolkata')->startOfDay();

    if (! $date->isWeekday()) {
        return false;
    }

    // Cache holiday list for 1 hour to reduce DB/API hits
    $holidays = Cache::remember('nse_holidays', 3600, function () {
        // Option A: Static list
        return [
            '2025-01-26',
            '2025-03-29',
            '2025-04-14',
            '2025-05-30',
            '2025-08-15',
            '2025-10-02',
            '2025-11-04',
            '2025-12-25',
        ];

        // Option B: Load from DB or API if you store holidays in a table or external JSON
        // return DB::table('holidays')->pluck('date')->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();
    });

    return !in_array($date->format('Y-m-d'), $holidays);
}

/**
 * Get current market session name.
 */
function getMarketSession(): string
{
    $now = now()->timezone('Asia/Kolkata');

    if (!$now->isWeekday()) {
        return 'holiday';
    }

    $time = $now->format('H:i');

    if ($time < '09:00') {
        return 'closed';
    } elseif ($time < '09:15') {
        return 'pre-market';
    } elseif ($time <= '15:30') {
        return 'market';
    } elseif ($time <= '16:00') {
        return 'post-market';
    }

    return 'closed';
}

/**
 * Returns the standard market open time.
 */
function getMarketOpenTime(): string
{
    return '09:15';
}
