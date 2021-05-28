<?php

use Illuminate\Support\Str;

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

$router->get('/key', function () use ($router) {
    return base64_encode(md5(rand()));
});

$router->get('/uuid', function () use ($router) {
    return (string) Str::uuid();
});

$router->post('/subscription/register', ['as'=>'subscriptionRegister', 'uses'=>'SubscriptionController@register']);
