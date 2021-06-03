<?php
require 'vendor/autoload.php';
require 'util/functions.php';

//load plugins
plugins_loader();
var_dump($plugins);
/*
$client = new WebSocket\Client("ws://echo.websocket.org/");
$client->text("Hello WebSocket.org!");
echo $client->receive();
$client->close();*/