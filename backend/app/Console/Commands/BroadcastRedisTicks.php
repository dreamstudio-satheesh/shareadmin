<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Events\TickUpdate;
use Throwable;

class BroadcastRedisTicks extends Command
{
    protected $signature = 'ticks:broadcast';
    protected $description = 'Listen to Redis ticks and broadcast them via Reverb';

    public function handle()
    {
        if (!isMarketOpen()) {
            $this->info('⏹ Market is closed or today is not a trading day. Skipping tick broadcast.');
            return;
        }

        $this->info('📡 Starting Redis tick broadcaster...');

        try {
            $redis = Redis::connection()->client(); // get raw phpRedis client

            $redis->subscribe(['ticks'], function ($redis, $channel, $message) {
                try {
                    $data = json_decode($message, true);

                    if (is_array($data)) {
                        broadcast(new TickUpdate($data))->toOthers();
                        logger()->info('🔥 Broadcasted TickUpdate: ' . json_encode($data));
                    }
                } catch (Throwable $inner) {
                    logger()->error('❌ Tick handler error: ' . $inner->getMessage());
                    report($inner);
                }
            });

        } catch (Throwable $e) {
            $this->error('❌ Error in tick broadcasting: ' . $e->getMessage());
            report($e);
        }
    }
}
