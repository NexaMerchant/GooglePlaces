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
use NexaMerchant\GooglePlaces\Http\Controllers\Admin\ExampleController;

Route::group(['middleware' => ['admin','admin_option_log'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('googleplaces')->group(function () {

        Route::controller(ExampleController::class)->prefix('example')->group(function () {

            Route::get('demo', 'demo')->name('googleplaces.admin.example.demo');

        });

    });
});