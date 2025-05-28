<?php
// app/Console/Commands/BroadcastRedisTicks.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Events\TickUpdate;

class BroadcastRedisTicks extends Command
{
    protected $signature = 'ticks:broadcast';
    protected $description = 'Listen to Redis ticks and broadcast them via Reverb';

    public function handle()
    {
        Redis::subscribe(['ticks'], function ($message) {
            $data = json_decode($message, true);

            if ($data) {
                broadcast(new TickUpdate($data));
            }
        });
    }
}
