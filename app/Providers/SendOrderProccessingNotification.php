<?php

namespace App\Providers;

use App\Providers\OrderProccessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderProccessingNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderProccessing $event): void
    {
        //
    }
}
