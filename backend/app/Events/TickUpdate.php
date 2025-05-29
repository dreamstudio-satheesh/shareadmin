<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TickUpdate implements ShouldBroadcastNow
{
    use SerializesModels;

    public array $tick;

    public function __construct(array $tick)
    {
        $this->tick = $tick;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('ticks');
    }

    public function broadcastAs(): string
    {
        return 'TickUpdate';
    }
}
