<?php

namespace NexaMerchant\GooglePlaces\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Webkul\Sales\Repositories\OrderRepository;

class CheckOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'GooglePlaces:check-order {--order_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check order address use Google Place Api';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        OrderRepository $orderRepository
    )
    {
        parent::__construct();
        $this->orderRepository = $orderRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->info('Check order address use Google Place Api');

        $order_id = $this->option('order_id');
        $this->info('Order ID: ' . $order_id);

        $order = $this->orderRepository->find($order_id);

        $address = $order->shipping_address->address1.', '.$order->shipping_address->city.', '.$order->shipping_address->state.' '.$order->shipping_address->postcode;

        //var_dump($address);exit;
        $this->info('Address: ' . $address);

        var_dump($order->shipping_address->toArray());


        $client = new Client([
            'base_uri' => 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json',
            'debug' => false,
        ]);

        $response = $client->request('GET', '', [
            'query' => [
               // 'input' => '1600 Amphitheatre Parkway, Mountain View, CA',
                'input' => $address,
                'inputtype' => 'textquery',
                'fields' => 'formatted_address,name,geometry,plus_code,place_id,icon',
                'key' => config('GooglePlaces.google_place_api_key'),
                'region' => $order->shipping_address->country
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);

        var_dump($response->getBody()->getContents());


    }
}