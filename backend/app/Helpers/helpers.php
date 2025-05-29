<?php

use App\Models\Instrument;
use App\Models\AdminSetting;
use Illuminate\Support\Carbon;

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
