<?php

namespace NexaMerchant\GooglePlaces\Listeners;

use Illuminate\Support\Facades\Log;
use Nicelizhi\Manage\Listeners\Base;

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
        //Artisan::queue((new Post())->getName(), ['--order_id'=> $order->id])->onConnection('redis')->onQueue('commands');
        // use default queue
        //Artisan::queue((new Post())->getName(), ['--order_id'=> $order->id]);

        Artisan::call('GooglePlaces:check-order', ['--order_id'=> $order->id]);

        Log::info('Order created for check place : ' . $order->id);
    }
}