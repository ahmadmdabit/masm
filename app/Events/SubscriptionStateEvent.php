<?php

namespace App\Events;

use App\Models\EventModel;

class SubscriptionStateEvent extends Event
{
    public EventModel $eventModel;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(EventModel $eventModel)
    {
        $this->eventModel = $eventModel;
        $this->eventModel->name = (String)SubscriptionStateEvent::class;
    }
}
