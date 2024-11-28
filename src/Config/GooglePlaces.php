<?php
/**
 * 
 * This file is auto generate by Nicelizhi\Apps\Commands\Create
 * @author Steve
 * @date 2024-11-15 14:47:28
 * @link https://github.com/xxxl4
 * 
 */
return [
    /**
     * 
     * The name of the package
     */
    'name' => 'GooglePlaces',
    /**
     * 
     * The version of the package
     */
    'version' => '1.0.7',
    /**
     * 
     * The version number of the package
     */
    'versionNum' => '107',

    /**
     *
     * Enabled
     */
     'enable' => env('GooglePlaces.ENABLED', true),

     /*
      * Composer Package Name
     */
    'composer' => 'nexa-merchant/googleplaces',

    /**
     * 
     * The description of the package
     */
    'description' => 'Check order place by google place api',

    /**
     * 
     * The author of the package
     */
    'author' => 'Steve',

    /**
     * 
     * The email of the author
     */
    'email' => 'email@example.com',

    /**
     * 
     * The homepage of the package
     */
    'homepage' => 'https://github.com/xxl4',

    /**
     * 
     * The keywords of the package
     */
    'keywords' => [],

    /**
     * 
     * The license of the package
     */
    'license' => 'MIT',

    /**
     * 
     * The type of the package
     */
    'type' => 'library',

    /**
     * 
     * The support of the package
     */
    'support' => [
        'email' => 'email@example.com',
        'issues' => 'https://github.com/xxl4'
    ],

    /**
     * 
     * Google Place Api Key
     * 
     */
    'google_place_api_key' => env('GOOGLE_PLACE_API_KEY', ''),

    /**
     * 
     * Feishu Webhook
     * 
     */
    'feishu_webhook' => env('GOOGLEPLACE_FEISHU_WEBHOOK', ''),
];