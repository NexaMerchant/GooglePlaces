<?php
namespace NexaMerchant\GooglePlaces\Console\Commands;

use Illuminate\Console\Command;

class DownLocales extends Command
{
    protected $signature = 'GooglePlaces:down-locales';
    protected $description = 'Download locales from Google Places API';

    private $locale_data_url = 'https://chromium-i18n.appspot.com/ssl-address/data';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Downloading locales from Google Places API');

        /***
         * 
         * @link https://chromium-i18n.appspot.com/ssl-address
         * 
         * 
         */

        $locales = json_decode(file_get_contents($this->locale_data_url));

        if (isset($locales->countries)) {
            //For some reason the countries are seperated by a tilde
            $countries = explode('~', $locales->countries);



            $data_dir = storage_path('app/locales/i18n');

            if (!is_dir($data_dir)) {
                mkdir($data_dir, 0777, true);
            }


            //Loop countries and grab the corrosponding json data
            foreach ($countries as $country) {
                $file = $data_dir . '/' . $country . '.json';

                file_put_contents($file, file_get_contents($this->locale_data_url . '/' . $country));
            }
        }

    }
}