<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PendingOrder;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class MonitorOrders extends Command
{
    protected $signature = 'monitor:orders';
    protected $description = 'Monitor pending orders and update status based on live LTP';

    public function handle()
    {
        if (!isMarketOpen() || !isTradingDay()) {
            $this->info('Market is closed. Skipping MonitorOrders.');
            return;
        }

        $orders = PendingOrder::with('account')
            ->where('status', 'pending')
            ->get();

        foreach ($orders as $order) {
            $account = $order->account;

            if (! $account?->access_token) {
                $order->update(['status' => 'failed', 'reason' => 'Missing access token']);
                Log::warning("Order {$order->id} failed: missing access token");
                continue;
            }

            $symbol = 'NSE:' . $order->symbol;
            $tickKey = "tick:$symbol";
            $tick = Redis::hgetall($tickKey);

            if (! $tick || ! isset($tick['lp'])) {
                Log::info("Order {$order->id} skipped: No tick data for $symbol");
                continue;
            }

            $ltp = floatval($tick['lp']);
            $executed = false;

            if ($ltp >= $order->target_price) {
                $order->update([
                    'status' => 'executed',
                    'executed_price' => $ltp,
                    'executed_at' => now(),
                ]);
                Log::info("Order {$order->id} executed at $ltp");
                $executed = true;
            }

            if (! $executed && $ltp <= $order->stoploss_price) {
                $order->update([
                    'status' => 'cancelled',
                    'stoploss_triggered_at' => now(),
                    'reason' => 'Stoploss hit',
                ]);
                Log::info("Order {$order->id} cancelled due to stoploss at $ltp");
            }
        }

        $this->info("✔ Order monitoring cycle complete.");
    }
}
