<?php

namespace App\Providers;
use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderProccessing implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
  public $progress;
    /**
     * Create a new event instance.
     */
    public function __construct( $progress)
    {
        $this->progress=$progress;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        
        return [
           // new PrivateChannel('stylizeit'),
            new Channel('order_progress')
        ];
    }
}
