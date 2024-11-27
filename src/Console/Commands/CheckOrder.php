<?php

namespace NexaMerchant\GooglePlaces\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Webkul\Sales\Repositories\OrderRepository;
use Illuminate\Support\Facades\Redis;

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

        var_dump($order_id);

        $order = $this->orderRepository->find($order_id);
        if(!$order){
            $this->error('Order not found');
            return;
        }

        // valid the order email
        if(!filter_var($order->customer_email, FILTER_VALIDATE_EMAIL)){
            $this->error('Invalid Email: ' . $order->customer_email);

            $text = "URL: ".config("app.url")."\n Order ID ".config('shopify.order_pre').'#'.$order_id." \n Invalid Email: " . $order->customer_email;

            $this->send($text);

            return;
        }

        // valid the order phone, and the phone maybe phone number or mobile number
        if(!preg_match('/^\+?\d+$/', $order->shipping_address->phone)){
            $this->error('Invalid Phone: ' . $order->shipping_address->phone);

            $text = "URL: ".config("app.url")."\n Order ID ".config('shopify.order_pre').'#'.$order_id." \n Invalid Phone: " . $order->shipping_address->phone;

            $this->send($text);

            return;
        }
        // check the postcode with state

        $address = $order->shipping_address->address1.', '.$order->shipping_address->city.', '.$order->shipping_address->state.' '.$order->shipping_address->postcode;

        //var_dump($address);exit;
        $this->info('Address: ' . $address. ' Country: ' . $order->shipping_address->country);

        $order_create_country = null;
        $order_create_ip = null;
        $ip_details = null;
        $order_shopify_id = null;

        //if the order is cod 
        if($order->payment->method == 'codpayment'){
            $this->info('Order is COD');
            
            // check the order create country
            if(config("GooglePlaces.enable")=="true") {
                $order_cod = \NexaMerchant\CheckoutCod\Models\OrderCods::where('order_id', $order->id)->first();
                if($order_cod) {
                    $order_create_country = $order_cod->ip_country;
                    $order_create_ip = $order_cod->ip_address;
                }
            }

            if(config("shopify.enable")=="true") {
                $order_shopify = \Nicelizhi\Shopify\Models\ShopifyOrder::where('order_id', $order->id)->first();
                if($order_shopify) {
                    $order_shopify_id = $order_shopify->shopify_order_id;
                }
            }

            // use the ip look up to get the more detail of the ip
            if($order_create_ip){
                // use the ip look up from redis
                if($order_create_country != $order->shipping_address->country){

                    $text = "URL: ".config("app.url")."\n Order ID ".config('shopify.order_pre').'#'.$order_id." \n Shopify ID：".$order_shopify_id." \n Address ".$address." \n IP Country Code: " . $order_create_country . ' is not the same as the order create country code: ' . $order->shipping_address->country;
                    $this->send($text);
                    return;
                }
            }

           

        }

        // check the repeat order by ip
        if($order_create_ip){
            $total = \NexaMerchant\CheckoutCod\Models\OrderCods::where('ip_address', $order_create_ip)->count();

            if($total>2){
                $text = "URL: ".config("app.url")."\n Order ID ".config('shopify.order_pre').'#'.$order_id." \n Shopify ID：".$order_shopify_id." \n IP Address: " . $order_create_ip . ' has ' . $total . ' orders';
                $this->send($text);
            }

        }

        // check the repeat order by email
        $total = $this->orderRepository->findWhere(['customer_email' => $order->customer_email, 'status' => 'processing'])->count();

        if($total>2){
            $text = "URL: ".config("app.url")."\n Order ID ".config('shopify.order_pre').'#'.$order_id." \n Shopify ID：".$order_shopify_id."\n Email: " . $order->customer_email . ' has ' . $total . ' orders';
            $this->send($text);
        }

        // // check the repeat order by phone
        // $total = \Webkul\Sales\Models\Order::with('addresses')->where('addresses.phone', $order->shipping_address->phone)->where("addresses.address_type", \Webkul\Sales\Models\OrderAddress::ADDRESS_TYPE_SHIPPING)->where('status', 'processing')->count();

        // if($total>2){
        //     $text = "URL: ".config("app.url")."\n Order ID ".$order_id." \n Phone: " . $order->shipping_address->phone . ' has ' . $total . ' orders';
        //     $this->send($text);
        // }
        // // check the repeat order by address
        // $total = $this->orderRepository->findWhere(['shipping_address.address1' => $order->shipping_address->address1, 'status' => 'processing'])->count();

        // if($total>2){
        //     $text = "URL: ".config("app.url")."\n Order ID ".$order_id." \n Address: " . $order->shipping_address->address1 . ' has ' . $total . ' orders';
        //     $this->send($text);
        // }


        $this->info('Order Create Country: ' . $order_create_country);
        $this->info('Order Create IP: ' . $order_create_ip);
        
        //var_dump($ip_details);

        //if the order in redis and return redis data
        $redis_data = Redis::get('GooglePlaces:order:'.$order_id);
        if($redis_data){
            var_dump(json_decode($redis_data, true));

            $redis_data = json_decode($redis_data, true);
            if(isset($redis_data['status'])) {
                if($redis_data['status']!='OK'){
                
                    if(config('GooglePlaces.enable')=="true" && config('GooglePlaces.feishu_webhook')) {
    
                        $text = "URL: ".config("app.url")."\n Order ID:  ".config('shopify.order_pre').'#'.$order_id." \n Shopify ID：".$order_shopify_id." \n Address:  ".$address. " \n Country: " .$order->shipping_address->country." \n Google Place Api Error: " . json_encode($resp);
        
                        $this->send($text);
                        
                    }
                }

                // return; // for testing
            }
        }

        $resp = $this->checkAddress($address, $order, $order_shopify_id);


        Redis::set('GooglePlaces:order:'.$order->id, json_encode($resp));


        
    }

    private function send($text) {
        $client = new Client([
            'base_uri' => 'https://open.feishu.cn/open-apis/bot/v2/hook/',
            'debug' => false,
        ]);

        $response = $client->request('POST', config('GooglePlaces.feishu_webhook'), [
            'json' => [
                'msg_type' => 'text',
                'content' => [
                    'text' => $text,
                ]
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                // add your headers here
                'Brand' => 'NexaMerchant',
            ]
        ]);
    }

    /**
     * 
     * @param string $address
     * @param object $order
     * 
     * @return array
     * 
     */
    private function checkAddress($address, $order, $order_shopify_id = null) {
        
        $resp = $this->searchGoogleMap($address, $order);
        var_dump($resp);
        if($resp['status']!='OK'){
            $address = $order->shipping_address->address1.' '.$order->shipping_address->postcode;
            $this->info('Address: ' . $address. ' Country: ' . $order->shipping_address->country);
            $resp = $this->searchGoogleMap($address, $order);

            $order_id = $order->id;

            // when it is not OK
            if($resp['status']!='OK'){
                if(config('GooglePlaces.enable')=="true" && config('GooglePlaces.feishu_webhook')) {
                    $text = "URL: ".config("app.url")."\n Order ID:  ".config('shopify.order_pre').'#'.$order_id." \n Shopify ID：".$order_shopify_id." \n Address:  ".$address. " \n Country: " .$order->shipping_address->country." \n Google Place Api Error: " . json_encode($resp);
                    $this->send($text);
                }
                return;
            }
        }

    }

    // 
    private function searchGoogleMap($address, $order) {
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

        return $resp;
    }
}