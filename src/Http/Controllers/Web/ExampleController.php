<?php
/**
 * 
 * This file is auto generate by Nicelizhi\Apps\Commands\Create
 * @author Steve
 * @date 2024-11-15 14:47:28
 * @link https://github.com/xxxl4
 * 
 */
namespace NexaMerchant\GooglePlaces\Http\Controllers\Web;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

class ExampleController extends Controller
{
    public function demo(Request $request) {
        $data = [];
        $data['code'] = 200;
        $data['message'] = "Demo";
        return view('GooglePlaces::demo', compact("data"));
    }
}
