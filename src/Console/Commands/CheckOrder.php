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

        $order = $this->orderRepository->find($order_id);
        if(!$order){
            $this->error('Order not found');
            return;
        }

        $address = $order->shipping_address->address1.', '.$order->shipping_address->city.', '.$order->shipping_address->state.' '.$order->shipping_address->postcode;

        //var_dump($address);exit;
        $this->info('Address: ' . $address. ' Country: ' . $order->shipping_address->country);

        $order_create_country = null;
        $order_create_ip = null;
        $ip_details = null;

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

            // use the ip look up to get the more detail of the ip
            if($order_create_ip){
                // use the ip look up from redis
                $ip_details = Redis::get('GooglePlaces:ip:'.$order_create_ip);
                if($ip_details){
                    $ip_details = json_decode($ip_details, true);
                }else{
                    $client = new Client([
                        'base_uri' => 'http://ip-api.com/json/',
                        'debug' => false,
                    ]);

                    $response = $client->request('GET', $order_create_ip, [
                        'headers' => [
                            // add your headers here
                            'Brand' => 'NexaMerchant',
                        ]
                    ]);

                    $resp = $response->getBody()->getContents();

                    //var_dump($resp);

                    $resp = json_decode($resp, true);
                    if($resp['status']=='success'){

                        $ip_details = $resp;
                        Redis::set('GooglePlaces:ip:'.$order_create_ip, json_encode($resp));
                        // check the ip countryCode
                        if($ip_details){
                            
                            $this->info('IP Details: ' . json_encode($ip_details));
                            $this->info('IP Country Code: ' . $ip_details['countryCode']);

                            // if the ip country code is not the same as the order create country code
                            if($ip_details['countryCode'] != $order->shipping_address->country){

                                $text = "URL: ".config("app.url")."\n Order ID ".$order_id." \n Address ".$address." \n IP Country Code: " . $ip_details['countryCode'] . ' is not the same as the order create country code: ' . $order->shipping_address->country;
                                $this->send($text);
                                return;
                            }

                        }
                    }
                    
                }
            }
        }


        $this->info('Order Create Country: ' . $order_create_country);
        $this->info('Order Create IP: ' . $order_create_ip);
        
        var_dump($ip_details);

        //if the order in redis and return redis data
        $redis_data = Redis::get('GooglePlaces:order:'.$order_id);
        if($redis_data){
            var_dump(json_decode($redis_data, true));

            $redis_data = json_decode($redis_data, true);

            if($redis_data['status']!='OK'){
                
                if(config('GooglePlaces.enable')=="true" && config('GooglePlaces.feishu_webhook')) {

                    $text = "URL: ".config("app.url")."\n Order ID:  ".$order_id." \n Address:  ".$address. " \n Country: " .$order->shipping_address->country." \n Google Place Api Error: " . json_encode($resp);
    
                    $this->send($text);
                    
                }
            }
            // return; // for testing
        }

        $resp = $this->searchGoogleMap($address, $order);

        // when it is not OK
        if($resp['status']!='OK'){
            $this->error('Error: ' . $resp['status']);

            // send the error to feishu
            // search city and code

            $address = $order->shipping_address->city.', '.$order->shipping_address->postcode;

            $this->info('Address: ' . $address. ' Country: ' . $order->shipping_address->country);

            $resp = $this->searchGoogleMap($address, $order);

            // when it is not OK
            if($resp['status']!='OK'){
                
                if(config('GooglePlaces.enable')=="true" && config('GooglePlaces.feishu_webhook')) {

                    $text = "URL: ".config("app.url")."\n Order ID:  ".$order_id." \n Address:  ".$address. " \n Country: " .$order->shipping_address->country." \n Google Place Api Error: " . json_encode($resp);
    
                    $this->send($text);
                    
                }
    
                return;
            }


        }


        Redis::set('GooglePlaces:order:'.$order->id, json_encode($resp));

        var_dump($resp);
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

        if($resp['status']=='OK'){

            $this->info('Search Zip CODE ID: ' . $resp['candidates'][0]['geometry']['location']['lat'].','.$resp['candidates'][0]['geometry']['location']['lng']);

            $client = new Client([
                'base_uri' => 'https://maps.googleapis.com/maps/api/geocode/json',
                'debug' => false,
            ]);

            $response = $client->request('GET', '', [
                'query' => [
                    //'place_id' => $resp['candidates'][0]['place_id'],
                    //'latlng' => $resp['candidates'][0]['geometry']['location']['lat'].','.$resp['candidates'][0]['geometry']['location']['lng'],
                    'address' => $address,
                    'key' => config('GooglePlaces.google_place_api_key'),
                    'fields' => 'address_component,formatted_address,geometry,plus_code',
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    // add your headers here
                    'Brand' => 'NexaMerchant',
                ]
            ]);

            $rp = $response->getBody()->getContents();

            $rp = json_decode($rp, true);

            var_dump($rp);
        }

        return $resp;
    }
}