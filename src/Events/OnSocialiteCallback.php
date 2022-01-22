<?php

namespace Admin\Socialite\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnSocialiteCallback
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $auth;
    public $driverType;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($auth, $driverType)
    {
        $this->auth = $auth;
        $this->driverType = $driverType;
    }
}
