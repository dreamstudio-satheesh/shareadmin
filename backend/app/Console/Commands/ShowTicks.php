<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ShowTicks extends Command
{
    protected $signature = 'ticks:show';
    protected $description = 'Fetch and display ticks from Redis';

    public function handle()
    {
        $keys = Redis::keys('tick:*');
        if (empty($keys)) {
            $this->info('No tick data found in Redis.');
            return;
        }

        foreach ($keys as $key) {
            $data = json_decode(Redis::get($key), true);
            $this->line("$key → ₹" . $data['ltp'] . " at " . $data['time']);
        }
    }
}
