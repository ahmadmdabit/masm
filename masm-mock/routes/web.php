<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('/mock/google-verification', ['middleware' => 'auth', 'as'=>'googleVerification', 'uses'=>'MockController@googleVerification']);
$router->post('/mock/ios-verification', ['middleware' => 'auth', 'as'=>'iosVerification', 'uses'=>'MockController@iosVerification']);
$router->post('/mock/slack-channel', ['middleware' => 'auth', 'as'=>'slackChannel', 'uses'=>'MockController@slackChannel']);
