<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/
//Route::get('phpinfo', function()
//{
//    phpinfo();
//});

//Route::resource('upload', "HomeController@showWelcome");
//Route::get('/', 'HomeController@index');
Route::get('/', function() {
	$urltoken = 'no token - already logged in';
		
	if(Session::get('logged_in') != 'true') {
		if(Input::get('token') == '' || Input::get('token') == null) {
			return Redirect::to('https://daq03.triumf.ca/daqinv/frontend/import');
		}
		else {
			$urltoken = Input::get('token');

		try {
			$dbtoken = DB::table('tokens')->where('token', '=', $urltoken)->get();
			if($dbtoken == null) {
				//return 'token not found';
				return Redirect::to('https://daq03.triumf.ca/daqinv/frontend');
			}

		}
		catch(Exception $e) {
			//return 'token not found exception';
			return Redirect::to('https://daq03.triumf.ca/daqinv/frontend');
		}
		
		//store confirmed in session variable - so other pages can check if confirmed
		Session::put('logged_in', 'true');
		}
	}

	//this is just for testing
	$tokens = DB::table('tokens')->lists('token');
	
    return View::make('Home.main')->with('alltokens', array('tokens' => $tokens, 'urltoken' => $urltoken));
});
//Route::resource('upload', 'HomeController');
Route::any('import/file', 'HomeController@submit');

Route::get('import', 'HomeController@import');

Route::get('export', function() {
	if(Session::get('logged_in') != 'true'){
		return Redirect::to('https://daq03.triumf.ca/daqinv/frontend/import');
	}
	
    $categories = DB::table('categories')->orderBy('name')->lists('name');
    return View::make('Home.export')->with('categories', $categories);
});

Route::post('export/category', 'HomeController@exportCategory');

Route::get('template', 'HomeController@writeTemplate');
