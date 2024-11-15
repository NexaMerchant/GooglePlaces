<?php
/**
 * 
 * This file is auto generate by Nicelizhi\Apps\Commands\Create
 * @author Steve
 * @date 2024-11-15 14:47:28
 * @link https://github.com/xxxl4
 * 
 */
namespace NexaMerchant\GooglePlaces\Console\Commands;

use NexaMerchant\Apps\Console\Commands\CommandInterface;

class UnInstall extends CommandInterface 

{
    protected $signature = 'GooglePlaces:uninstall';

    protected $description = 'Uninstall GooglePlaces an app';

    public function getAppVer() {
        return config("GooglePlaces.ver");
    }

    public function getAppName() {
        return config("GooglePlaces.name");
    }

    public function handle()
    {
        if (!$this->confirm('Do you wish to continue?')) {
            // ...
            $this->error("App GooglePlaces UnInstall cannelled");
            return false;
        }
    }
}