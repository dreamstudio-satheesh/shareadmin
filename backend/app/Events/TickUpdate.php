<?php

// app/Events/TickUpdate.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class TickUpdate implements ShouldBroadcastNow
{
    public $tick;

    public function __construct(array $tick)
    {
        $this->tick = $tick;
    }

    public function broadcastOn()
    {
        return new Channel('ticks');
    }

    public function broadcastAs()
    {
        return 'TickUpdate';
    }
}
