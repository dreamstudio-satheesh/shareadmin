<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Events\TickUpdated;

class RelayRedisTicks extends Command
{
    protected $signature = 'tick:relay';
    protected $description = 'Relay ticks from Redis PubSub to Pusher WebSocket';

    public function handle()
    {
        $this->info('📡 Listening for ticks on Redis pub/sub...');

        Redis::connection()->psubscribe(['ticks'], function ($message, $channel) {
            $data = json_decode($message, true);
            if ($data && isset($data['token'], $data['ltp'])) {
                broadcast(new TickUpdated($data['token'], $data));
                $this->info("🔁 Broadcasted tick for token {$data['token']}: ₹{$data['ltp']}");
            }
        });
    }
}
