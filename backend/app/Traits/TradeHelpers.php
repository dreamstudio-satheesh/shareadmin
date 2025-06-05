<?php

namespace App\Traits;

use App\Models\Instrument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

trait TradeHelpers
{
    protected function parseSymbol(string $raw): array
    {
        $raw = strtoupper(trim($raw));
        return str_contains($raw, ':') ? explode(':', $raw, 2) : ['NSE', $raw];
    }

    protected function formatSymbol(string $symbol): string
    {
        return str_contains($symbol, ':') ? strtoupper($symbol) : 'NSE:' . strtoupper($symbol);
    }

    protected function roundToTick(float $value, float $tick): float
    {
        return round(floor($value / $tick) * $tick, 2);
    }

    protected function snapToTick(float $value, float $tick, bool $roundUp = false): float
    {
        $factor = $value / $tick;
        $snapped = $roundUp
            ? ceil($factor) * $tick
            : floor($factor) * $tick;

        return round($snapped, 2);
    }

    protected function getTickSize(string $exchange, string $symbol): float
    {
        return Instrument::where('exchange', $exchange)
            ->where('tradingsymbol', $symbol)
            ->value('tick_size') ?? 0.05;
    }

    /**
     * Fetch LTP from Redis or fallback to API.
     */
    protected function fetchLTP(string $exchange, string $symbol, int $token, string $fullSymbol, $api, int $rowIndex): array
    {
        $ltp = null;
        $source = null;

        // ✅ Try Redis
        if (isMarketOpen()) {
            $tick = Redis::get("tick:$token");
            $tickData = $tick ? json_decode($tick, true) : null;

            if ($tickData && isset($tickData['ltp'])) {
                $ltp = floatval($tickData['ltp']);
                $source = 'redis';
            }
        }

        // ✅ Fallback to API
        if (!$ltp) {
            try {
                $response = $api->getLTP([$fullSymbol]);
                $apiLtp = $response['data'][$fullSymbol]['last_price'] ?? null;

                if ($apiLtp) {
                    $ltp = floatval($apiLtp);
                    $source = 'api';
                    Redis::setex("tick:$token", 300, json_encode(['ltp' => $ltp, 'time' => now()->timestamp]));
                } else {
                    Log::warning("Row $rowIndex: LTP not found from API for $fullSymbol.");
                }
            } catch (\Throwable $e) {
                Log::error("Row $rowIndex: API fallback failed for $fullSymbol. Error: " . $e->getMessage());
            }
        }

        return [$ltp, $source];
    }


}
