<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TickUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $token;
    public $data;

    public function __construct($token, $data)
    {
        $this->token = $token;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new Channel('ticks');
    }

    public function broadcastAs()
    {
        return 'TickUpdated';
    }
}
