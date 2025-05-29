<?php

namespace App\Imports;

use App\Models\PendingOrder;
use App\Models\Instrument;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Redis;

class PendingOrdersImport implements ToCollection
{
    protected $accountId;

    public function __construct($accountId)
    {
        $this->accountId = $accountId;
    }

    public function collection(Collection $rows)
    {
        $rows->shift(); // Skip header row

        foreach ($rows as $row) {
            $symbol = trim($row[0]);
            $targetPercent = floatval($row[1]);
            $qty = floatval($row[2]);
            $product = strtoupper(trim($row[3]));

            // Skip duplicate pending order
            if (PendingOrder::where('zerodha_account_id', $this->accountId)
                ->where('symbol', $symbol)
                ->where('status', 'pending')
                ->exists()) {
                continue;
            }

            // Get token
            $token = getTokenFromSymbol($symbol); // your helper function
            if (!$token) continue;

            $ltp = null;
            $source = null;

            if (isMarketOpen()) {
                // Try Redis tick
                $tick = Redis::get("tick:$token");
                $tickData = $tick ? json_decode($tick, true) : null;

                if ($tickData && isset($tickData['ltp'])) {
                    $ltp = floatval($tickData['ltp']);
                    $source = 'redis';
                }
            }

            // Fallback to instrument table
            if (!$ltp) {
                $instrument = Instrument::where('instrument_token', $token)->first();
                if ($instrument && $instrument->last_price > 0) {
                    $ltp = floatval($instrument->last_price);
                    $source = 'instrument';
                }
            }

            if (!$ltp) continue;

            $targetPrice = round($ltp + ($ltp * $targetPercent / 100), 2);
            $stoplossPercent = getStoplossPercent(); // your config helper
            $stoplossPrice = round($ltp - ($ltp * $stoplossPercent / 100), 2);

            PendingOrder::create([
                'zerodha_account_id' => $this->accountId,
                'symbol' => $symbol,
                'qty' => $qty,
                'target_percent' => $targetPercent,
                'ltp_at_upload' => $ltp,
                'target_price' => $targetPrice,
                'stoploss_price' => $stoplossPrice,
                'product' => $product,
                'ltp_source' => $source, // Optional: add this column in DB if you want
            ]);
        }
    }
}
