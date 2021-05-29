<?php

namespace App\Listeners;

use App\Events\SubscriptionStateEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Helpers\CurlHelper;
use Illuminate\Support\Facades\DB;

class SubscriptionStateListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\SubscriptionStateEvent  $event
     * @return void
     */
    public function handle(SubscriptionStateEvent $event)
    {
        $tries = 5;
        $condition = false;
        do {
            $condition = $this->process($event);
        } while (!$condition && --$tries > 0);
    }

    protected function process(SubscriptionStateEvent $event)
    {
        try {
            DB::insert(
                'INSERT INTO `events`(`name`, `device_id`, `app_id`, `info`)
                 VALUES (?,?,?,?)', [
                    $event->eventModel->name,
                    $event->eventModel->device_id,
                    $event->eventModel->app_id,
                    json_encode($event->eventModel->info),
                ]
            );

            $response = CurlHelper::post(
                env('MOCK_URL').'/mock/slack-channel',
                [
                    'device_id' => $event->eventModel->device_id,
                    'app_id' => $event->eventModel->app_id,
                    'info' => json_encode($event->eventModel->info),
                ]);
            if (isset($response)) {
                if (is_bool($response)) {
                    if ($response) {
                        return true;
                    }
                    else {
                        return false;
                    }
                }
                else {
                    return true;
                }
            }
            else {
                return false;
            }
        } catch (\Throwable $th) {
            throw $th;
            return false;
        }
    }
}
