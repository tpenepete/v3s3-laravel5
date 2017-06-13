<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::put('/v3s3/{object}', 'v3s3\v3s3_Controller@put');
Route::get('/v3s3/{object}', 'v3s3\v3s3_Controller@get');
Route::delete('/v3s3/{object}', 'v3s3\v3s3_Controller@delete');
Route::post('/v3s3/{object}', 'v3s3\v3s3_Controller@post');
Route::post('/v3s3', 'v3s3\v3s3_Controller@post');