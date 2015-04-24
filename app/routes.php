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
			
			//clear expired 30min tokens from db
			$currentTime = $dbtoken[0]->ts;
			$all_db_tokens = DB::table('tokens')->get();
			foreach ($all_db_tokens as $old_token)
			{
				$db_item_time = $old_token->ts;
				$datetime1 = new DateTime($currentTime);
				$datetime2 = new DateTime($db_item_time);
				$difference_in_seconds = $datetime1->format('U') - $datetime2->format('U');
				//echo $difference_in_seconds;
				if($difference_in_seconds > 1800) {
					DB::table('tokens')->delete($old_token->id);
				}
			}
			
			//store confirmed in session variable - so other pages can check if confirmed
			Session::put('logged_in', 'true');
		}
	}
	//these 2 lines are just for testing db token deletions
	//$tokens = DB::table('tokens')->get();
    //return View::make('Home.main')->with('alltokens', array('tokens' => $tokens, 'urltoken' => $urltoken));
	
	return View::make('Home.main');
});
//Route::resource('upload', 'HomeController');
Route::any('import/file', 'HomeController@submit');

Route::get('import', 'HomeController@import');

Route::get('export', 'HomeController@export');

Route::post('export/category', 'HomeController@exportCategory');

Route::get('template', 'HomeController@writeTemplate');
