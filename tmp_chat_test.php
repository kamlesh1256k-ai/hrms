<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Illuminate\Support\Facades\Auth::loginUsingId(7);
$controller = $app->make(App\Http\Controllers\vendor\Chatify\MessagesController::class);
$request = Illuminate\Http\Request::create('/chats/getContacts', 'GET');
$response = $controller->getContacts($request);
echo $response->getContent();
