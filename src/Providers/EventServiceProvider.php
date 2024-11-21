<?php

namespace NexaMerchant\GooglePlaces\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'checkout.order.save.after' => [
            'NexaMerchant\GooglePlaces\Listeners\Order@afterCreated',
        ],
    ];
}
