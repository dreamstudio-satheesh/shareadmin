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
        $this->info('📡 Starting Redis tick broadcaster...');

        try {
            if (!isMarketOpen()) {
                $this->warn('⏹ Market is closed. Skipping tick broadcast.');
                return;
            }

            Redis::psubscribe(['ticks'], function ($message, $channel) {
                try {
                    if (!isMarketOpen()) {
                        logger()->info('⏹ Market closed during broadcast. Ignoring tick.');
                        return;
                    }

                    $data = json_decode($message, true);

                    if (is_array($data)) {
                        broadcast(new TickUpdate($data))->toOthers();
                        logger()->info("📤 Tick broadcasted", $data);
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
