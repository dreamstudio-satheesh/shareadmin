<?php

function getTokenFromSymbol(string $symbol): ?int {
    return \App\Models\Instrument::where('tradingsymbol', $symbol)->value('instrument_token');
}

function getStoplossPercent(): float {
    return \App\Models\AdminSetting::value('stoploss_percent') ?? 1.5;
}


function isMarketOpen(): bool
{
    $now = now()->timezone('Asia/Kolkata');
    $start = $now->copy()->setTime(9, 15);
    $end = $now->copy()->setTime(15, 30);

    return $now->isWeekday() && $now->between($start, $end);
}


function getMarketSession(): string
{
    $now = now()->timezone('Asia/Kolkata');

    if (!$now->isWeekday()) {
        return 'holiday'; // Saturday/Sunday
    }

    $time = $now->format('H:i');

    if ($time < '09:00') {
        return 'closed';
    } elseif ($time >= '09:00' && $time < '09:15') {
        return 'pre-market';
    } elseif ($time >= '09:15' && $time <= '15:30') {
        return 'market';
    } elseif ($time > '15:30' && $time <= '16:00') {
        return 'post-market';
    }

    return 'closed'; // After 4:00 PM
}

function getMarketOpenTime(): string
{
    return '09:15';
}
