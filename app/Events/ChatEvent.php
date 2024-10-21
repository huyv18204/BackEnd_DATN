<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private string $message;
    private $user;

    public $sender;

    public function __construct(string $message, $user,$sender)
    {
        $this->message = $message;
        $this->user = $user;
        $this->sender = $sender;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->user->id);
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'user' => $this->user,
            'sender' => $this->sender
        ];
    }
}
