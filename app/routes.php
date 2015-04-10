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

Route::get('phpinfo', function()
{
    phpinfo();
});

//Route::resource('upload', "HomeController@showWelcome");
Route::get('/', 'HomeController@index');
Route::resource('upload', 'UploadController');
Route::any('upload/file', 'UploadController@submit');