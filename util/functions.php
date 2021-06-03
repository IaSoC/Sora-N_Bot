<?php
//store values
$system_vaules['plugins'] = array();
$system_vaules['commands'] = array();

function plugins_loader(){
    global $system_vaules;
    $back = scandir('plugins');
    if($back === false){
        mkdir('plugins');
        $back = scandir('plugins');
    }
    unset($back[0],$back[1]);
    $back = array_merge($back);
    //load in
    foreach($back as $plugin){
        if(file_exists('plugins/'.$plugin.'/main.php')){
            require 'plugins/'.$plugin.'/main.php';
        }else{
            trigger_error('Plugin '.$plugin.' Not Exist!', E_USER_WARNING);
        }
    }
}
function register_plguin($name,$description){
    global $system_vaules;
    $system_vaules['plugins'][] = array('name'=>$name,'desc'=>$description);
}
function register_command($plugin,$command_tree){
    global $system_vaules;
    
}