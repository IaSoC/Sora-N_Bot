<?php
require 'vendor/autoload.php';
require 'util/functions.php';

//load plugins
plugins_loader();
var_dump($plugins);

$client = new WebSocket\Client("ws://127.0.0.1:6700/event");
while(1){
    echo $client->receive();
}
/*$client->text('{
    "action": "send_private_msg",
    "params": {
        "user_id": 3470004930,
        "message": "你好"
    }
}');
*/
$client->close();