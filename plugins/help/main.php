<?php
//load in
class help{
    function __construct() {
        register_plguin('help','The help');
        print "In constructor\n";
    }

    function __destruct() {
        print "Destroying " . __CLASS__ . "\n";
    }
}