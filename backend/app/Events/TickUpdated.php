<?php

// app/Events/TickUpdated.php

namespace App\Events;


use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TickUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $token, $data;

    public function __construct($token, $data)
    {
        $this->token = $token;
        $this->data = $data;
    }

    public function broadcastOn()
    {
         Log::info('Broadcasting TickUpdated on channel ticks');
        return new Channel('ticks'); // 👈 PUBLIC CHANNEL (not PrivateChannel)
    }

    public function broadcastAs()
    {
        return 'TickUpdated'; // 👈 You MUST match this in JS: .listen('.TickUpdated')
    }

    public function broadcastWith()
    {
        return [
            'token' => $this->token,
            'data' => $this->data,
        ];
    }
}

