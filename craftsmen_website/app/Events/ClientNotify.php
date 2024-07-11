<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientNotify implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $user_id;
    public $title;
    public $msg;
    public $notify_id;
    public function __construct($user_id,$title,$msg,$notify_id)
    {
        $this->user_id = $user_id;
        $this->title = $title;
        $this->msg = $msg;
        $this->notify_id = $notify_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn(): array
    {
        return [
            new Channel($this->user_id . 'ClientNotify')
        ];
    }

    public function broadcastAs()
    {
        return $this->user_id . 'ClientNotify';
    }

    public function broadcastWith(): array
    {
        return ['title' => $this->title, 'msg' => $this->msg, 'notify_id' => $this->notify_id];
    }
}
