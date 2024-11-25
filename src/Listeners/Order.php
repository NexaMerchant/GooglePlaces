<?php

namespace NexaMerchant\GooglePlaces\Listeners;

use Illuminate\Support\Facades\Log;
use Nicelizhi\Manage\Listeners\Base;
use Illuminate\Support\Facades\Artisan;

class Order extends Base
{
    /**
     * After order is created
     *
     * @param  \Webkul\Sale\Contracts\Order  $order
     * @return void
     */
    public function afterCreated($order)
    {
        if(!config('GooglePlaces.enable')) {
            return;
        }
        Artisan::queue("GooglePlaces:check-order", ['--order_id'=> $order->id])->onQueue('check-order');

        Log::info('Order created for check place : ' . $order->id);
    }
}