<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    private string $type;
    private $product;

    public function __construct(string $type, $product)
    {
        $this->product = $product;
        $this->type = $type;
    }

    public function broadcastOn()
    {
        return new Channel($this->type . '.product');
    }

    public function broadcastWith()
    {
        return [
            'product' => $this->product
        ];
    }
}
