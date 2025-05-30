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
            // $this->info('⏹ Market is closed.');
            // return;
        }

        $this->info('📡 Starting Redis tick broadcaster...');

        try {
            Redis::subscribe(['ticks'], function ($message) {
                $data = json_decode($message, true);

                if (is_array($data)) {
                    broadcast(new TickUpdate($data))->toOthers();
                    logger()->info('🔥 Broadcasted TickUpdate: ' . json_encode($data));
                }
            });
        } catch (Throwable $e) {
            $this->error('❌ Error in tick broadcasting: ' . $e->getMessage());
            report($e);
        }
    }
}
