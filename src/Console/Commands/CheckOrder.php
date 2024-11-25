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
    protected $description = 'Check order address use Google Place Api GooglePlaces:check-order {--order_id=}';

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
        if (! config('GooglePlaces.google_place_api_key')) {
            $this->error('Google Place Api Key is not configured');
            return;
        }

       // $this->info('Check order address use Google Place Api');
        $order_id = $this->option('order_id');
        $this->info('Order ID: ' . $order_id);

        $order = $this->orderRepository->find($order_id);
        if(!$order){
            $this->error('Order not found');
            return;
        }

        $address = $order->shipping_address->address1.', '.$order->shipping_address->city.', '.$order->shipping_address->state.' '.$order->shipping_address->postcode;

        //var_dump($address);exit;
        $this->info('Address: ' . $address);

        $order_create_country = null;
        $order_create_ip = null;

        //if the order is cod 
        if($order->payment->method == 'codpayment'){
            $this->info('Order is COD');
            
            // check the order create country
            if(config("GooglePlaces.enable")) {
                $order_code = \NexaMerchant\CheckoutCod\Models\OrderCodsProxy::where('order_id', $order->id)->first();
                $order_create_country = $order_code->country;
                $order_create_ip = $order_code->ip;
            }
        }

        $this->info('Order Create Country: ' . $order_create_country);
        $this->info('Order Create IP: ' . $order_create_ip);


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
                'Accept' => 'application/json',
                // add your headers here
                'Brand' => 'NexaMerchant',
            ]
        ]);

        $resp = $response->getBody()->getContents();

        $resp = json_decode($resp, true);

        // when it is not OK
        if($resp['status']!='OK'){
            $this->error('Error: ' . $resp['status']);
            return;
        }

        var_dump($resp);


    }
}