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
            $redis = Redis::connection();
            $pubsub = $redis->pubSubLoop();
            $pubsub->subscribe('ticks');

            foreach ($pubsub as $message) {
                if (!isMarketOpen()) {
                    $this->info('⏹ Market closed during broadcast. Exiting loop.');
                    break;
                }

                if ($message->kind === 'message') {
                    $data = json_decode($message->payload, true);

                    if (is_array($data)) {
                        broadcast(new TickUpdate($data));
                        logger()->info('🔥 Sent TickUpdate: ' . json_encode($data));
                    } else {
                        logger()->warning('⚠️ Invalid tick data: ' . $message->payload);
                    }
                }
            }

            $pubsub->unsubscribe();
            $this->info('✅ Tick broadcasting stopped.');

        } catch (Throwable $e) {
            $this->error('❌ Error in tick broadcasting: ' . $e->getMessage());
            report($e);
        }
    }
}
