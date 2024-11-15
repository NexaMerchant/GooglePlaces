<?php
/**
 * 
 * This file is auto generate by Nicelizhi\Apps\Commands\Create
 * @author Steve
 * @date 2024-11-15 14:47:28
 * @link https://github.com/xxxl4
 * 
 */
use Illuminate\Support\Facades\Route;
use NexaMerchant\GooglePlaces\Http\Controllers\Api\ExampleController;

Route::group(['middleware' => ['api'], 'prefix' => 'api'], function () {
     Route::prefix('googleplaces')->group(function () {

        Route::controller(ExampleController::class)->prefix('example')->group(function () {

            Route::get('demo', 'demo')->name('googleplaces.api.example.demo');

        });

     });
});