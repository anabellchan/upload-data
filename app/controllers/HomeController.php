<?php

class HomeController extends BaseController {

    /*
    |--------------------------------------------------------------------------
    | Default Home Controller
    |--------------------------------------------------------------------------
    |
    | You may wish to use controllers instead of, or in addition to, Closure
    | based routes. That's great! Here is an example controller method to
    | get you started. To route to this controller, just add the route:
    |
    |	Route::get('/', 'HomeController@showWelcome');
    |
    */

//	public function showWelcome()
//	{
//		return View::make('hello');
//	}

    public function index()
    {
        $categories = DB::table('categories')->orderBy('name')->lists('name');

        return View::make("home.upload")->with('categories', $categories);
//
//        return View::make("home.index");
    }

}
