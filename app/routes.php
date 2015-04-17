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
    return View::make('Home.main');
});
Route::resource('upload', 'HomeController');
Route::any('import/file', 'HomeController@submit');

Route::get('import', function(){
    $categories = DB::table('categories')->orderBy('name')->lists('name');
    return View::make('Home.import')->with('categories', $categories);
});

Route::get('export', function(){
    return View::make('Home.export');
});
Route::get('template', 'HomeController@writeTemplate');
