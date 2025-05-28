<?php

namespace App\Events;

use Laravel\Reverb\Loggers\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TickUpdated implements ShouldBroadcast
{
    public $token, $data;

    public function __construct($token, $data)
    {
        $this->token = $token;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        Log::info('🔥 Broadcasting TickUpdated via: ' . config('broadcasting.default'));
        return new Channel('ticks');
    }

    public function broadcastWith()
    {
        return [
            'token' => $this->token,
            'data' => $this->data,
        ];
    }
}
