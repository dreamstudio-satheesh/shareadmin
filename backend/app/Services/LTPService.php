<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class LTPService
{
    public function get(string $exchange, string $symbol, int $token, string $fullSymbol, $api, int $rowIndex): array
    {
        $ltp = null;
        $source = null;

        if (isMarketOpen()) {
            $tick = Redis::get("tick:$token");
            $tickData = $tick ? json_decode($tick, true) : null;

            if ($tickData && isset($tickData['ltp'])) {
                $ltp = floatval($tickData['ltp']);
                $source = 'redis';
            }
        }

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
