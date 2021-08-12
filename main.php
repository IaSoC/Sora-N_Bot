<?php
require_once 'vendor/autoload.php';
use Workerman\Worker;

$context = array(
    'ssl' => array(
        'local_cert'  => './full_chain.pem',
        'local_pk'    => './private.key',
        'verify_peer' => false,
    )
);

$worker = new Worker("websocket://0.0.0.0:18780",$context);

$worker->transport = 'ssl';
$worker->count = 1;

$plugins = array();
$GLOBALS['configs'] = array();

$worker->onWorkerStart = function(){
    global $plugin;
    $GLOBALS['configs'] = json_decode(file_get_contents('config.json'),true);
    if(isset($GLOBALS['configs']['bot_addr']) and substr($GLOBALS['configs']['bot_addr'],-1) !== '/'){
        $GLOBALS['configs']['bot_addr'] = $GLOBALS['configs']['bot_addr'].'/';
    }

    $dir = scandir('plugins');
    unset($dir[0],$dir[1]);
    $plugins = array();
    $plugins['_listen'] = array(
        '*' => array(),
        'message' => array(
            'group' => array(),
            'private' => array(),
            '*' => array()
        ),
        'request' => array(
            'friend' => array(),
            'group' => array(
                'add' => array(),
                'invite' => array(),
                '*' => array()
            )
        ),
        'notice' => array(
            'group' => array(
                'upload' => array(),
                'admin' => array(),
                'decrease' => array(),
                'increase' => array(),
                'mute' => array(),
                'recall' => array(),
                '*' => array()
            ),
            'friend' => array(
                'recall' => array(),
                'add' => array(),
                '*' => array()
            ),
            'notify' => array(
                'poke' => array(),
                'lucky_king' => array(),
                'honor' => array(),
                '*' => array()
            )
        )
    );
    foreach($dir as $plug){
        include_once 'plugins/'.$plug;
    }
};

$worker->onWorkerReload = function(){
    global $plugins;
    $plugins = array();
    $GLOBALS['configs'] = array();
};

$worker->onConnect = function($connection) {
    $connection->onWebSocketConnect = function($connection){
        $key = explode(' ',$_SERVER['HTTP_AUTHORIZATION'])[1];
        if($key !== 'Sorano_machi'){
            $connection->close();
        }
    };
    stdout('客户端连入');
};

$worker->onMessage = function($connection, $data)
{
    global $plugins;
    $data = json_decode($data,true);

    if($data['post_type'] !== 'meta_event'){
        //event_executer('*',$data);
    }
    switch ($data['post_type']) {
        case 'message':
            event_executer('message.*',$data);
            switch ($data['message_type']) {
                case 'group':
                    event_executer('message.group',$data);
                    break;
                
                case 'private':
                    event_executer('message.private',$data);
                    break;
            }
            break;
        
        case 'request':
            event_executer('request.*',$data);
            switch ($data['request_type']) {
                case 'friend':
                    event_executer('request.friend',$data);
                    break;
                
                case 'group':
                    event_executer('request.group.*',$data);
                    switch ($data['sub_type']) {
                        case 'add':
                            event_executer('request.group.add',$data);
                            break;
                        
                        case 'invite':
                            event_executer('request.group.invite',$data);
                            break;
                    }
                    break;
            }
            break;
        
        case 'notice':
            event_executer('notice.*',$data);
            switch ($data['notice_type']) {
                case 'notify':
                    event_executer('notice.notify.*',$data);
                    switch ($data['sub_type']) {
                        case 'poke':
                            event_executer('notice.notify.poke',$data);
                            break;
                        
                        case 'lucky_king':
                            event_executer('notice.notify.lucky_king',$data);
                            break;
                        case 'honor':
                            event_executer('notice.notify.honor',$data);
                            break;
                    }
                    
                    break;
                
                case 'group_upload':
                    event_executer('notice.group.upload',$data);
                    break;
                case 'group_admin':
                    event_executer('notice.group.admin',$data);
                    break;
                case 'group_decrease':
                    event_executer('notice.group.decrease',$data);
                    break;
                case 'group_increase':
                    event_executer('notice.group.increase',$data);
                    break;
                case 'group_ban':
                    event_executer('notice.group.mute',$data);
                    break;
                case 'group_recall':
                    event_executer('notice.group.recall',$data);
                    break;
                case 'friend_add':
                    event_executer('notice.friend.add',$data);
                    break;
                case 'firend_recall':
                    event_executer('notice.friend.recall',$data);
                    break;
            }
            break;
    }

};

function send_msg($group_id,string $msg,string $msg_type = 'private',string $access_token = 'test'):Array{
    if(isset($GLOBALS['configs']['bot_access'])){
        $access_token = $GLOBALS['configs']['bot_access'];
    }
    
    if($msg_type == 'private'){
        $query_result = json_decode(file_get_contents($GLOBALS['configs']['bot_addr'].'send_private_msg?user_id='.$group_id.'&message='.urlencode($msg).'&access_token='.$access_token),true);
    }else{
        $query_result = json_decode(file_get_contents($GLOBALS['configs']['bot_addr'].'send_group_msg?group_id='.$group_id.'&message='.urlencode($msg).'&access_token='.$access_token),true);
    }

	
	if($query_result['status'] !== 'ok'){
		return array('retcode' => 1);
	}
	return array('retcode' => 0,'msg_id' => $query_result['data']['message_id']);
}
function bot_action(string $endpoint,Array $parameter = array(),string $access_token = 'test'):Array{
    if(isset($GLOBALS['configs']['bot_access'])){
        $access_token = $GLOBALS['configs']['bot_access'];
    }
    $paralist = '';
    foreach($parameter as $key => $value){
        $paralist = $paralist.$key.'='.$value.'&';
    }
    $query_result = json_decode(file_get_contents($GLOBALS['configs']['bot_addr'].$endpoint.'?'.$paralist.'access_token='.$access_token),true);
    if($query_result['status'] !== 'ok'){
		return array('retcode' => 1);
	}
    return array('retcode' => 0,'data' => $query_result);
}

function stdout($msg,$lvl = 'INFO'){
    echo '['.date('Y-m-d H:i:s').']['.$lvl.']: '.$msg.PHP_EOL;
}

function register_event($event_name,$function_name,$priority = 50){
    global $plugins;
    $event_l = explode('.',$event_name);

    $plugins_t = array();
    $plugins_t_temp = array();

    foreach($event_l as $event){
        //3ly
        switch (count($plugins_t_temp)) {
            case 0:
                $plugins_t[$event] = array();

                if(count($event_l) == 1){
                    if(!isset($plugins_t[$event][$priority])){
                        $plugins_t[$event][$priority] = array();
                    }
                    $plugins_t[$event][$priority][] = $function_name;
                }
                $plugins_t_temp[] = $event;
                break;
            case 1:
                $plugins_t[$plugins_t_temp[0]][$event] = array();
                if(count($event_l) == 2){
                    if(!isset($plugins_t[$plugins_t_temp[0]][$event][$priority])){
                        $plugins_t[$plugins_t_temp[0]][$event][$priority] = array();
                    }
                    $plugins_t[$plugins_t_temp[0]][$event][$priority][] = $function_name;
                }
                $plugins_t_temp[] = $event;
                break;
            case 2:
                $plugins_t[$plugins_t_temp[0]][$plugins_t_temp[1]][$event] = array();
                if(count($event_l) == 3){
                    if(!isset($plugins_t[$plugins_t_temp[0]][$plugins_t_temp[1]][$event][$priority])){
                        $plugins_t[$plugins_t_temp[0]][$plugins_t_temp[1]][$event][$priority] = array();
                    }
                    $plugins_t[$plugins_t_temp[0]][$plugins_t_temp[1]][$event][$priority][] = $function_name;
                }
                $plugins_t_temp[] = $event;
                break;
            case 3:
                $plugins_t[$plugins_t_temp[0]][$plugins_t_temp[1]][$plugins_t_temp[2]][$priority][$event] = array($function_name);
                break;
        }            
    }
    $plugins['_listen'] = array_merge_recursive($plugins['_listen'],$plugins_t);
}

function event_executer($event_name,$data){
    global $plugins;

    $event_l = explode('.',$event_name);

    switch (count($event_l)) {
        case 1:
            foreach($plugins['_listen'][$event_l[0]] as $per_handlers){
                foreach($per_handlers as $per_handler){
                    $r = $per_handler($data);
                    switch ($r) {
                        case 'block':
                            # code...
                            break 3;
                    }
                }
            }
            break;
        case 2:
            foreach($plugins['_listen'][$event_l[0]][$event_l[1]] as $per_handlers){
                foreach($per_handlers as $per_handler){
                    $r = $per_handler($data);
                    switch ($r) {
                        case 'block':
                            # code...
                            break 3;
                    }
                }
            }
            break;
        case 3:
            foreach($plugins['_listen'][$event_l[0]][$event_l[1]][$event_l[2]] as $per_handlers){
                foreach($per_handlers as $per_handler){
                    $r = $per_handler($data);
                    switch ($r) {
                        case 'block':
                            # code...
                            break 3;
                    }
                }
            }
            break;
    }
}

Worker::runAll();